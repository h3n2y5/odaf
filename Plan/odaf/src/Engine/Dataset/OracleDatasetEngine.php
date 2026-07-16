<?php

declare(strict_types=1);

namespace Odaf\Engine\Dataset;

use Illuminate\Database\ConnectionInterface;
use Odaf\Engine\Dataset\Contracts\DatasetEngineInterface;
use Odaf\Engine\Dataset\Contracts\DatasetResultInterface;
use Odaf\Runtime\Contracts\ExecutionContextInterface;
use Odaf\Runtime\UnifiedRuntimeKernel;
use Odaf\Support\Identity\IdentityGeneratorInterface;
use RuntimeException;

/**
 * BB-05 Dataset Engine — implementasi Oracle (Vol.3 Bab 11).
 *
 * Satu-satunya mesin CRUD generik. Tidak ada repository per-entity: seluruh
 * entity diproses berdasarkan definisi dataset terkompilasi (dari package).
 *
 * Fitur F1: query (filter/sort/paging), find, create, update (optimistic lock),
 * delete (soft/hard), kolom audit otomatis, konversi RAW(16) transparan.
 */
final class OracleDatasetEngine implements DatasetEngineInterface
{
    /** @var array<string, array<string, string>> cache: table -> (column => dataType) */
    private array $columnCache = [];

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly UnifiedRuntimeKernel $kernel,
        private readonly IdentityGeneratorInterface $identity,
    ) {}

    public function query(
        ExecutionContextInterface $context,
        string $datasetId,
        array $criteria = [],
        int $page = 1,
        int $pageSize = 25,
    ): DatasetResultInterface {
        $ds = $this->definition($context, $datasetId);
        $table = $this->tableName($ds);
        $columns = $this->columns($table);

        [$where, $bindings] = $this->buildWhere($ds, $columns, $criteria);
        // Row-level: baris DRAFT hanya terlihat oleh pembuatnya.
        [$where, $bindings] = $this->applyDraftVisibility($context, $columns, $where, $bindings);
        $orderBy = $this->buildOrder($ds, $columns, $criteria);

        $total = (int) $this->connection->scalar(
            "SELECT COUNT(*) FROM {$table} {$where}",
            $bindings,
        );

        $page = max(1, $page);
        $pageSize = max(1, $pageSize);
        $offset = ($page - 1) * $pageSize;

        $selectList = $this->selectList($columns);
        $rows = $this->connection->select(
            "SELECT {$selectList} FROM {$table} {$where} ORDER BY {$orderBy} OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$bindings, $offset, $pageSize],
        );

        return new DatasetResult(
            rows: array_map(fn ($r): array => $this->normalizeRow($r), $rows),
            total: $total,
            page: $page,
            pageSize: $pageSize,
        );
    }

    public function find(ExecutionContextInterface $context, string $datasetId, string $key): ?array
    {
        $ds = $this->definition($context, $datasetId);
        $table = $this->tableName($ds);
        $columns = $this->columns($table);
        $pk = strtoupper($ds['primaryKey']);

        $selectList = $this->selectList($columns);
        [$pkExpr, $pkBind] = $this->keyPredicate($pk, $columns, $key);

        $softDeleteClause = $this->softDeleteClause($ds, $columns);
        $where = "WHERE {$pkExpr}".($softDeleteClause !== '' ? " AND {$softDeleteClause}" : '');

        $row = $this->connection->selectOne(
            "SELECT {$selectList} FROM {$table} {$where}",
            [$pkBind],
        );

        return $row === null ? null : $this->normalizeRow($row);
    }

    public function create(ExecutionContextInterface $context, string $datasetId, array $data): string
    {
        $ds = $this->definition($context, $datasetId);
        $table = $this->tableName($ds);
        $columns = $this->columns($table);
        $pk = strtoupper($ds['primaryKey']);

        $values = $this->filterWritable($data, $columns, $pk);

        // Generate identitas PK bila RAW(16) dan belum di-supply.
        $key = '';
        if ($this->isRaw($columns[$pk] ?? '')) {
            $key = $this->identity->generate();
            $values[$pk] = ['raw' => $key];
        } elseif (isset($data[$pk])) {
            $key = (string) $data[$pk];
            $values[$pk] = $data[$pk];
        }

        // Kolom audit otomatis.
        $this->applyAuditColumns($values, $columns, $context, 'CREATE');

        [$cols, $placeholders, $bindings] = $this->compileInsert($values, $columns);

        $this->connection->insert(
            "INSERT INTO {$table} (".implode(', ', $cols).') VALUES ('.implode(', ', $placeholders).')',
            $bindings,
        );

        $this->audit($context, 'DATA_CREATE', $key, $this->guessObjectName($data), [], $data);

        return $key;
    }

    public function update(ExecutionContextInterface $context, string $datasetId, string $key, array $data): void
    {
        $ds = $this->definition($context, $datasetId);
        $table = $this->tableName($ds);
        $columns = $this->columns($table);
        $pk = strtoupper($ds['primaryKey']);

        $oldData = $this->find($context, $datasetId, $key) ?? [];

        $values = $this->filterWritable($data, $columns, $pk);
        $this->applyAuditColumns($values, $columns, $context, 'UPDATE');

        $setParts = [];
        $bindings = [];
        foreach ($values as $col => $val) {
            [$expr, $bind] = $this->valueExpr($col, $val, $columns);
            $setParts[] = "{$col} = {$expr}";
            if ($bind !== self::NO_BIND) {
                $bindings[] = $bind;
            }
        }

        // Optimistic locking via VERSION_NO bila ada.
        $versionClause = '';
        if (isset($columns['VERSION_NO'])) {
            $setParts[] = 'VERSION_NO = VERSION_NO + 1';
            if (isset($data['VERSION_NO'])) {
                $versionClause = ' AND VERSION_NO = ?';
            }
        }

        [$pkExpr, $pkBind] = $this->keyPredicate($pk, $columns, $key);
        $sql = "UPDATE {$table} SET ".implode(', ', $setParts)." WHERE {$pkExpr}{$versionClause}";
        // Urutan binding harus mengikuti placeholder di WHERE: PK dulu, lalu VERSION_NO.
        $bindings[] = $pkBind;
        if ($versionClause !== '') {
            $bindings[] = (int) $data['VERSION_NO'];
        }

        $affected = $this->connection->update($sql, $bindings);
        if ($affected === 0) {
            throw new RuntimeException('Update gagal: baris tidak ditemukan atau versi telah berubah (optimistic lock).');
        }

        $this->audit($context, 'DATA_UPDATE', $key, $this->guessObjectName(array_merge($oldData, $data)), $oldData, $data);
    }

    public function delete(ExecutionContextInterface $context, string $datasetId, string $key): void
    {
        $ds = $this->definition($context, $datasetId);
        $table = $this->tableName($ds);
        $columns = $this->columns($table);
        $pk = strtoupper($ds['primaryKey']);
        [$pkExpr, $pkBind] = $this->keyPredicate($pk, $columns, $key);

        $oldData = $this->find($context, $datasetId, $key) ?? [];

        if (($ds['softDelete'] ?? false) && isset($columns['DELETED_AT'])) {
            $set = ['DELETED_AT = SYSTIMESTAMP'];
            $bindings = [];
            if (isset($columns['DELETED_BY']) && $context->userId() !== null) {
                $set[] = 'DELETED_BY = HEXTORAW(?)';
                $bindings[] = strtoupper($context->userId());
            }
            if (isset($columns['ACTIVE_FLAG'])) {
                $set[] = 'ACTIVE_FLAG = 0';
            }
            $bindings[] = $pkBind;
            $this->connection->update(
                "UPDATE {$table} SET ".implode(', ', $set)." WHERE {$pkExpr}",
                $bindings,
            );

            $this->audit($context, 'DATA_DELETE', $key, $this->guessObjectName($oldData), $oldData, []);
            return;
        }

        $this->connection->delete("DELETE FROM {$table} WHERE {$pkExpr}", [$pkBind]);
        $this->audit($context, 'DATA_DELETE', $key, $this->guessObjectName($oldData), $oldData, []);
    }

    public function cloneRow(ExecutionContextInterface $context, string $datasetId, string $key): string
    {
        $ds = $this->definition($context, $datasetId);
        $table = $this->tableName($ds);
        $columns = $this->columns($table);
        $pk = strtoupper($ds['primaryKey']);

        if (! $this->isRaw($columns[$pk] ?? '')) {
            throw new RuntimeException('Clone hanya mendukung tabel dengan primary key RAW(16).');
        }

        $userId = $context->userId();
        $userIsRaw = $userId !== null && preg_match('/^[0-9A-Fa-f]{32}$/', $userId) === 1;
        // Kolom unik (teks) satu-kolom: regenerasi nilai agar tidak bentrok.
        $uniqueText = $this->uniqueTextColumns($table);

        $newKey = $this->identity->generate();

        $cols = [];
        $selects = [];
        $selectBind = [];

        foreach ($columns as $name => $type) {
            $cols[] = $name;

            if ($name === $pk) {
                $selects[] = 'HEXTORAW(?)';
                $selectBind[] = strtoupper($newKey);
            } elseif ($name === 'STATUS') {
                $selects[] = "'DRAFT'";
            } elseif ($name === 'CREATED_AT' || $name === 'UPDATED_AT') {
                $selects[] = 'SYSTIMESTAMP';
            } elseif ($name === 'CREATED_BY' || $name === 'UPDATED_BY') {
                if ($userIsRaw) {
                    $selects[] = 'HEXTORAW(?)';
                    $selectBind[] = strtoupper((string) $userId);
                } else {
                    $selects[] = 'NULL';
                }
            } elseif ($name === 'DELETED_AT' || $name === 'DELETED_BY') {
                $selects[] = 'NULL';
            } elseif ($name === 'VERSION_NO') {
                $selects[] = '1';
            } elseif (isset($uniqueText[$name])) {
                // Nilai unik baru dalam batas panjang kolom (karakter).
                $len = max(8, (int) $uniqueText[$name]);
                $selects[] = "SUBSTR('CLONE-' || RAWTOHEX(SYS_GUID()), 1, {$len})";
            } else {
                // Salin nilai apa adanya (aman untuk DATE/RAW/NUMBER: copy dalam DB).
                $selects[] = $name;
            }
        }

        [$pkExpr, $pkBind] = $this->keyPredicate($pk, $columns, $key);

        $sql = "INSERT INTO {$table} (".implode(', ', $cols).') '
            ."SELECT ".implode(', ', $selects)." FROM {$table} WHERE {$pkExpr}";

        $oldData = $this->find($context, $datasetId, $key) ?? [];

        $this->connection->insert($sql, [...$selectBind, $pkBind]);

        $this->audit($context, 'DATA_CLONE', $newKey, $this->guessObjectName($oldData) . ' (Clone)', $oldData, []);

        return $newKey;
    }

    /**
     * Kolom teks dengan constraint UNIQUE satu-kolom -> panjang karakter.
     * Dipakai saat clone untuk meregenerasi nilai agar tidak melanggar unik.
     *
     * @return array<string, int>
     */
    private function uniqueTextColumns(string $table): array
    {
        $rows = $this->connection->select(
            "SELECT cc.COLUMN_NAME AS CN, tc.CHAR_LENGTH AS CL, tc.DATA_TYPE AS DT
             FROM USER_CONSTRAINTS c
             JOIN USER_CONS_COLUMNS cc ON cc.CONSTRAINT_NAME = c.CONSTRAINT_NAME
             JOIN USER_TAB_COLUMNS tc ON tc.TABLE_NAME = c.TABLE_NAME AND tc.COLUMN_NAME = cc.COLUMN_NAME
             WHERE c.TABLE_NAME = ? AND c.CONSTRAINT_TYPE = 'U'
               AND (SELECT COUNT(*) FROM USER_CONS_COLUMNS x WHERE x.CONSTRAINT_NAME = c.CONSTRAINT_NAME) = 1",
            [$table],
        );

        $out = [];
        foreach ($rows as $r) {
            $r = array_change_key_case((array) $r, CASE_UPPER);
            if (str_contains(strtoupper((string) $r['DT']), 'CHAR')) {
                $out[strtoupper((string) $r['CN'])] = (int) ($r['CL'] ?? 30);
            }
        }

        return $out;
    }

    /**
     * Baris berstatus DRAFT hanya boleh dilihat pembuatnya. Diterapkan hanya
     * bila tabel memiliki kolom STATUS dan CREATED_BY.
     *
     * @param  array<string, string>  $columns
     * @param  array<int, mixed>  $bindings
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function applyDraftVisibility(ExecutionContextInterface $context, array $columns, string $where, array $bindings): array
    {
        if (! isset($columns['STATUS']) || ! isset($columns['CREATED_BY'])) {
            return [$where, $bindings];
        }

        $userId = $context->userId();
        if ($userId !== null && preg_match('/^[0-9A-Fa-f]{32}$/', $userId) === 1) {
            $clause = "(STATUS <> 'DRAFT' OR CREATED_BY = HEXTORAW(?))";
            $bindings[] = strtoupper($userId);
        } else {
            $clause = "STATUS <> 'DRAFT'";
        }

        $where = $where === '' ? "WHERE {$clause}" : "{$where} AND {$clause}";

        return [$where, $bindings];
    }

    // ---- Helpers ------------------------------------------------------------

    private const NO_BIND = "\0__NO_BIND__\0";

    /**
     * @return array<string, mixed>
     */
    private function definition(ExecutionContextInterface $context, string $datasetId): array
    {
        $ds = $this->kernel->dataset($context->applicationId(), $datasetId);
        if ($ds === null) {
            throw new RuntimeException("Dataset tidak ada pada package runtime: {$datasetId}");
        }
        if (($ds['primaryKey'] ?? '') === '') {
            throw new RuntimeException("Dataset {$datasetId} tanpa primary key terkompilasi.");
        }

        return $ds;
    }

    /**
     * @param  array<string, mixed>  $ds
     */
    private function tableName(array $ds): string
    {
        $type = (string) ($ds['sourceType'] ?? '');
        if (! in_array($type, ['TABLE', 'VIEW'], true)) {
            throw new RuntimeException("F1 hanya mendukung dataset TABLE/VIEW. Diberikan: {$type}");
        }
        $obj = (string) ($ds['sourceObject'] ?? '');
        if (! preg_match('/^[A-Za-z0-9_$#.]+$/', $obj)) {
            throw new RuntimeException("Nama sumber dataset tidak valid: {$obj}");
        }

        return strtoupper($obj);
    }

    /**
     * Kolom tabel: name => DATA_TYPE (uppercase). Di-cache.
     *
     * @return array<string, string>
     */
    private function columns(string $table): array
    {
        if (isset($this->columnCache[$table])) {
            return $this->columnCache[$table];
        }

        $rows = $this->connection->select(
            'SELECT COLUMN_NAME, DATA_TYPE FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ?',
            [$table],
        );

        $cols = [];
        foreach ($rows as $r) {
            $r = array_change_key_case((array) $r, CASE_UPPER);
            $cols[strtoupper((string) $r['COLUMN_NAME'])] = strtoupper((string) $r['DATA_TYPE']);
        }

        if ($cols === []) {
            throw new RuntimeException("Tabel sumber tidak ditemukan atau tanpa kolom: {$table}");
        }

        return $this->columnCache[$table] = $cols;
    }

    private function isRaw(string $dataType): bool
    {
        return str_starts_with($dataType, 'RAW');
    }

    /**
     * Daftar SELECT dengan RAWTOHEX untuk kolom RAW agar hasil berupa string hex.
     *
     * @param  array<string, string>  $columns
     */
    private function selectList(array $columns): string
    {
        $parts = [];
        foreach ($columns as $name => $type) {
            $parts[] = $this->isRaw($type) ? "RAWTOHEX({$name}) AS {$name}" : $name;
        }

        return implode(', ', $parts);
    }

    /**
     * Predikat primary key (RAW -> HEXTORAW).
     *
     * @param  array<string, string>  $columns
     * @return array{0: string, 1: mixed}
     */
    private function keyPredicate(string $pk, array $columns, string $key): array
    {
        if ($this->isRaw($columns[$pk] ?? '')) {
            return ["{$pk} = HEXTORAW(?)", strtoupper($key)];
        }

        return ["{$pk} = ?", $key];
    }

    /**
     * @param  array<string, mixed>  $ds
     * @param  array<string, string>  $columns
     */
    private function softDeleteClause(array $ds, array $columns): string
    {
        if (($ds['softDelete'] ?? false) && isset($columns['DELETED_AT'])) {
            return 'DELETED_AT IS NULL';
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $ds
     * @param  array<string, string>  $columns
     * @param  array<string, mixed>  $criteria
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildWhere(array $ds, array $columns, array $criteria): array
    {
        $clauses = [];
        $bindings = [];

        $soft = $this->softDeleteClause($ds, $columns);
        if ($soft !== '') {
            $clauses[] = $soft;
        }

        foreach ($criteria as $col => $value) {
            if (str_starts_with((string) $col, '_')) {
                continue; // meta seperti _sort, _dir
            }
            $colUpper = strtoupper((string) $col);
            if (! isset($columns[$colUpper])) {
                continue;
            }
            $type = $columns[$colUpper];
            if ($this->isRaw($type)) {
                $clauses[] = "{$colUpper} = HEXTORAW(?)";
                $bindings[] = strtoupper((string) $value);
            } elseif ($this->isTextType($type) && is_string($value)) {
                $clauses[] = "UPPER({$colUpper}) LIKE UPPER(?)";
                $bindings[] = '%'.$value.'%';
            } else {
                $clauses[] = "{$colUpper} = ?";
                $bindings[] = $value;
            }
        }

        $where = $clauses === [] ? '' : 'WHERE '.implode(' AND ', $clauses);

        return [$where, $bindings];
    }

    /**
     * @param  array<string, mixed>  $ds
     * @param  array<string, string>  $columns
     * @param  array<string, mixed>  $criteria
     */
    private function buildOrder(array $ds, array $columns, array $criteria): string
    {
        $sort = strtoupper((string) ($criteria['_sort'] ?? $ds['primaryKey']));
        if (! isset($columns[$sort])) {
            $sort = strtoupper((string) $ds['primaryKey']);
        }
        $dir = strtoupper((string) ($criteria['_dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        return "{$sort} {$dir}";
    }

    private function isTextType(string $type): bool
    {
        return str_contains($type, 'CHAR') || str_contains($type, 'CLOB');
    }

    /**
     * Saring data ke kolom yang benar-benar ada (kecuali PK & kolom audit terkelola).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $columns
     * @return array<string, mixed>
     */
    private function filterWritable(array $data, array $columns, string $pk): array
    {
        $managed = ['CREATED_AT', 'CREATED_BY', 'UPDATED_AT', 'UPDATED_BY', 'DELETED_AT', 'DELETED_BY', 'VERSION_NO'];
        $out = [];
        foreach ($data as $col => $val) {
            $colUpper = strtoupper((string) $col);
            if ($colUpper === $pk || in_array($colUpper, $managed, true)) {
                continue;
            }
            if (isset($columns[$colUpper])) {
                $out[$colUpper] = $val;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $values  (by-ref)
     * @param  array<string, string>  $columns
     */
    private function applyAuditColumns(array &$values, array $columns, ExecutionContextInterface $context, string $op): void
    {
        $userId = $context->userId();
        if ($op === 'CREATE') {
            if (isset($columns['CREATED_AT'])) {
                $values['CREATED_AT'] = ['sql' => 'SYSTIMESTAMP'];
            }
            if (isset($columns['CREATED_BY']) && $userId !== null) {
                $values['CREATED_BY'] = ['raw' => $userId];
            }
            if (isset($columns['VERSION_NO'])) {
                $values['VERSION_NO'] = 1;
            }
        } else {
            if (isset($columns['UPDATED_AT'])) {
                $values['UPDATED_AT'] = ['sql' => 'SYSTIMESTAMP'];
            }
            if (isset($columns['UPDATED_BY']) && $userId !== null) {
                $values['UPDATED_BY'] = ['raw' => $userId];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, string>  $columns
     * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, mixed>}
     */
    private function compileInsert(array $values, array $columns): array
    {
        $cols = [];
        $placeholders = [];
        $bindings = [];
        foreach ($values as $col => $val) {
            [$expr, $bind] = $this->valueExpr($col, $val, $columns);
            $cols[] = $col;
            $placeholders[] = $expr;
            if ($bind !== self::NO_BIND) {
                $bindings[] = $bind;
            }
        }

        return [$cols, $placeholders, $bindings];
    }

    /**
     * Ekspresi SQL + binding untuk sebuah nilai kolom.
     * Nilai khusus: ['sql' => 'SYSTIMESTAMP'] (literal), ['raw' => hex] (HEXTORAW).
     *
     * @param  array<string, string>  $columns
     * @return array{0: string, 1: mixed}
     */
    private function valueExpr(string $col, mixed $val, array $columns): array
    {
        if (is_array($val) && isset($val['sql'])) {
            return [(string) $val['sql'], self::NO_BIND];
        }
        if (is_array($val) && isset($val['raw'])) {
            return ['HEXTORAW(?)', strtoupper((string) $val['raw'])];
        }

        $type = $columns[$col] ?? '';

        if ($this->isRaw($type) && $val !== null) {
            return ['HEXTORAW(?)', strtoupper((string) $val)];
        }

        // String kosong pada kolom non-teks (DATE/NUMBER/dll) -> NULL.
        if ($val === '' && ! $this->isTextType($type)) {
            $val = null;
        }

        // Kolom tanggal/waktu: konversi eksplisit dari format input HTML
        // ('YYYY-MM-DD' atau 'YYYY-MM-DDTHH:MM') agar tidak bergantung NLS.
        if ($val !== null) {
            if (str_starts_with($type, 'DATE')) {
                return [$this->dateExpr((string) $val), $val];
            }
            if (str_starts_with($type, 'TIMESTAMP')) {
                return [$this->timestampExpr((string) $val), $val];
            }
        }

        return ['?', $val];
    }

    private function dateExpr(string $val): string
    {
        return $this->hasTime($val)
            ? "TO_DATE(?, 'YYYY-MM-DD\"T\"HH24:MI')"
            : "TO_DATE(?, 'YYYY-MM-DD')";
    }

    private function timestampExpr(string $val): string
    {
        return $this->hasTime($val)
            ? "TO_TIMESTAMP(?, 'YYYY-MM-DD\"T\"HH24:MI')"
            : "TO_TIMESTAMP(?, 'YYYY-MM-DD')";
    }

    private function hasTime(string $val): bool
    {
        return (bool) preg_match('/[T ]\d{2}:\d{2}/', $val);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeRow(mixed $row): array
    {
        $arr = (array) $row;
        $out = [];
        foreach ($arr as $k => $v) {
            if (is_resource($v)) {
                $v = stream_get_contents($v);
            }
            $out[strtoupper((string) $k)] = $v;
        }

        return $out;
    }

    private function guessObjectName(array $row): ?string
    {
        $candidates = ['OBJECT_NAME', 'NAME', 'USERNAME', 'CUSTOMER_NAME', 'TITLE', 'CODE', 'EVENT_CODE', 'DATASET_CODE'];
        foreach ($candidates as $c) {
            $val = $row[$c] ?? $row[strtolower($c)] ?? null;
            if ($val !== null && is_scalar($val)) {
                return (string) $val;
            }
        }
        return null;
    }

    private function audit(ExecutionContextInterface $context, string $eventType, string $objectId, ?string $objectName, array $before, array $after): void
    {
        try {
            $engine = app(\Odaf\Engine\Audit\Contracts\AuditEngineInterface::class);
            $engine->record($context, $eventType, $objectId, $objectName, $before, $after);
        } catch (\Throwable $e) {
            // Ignore audit errors so it doesn't fail the business operation
        }
    }
}
