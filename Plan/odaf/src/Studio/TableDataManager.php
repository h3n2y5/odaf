<?php

declare(strict_types=1);

namespace Odaf\Studio;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Odaf\Support\Identity\IdentityGeneratorInterface;
use RuntimeException;

/**
 * CRUD generik berbasis introspeksi untuk ODAF Studio Data Manager.
 *
 * Beroperasi pada tabel Oracle apa pun tanpa metadata terkompilasi. Menangani
 * konversi RAW(16) (hex), DATE/TIMESTAMP (TO_DATE/TO_CHAR), CLOB, primary key
 * tunggal maupun komposit, generasi PK RAW(16) otomatis, kolom audit, dan
 * optimistic locking via VERSION_NO.
 */
final class TableDataManager
{
    private const SYSTEM_COLUMNS = ['CREATED_AT', 'CREATED_BY', 'UPDATED_AT', 'UPDATED_BY', 'VERSION_NO'];

    private const NO_BIND = "\0__NO_BIND__\0";

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly TableIntrospector $introspector,
        private readonly IdentityGeneratorInterface $identity,
    ) {}

    /**
     * Daftar baris dengan pencarian + paginasi.
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, pageSize: int, lastPage: int}
     */
    /**
     * @param  array<string, mixed>  $filters  filter per kolom (COLUMN => nilai)
     */
    public function list(string $table, string $search = '', ?string $sort = null, string $dir = 'ASC', int $page = 1, int $pageSize = 20, array $filters = []): array
    {
        $schema = $this->schema($table);
        $columns = $schema['columns'];
        $tableName = $schema['table'];

        [$where, $bindings] = $this->whereClause($columns, $search, $filters);
        $orderBy = $this->orderClause($schema, $sort, $dir);
        $selectList = $this->selectList($columns);

        $total = (int) $this->connection->scalar("SELECT COUNT(*) FROM {$tableName} {$where}", $bindings);

        $page = max(1, $page);
        $pageSize = max(1, $pageSize);
        $offset = ($page - 1) * $pageSize;

        $rows = $this->connection->select(
            "SELECT {$selectList} FROM {$tableName} {$where} ORDER BY {$orderBy} OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$bindings, $offset, $pageSize],
        );

        return [
            'rows' => array_map(fn ($r): array => $this->normalizeRow($r), $rows),
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'lastPage' => $pageSize > 0 ? (int) max(1, ceil($total / $pageSize)) : 1,
        ];
    }

    /**
     * Ambil satu baris berdasarkan key (map kolom PK -> nilai).
     *
     * @param  array<string, mixed>  $key
     * @return array<string, mixed>|null
     */
    public function find(string $table, array $key): ?array
    {
        $schema = $this->schema($table);
        [$where, $bindings] = $this->keyPredicate($schema, $key);
        $selectList = $this->selectList($schema['columns']);

        $row = $this->connection->selectOne(
            "SELECT {$selectList} FROM {$schema['table']} WHERE {$where}",
            $bindings,
        );

        return $row === null ? null : $this->normalizeRow($row);
    }

    /**
     * Buat baris baru. Mengembalikan key (map kolom PK -> nilai) baris tersebut.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public function create(string $table, array $data, ?string $userId = null): array
    {
        $schema = $this->schema($table);
        $columns = $schema['columns'];
        $pk = $schema['primaryKey'];

        $values = $this->writableValues($data, $columns, isUpdate: false);
        $key = [];

        // Generasi PK RAW(16) tunggal bila belum di-supply.
        if (count($pk) === 1) {
            $pkCol = $pk[0];
            if (($columns[$pkCol]['isBinary'] ?? false) && ! $this->provided($data, $pkCol)) {
                $generated = strtoupper($this->identity->generate());
                $values[$pkCol] = ['raw' => $generated];
                $key[$pkCol] = $generated;
            }
        }
        // Nilai PK yang di-supply pengguna (mis. key komposit / natural key).
        foreach ($pk as $pkCol) {
            if (! isset($key[$pkCol]) && $this->provided($data, $pkCol)) {
                $key[$pkCol] = ($columns[$pkCol]['isBinary'] ?? false)
                    ? strtoupper((string) $data[$pkCol])
                    : (string) $data[$pkCol];
            }
        }

        $this->applyAudit($values, $columns, $userId, isUpdate: false);

        [$cols, $placeholders, $bindings] = $this->compileValues($values, $columns);
        $sql = "INSERT INTO {$schema['table']} (".implode(', ', $cols).') VALUES ('.implode(', ', $placeholders).')';

        $this->execWrite(fn () => $this->connection->insert($sql, $bindings));

        $this->audit($schema['table'], 'DATA_CREATE', $key, $this->guessObjectName($data), [], $data, $userId);

        return $key;
    }

    /**
     * Duplikat (clone) sebuah baris menjadi record baru. PK RAW(16) baru
     * digenerate; STATUS diset 'DRAFT' bila kolom ada; kolom audit di-reset.
     * Menyalin nilai di level DB (INSERT ... SELECT) sehingga aman untuk semua
     * tipe kolom (DATE/RAW/NUMBER). Mengembalikan key baru.
     *
     * @param  array<string, mixed>  $key
     * @return array<string, string>
     */
    public function cloneRow(string $table, array $key, ?string $userId = null): array
    {
        $schema = $this->schema($table);
        $columns = $schema['columns'];
        $pk = $schema['primaryKey'];

        if (count($pk) !== 1 || ! ($columns[$pk[0]]['isBinary'] ?? false)) {
            throw new RuntimeException('Clone hanya mendukung tabel dengan primary key tunggal RAW(16).');
        }

        $pkCol = $pk[0];
        $newKey = strtoupper($this->identity->generate());
        $userIsRaw = $userId !== null && preg_match('/^[0-9A-Fa-f]{32}$/', $userId) === 1;
        $uniqueText = $this->uniqueTextColumns($schema['table']);

        $cols = [];
        $selects = [];
        $bind = [];

        foreach ($columns as $name => $meta) {
            $cols[] = $name;
            if ($name === $pkCol) {
                $selects[] = 'HEXTORAW(?)';
                $bind[] = $newKey;
            } elseif ($name === 'STATUS') {
                $selects[] = "'DRAFT'";
            } elseif ($name === 'CREATED_AT' || $name === 'UPDATED_AT') {
                $selects[] = 'SYSTIMESTAMP';
            } elseif ($name === 'CREATED_BY' || $name === 'UPDATED_BY') {
                if ($userIsRaw) {
                    $selects[] = 'HEXTORAW(?)';
                    $bind[] = strtoupper((string) $userId);
                } else {
                    $selects[] = 'NULL';
                }
            } elseif ($name === 'DELETED_AT' || $name === 'DELETED_BY') {
                $selects[] = 'NULL';
            } elseif ($name === 'VERSION_NO') {
                $selects[] = '1';
            } elseif (isset($uniqueText[$name])) {
                // Kolom unik teks: nilai baru agar tidak melanggar constraint.
                $len = max(8, (int) $uniqueText[$name]);
                $selects[] = "SUBSTR('CLONE-' || RAWTOHEX(SYS_GUID()), 1, {$len})";
            } else {
                $selects[] = $name;
            }
        }

        [$where, $whereBind] = $this->keyPredicate($schema, $key);

        $sql = "INSERT INTO {$schema['table']} (".implode(', ', $cols).') '
            .'SELECT '.implode(', ', $selects)." FROM {$schema['table']} WHERE {$where}";

        $oldData = $this->find($table, $key) ?? [];
        $this->execWrite(fn () => $this->connection->insert($sql, [...$bind, ...$whereBind]));

        $this->audit($schema['table'], 'DATA_CLONE', [$pkCol => $newKey], $this->guessObjectName($oldData) . ' (Clone)', $oldData, [], $userId);

        return [$pkCol => $newKey];
    }

    /**
     * Kolom teks dengan constraint UNIQUE satu-kolom -> panjang karakter.
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
     * Perbarui baris.
     *
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $data
     */
    public function update(string $table, array $key, array $data, ?string $userId = null): void
    {
        $schema = $this->schema($table);
        $columns = $schema['columns'];

        $values = $this->writableValues($data, $columns, isUpdate: true);
        $this->applyAudit($values, $columns, $userId, isUpdate: true);
        
        $oldData = $this->find($table, $key) ?? [];

        $setParts = [];
        $bindings = [];
        foreach ($values as $col => $val) {
            [$expr, $bind] = $this->valueExpr($col, $val, $columns);
            $setParts[] = "{$col} = {$expr}";
            if ($bind !== self::NO_BIND) {
                $bindings[] = $bind;
            }
        }

        if (isset($columns['VERSION_NO'])) {
            $setParts[] = 'VERSION_NO = VERSION_NO + 1';
        }

        if ($setParts === []) {
            return;
        }

        [$where, $keyBindings] = $this->keyPredicate($schema, $key);
        $sql = "UPDATE {$schema['table']} SET ".implode(', ', $setParts)." WHERE {$where}";

        $affected = $this->execWrite(fn () => $this->connection->update($sql, [...$bindings, ...$keyBindings]));
        if ($affected === 0) {
            throw new RuntimeException('Baris tidak ditemukan untuk diperbarui.');
        }
        
        $this->audit($schema['table'], 'DATA_UPDATE', $key, $this->guessObjectName(array_merge($oldData, $data)), $oldData, $data, $userId);
    }

    /**
     * Hapus baris (hard delete).
     *
     * @param  array<string, mixed>  $key
     */
    public function delete(string $table, array $key): void
    {
        $schema = $this->schema($table);
        [$where, $bindings] = $this->keyPredicate($schema, $key);
        
        $oldData = $this->find($table, $key) ?? [];

        $this->execWrite(fn () => $this->connection->delete("DELETE FROM {$schema['table']} WHERE {$where}", $bindings));
        
        $this->audit($schema['table'], 'DATA_DELETE', $key, $this->guessObjectName($oldData), $oldData, [], null);
    }

    // ---- Key encoding -------------------------------------------------------

    /**
     * Encode key (map PK) menjadi token URL-safe.
     *
     * @param  array<string, mixed>  $key
     */
    public static function encodeKey(array $key): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($key)), '+/', '-_'), '=');
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeKey(string $token): array
    {
        $json = base64_decode(strtr($token, '-_', '+/'), true);
        if ($json === false) {
            return [];
        }
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Bangun key dari sebuah baris hasil (nilai kolom PK).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function keyFromRow(string $table, array $row): array
    {
        $key = [];
        foreach ($this->schema($table)['primaryKey'] as $col) {
            $key[$col] = $row[$col] ?? null;
        }

        return $key;
    }

    // ---- Helpers ------------------------------------------------------------

    /**
     * @return array{table: string, columns: array<string, array<string, mixed>>, primaryKey: array<int, string>, displayColumn: string}
     */
    private function schema(string $table): array
    {
        if (! $this->introspector->tableExists($table)) {
            throw new RuntimeException("Tabel tidak ditemukan: {$table}");
        }

        return $this->introspector->schema($table);
    }

    /**
     * @param  array<string, array<string, mixed>>  $columns
     */
    private function selectList(array $columns): string
    {
        $parts = [];
        foreach ($columns as $name => $meta) {
            $type = (string) $meta['dataType'];
            if (($meta['isBinary'] ?? false)) {
                $parts[] = "RAWTOHEX({$name}) AS {$name}";
            } elseif ($type === 'DATE') {
                $parts[] = "TO_CHAR({$name}, 'YYYY-MM-DD') AS {$name}";
            } elseif (str_starts_with($type, 'TIMESTAMP')) {
                $parts[] = "TO_CHAR({$name}, 'YYYY-MM-DD\"T\"HH24:MI') AS {$name}";
            } else {
                $parts[] = $name;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * @param  array<string, array<string, mixed>>  $columns
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function searchClause(array $columns, string $search): array
    {
        if (trim($search) === '') {
            return ['', []];
        }

        $clauses = [];
        $bindings = [];
        foreach ($columns as $name => $meta) {
            if (in_array((string) $meta['dataType'], ['VARCHAR2', 'CHAR', 'NVARCHAR2', 'NCHAR'], true)) {
                $clauses[] = "UPPER({$name}) LIKE UPPER(?)";
                $bindings[] = '%'.$search.'%';
            }
        }

        if ($clauses === []) {
            return ['', []];
        }

        return ['WHERE ('.implode(' OR ', $clauses).')', $bindings];
    }

    /**
     * Gabungkan pencarian global (OR antar kolom teks) dengan filter per-kolom (AND).
     *
     * @param  array<string, array<string, mixed>>  $columns
     * @param  array<string, mixed>  $filters
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function whereClause(array $columns, string $search, array $filters): array
    {
        $parts = [];
        $bindings = [];

        // Pencarian global.
        [$searchWhere, $searchBind] = $this->searchClause($columns, $search);
        if ($searchWhere !== '') {
            $parts[] = '('.substr($searchWhere, 6).')'; // buang prefix "WHERE "
            $bindings = array_merge($bindings, $searchBind);
        }

        // Filter per-kolom (LIKE, case-insensitive).
        foreach ($filters as $col => $val) {
            if ($val === '' || $val === null) {
                continue;
            }
            $name = strtoupper((string) $col);
            if (! isset($columns[$name])) {
                continue;
            }
            $meta = $columns[$name];
            $type = (string) $meta['dataType'];

            if (($meta['isBinary'] ?? false)) {
                $parts[] = "UPPER(RAWTOHEX({$name})) LIKE UPPER(?)";
            } elseif (in_array($type, ['VARCHAR2', 'CHAR', 'NVARCHAR2', 'NCHAR', 'CLOB', 'NCLOB'], true)) {
                $parts[] = "UPPER({$name}) LIKE UPPER(?)";
            } else {
                $parts[] = "UPPER(TO_CHAR({$name})) LIKE UPPER(?)";
            }
            $bindings[] = '%'.$val.'%';
        }

        $where = $parts === [] ? '' : 'WHERE '.implode(' AND ', $parts);

        return [$where, $bindings];
    }

    /**
     * @param  array{columns: array<string, array<string, mixed>>, primaryKey: array<int, string>}  $schema
     */
    private function orderClause(array $schema, ?string $sort, string $dir): string
    {
        $columns = $schema['columns'];
        $sort = $sort !== null ? strtoupper($sort) : null;
        if ($sort === null || ! isset($columns[$sort]) || $this->isLob((string) $columns[$sort]['dataType'])) {
            $sort = $schema['primaryKey'][0] ?? (string) array_key_first($columns);
        }
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        return "{$sort} {$dir}";
    }

    private function isLob(string $type): bool
    {
        return in_array($type, ['CLOB', 'NCLOB', 'BLOB', 'LONG'], true);
    }

    /**
     * @param  array{table: string, columns: array<string, array<string, mixed>>, primaryKey: array<int, string>}  $schema
     * @param  array<string, mixed>  $key
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function keyPredicate(array $schema, array $key): array
    {
        $columns = $schema['columns'];
        $pk = $schema['primaryKey'];
        if ($pk === []) {
            throw new RuntimeException("Tabel {$schema['table']} tidak memiliki primary key; tidak dapat diedit.");
        }

        $clauses = [];
        $bindings = [];
        foreach ($pk as $col) {
            if (! array_key_exists($col, $key)) {
                throw new RuntimeException("Nilai primary key tidak lengkap: {$col}");
            }
            if (($columns[$col]['isBinary'] ?? false)) {
                $clauses[] = "{$col} = HEXTORAW(?)";
                $bindings[] = strtoupper((string) $key[$col]);
            } else {
                $clauses[] = "{$col} = ?";
                $bindings[] = $key[$col];
            }
        }

        return [implode(' AND ', $clauses), $bindings];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function provided(array $data, string $column): bool
    {
        return array_key_exists($column, $data) && $data[$column] !== null && $data[$column] !== '';
    }

    /**
     * Nilai yang boleh ditulis (kecuali kolom sistem; PK diperlakukan khusus).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, mixed>>  $columns
     * @return array<string, mixed>
     */
    private function writableValues(array $data, array $columns, bool $isUpdate): array
    {
        $out = [];
        foreach ($data as $col => $val) {
            $col = strtoupper((string) $col);
            if (! isset($columns[$col]) || in_array($col, self::SYSTEM_COLUMNS, true)) {
                continue;
            }
            // Saat update, kolom PK tidak diubah.
            if ($isUpdate && ($columns[$col]['isPk'] ?? false)) {
                continue;
            }
            $out[$col] = $val === '' ? null : $val;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $values  (by-ref)
     * @param  array<string, array<string, mixed>>  $columns
     */
    private function applyAudit(array &$values, array $columns, ?string $userId, bool $isUpdate): void
    {
        if (! $isUpdate) {
            if (isset($columns['CREATED_AT'])) {
                $values['CREATED_AT'] = ['sql' => 'SYSTIMESTAMP'];
            }
            if (isset($columns['CREATED_BY']) && $userId !== null) {
                $values['CREATED_BY'] = ['raw' => $userId];
            }
        }
        if (isset($columns['UPDATED_AT'])) {
            $values['UPDATED_AT'] = ['sql' => 'SYSTIMESTAMP'];
        }
        if (isset($columns['UPDATED_BY']) && $userId !== null) {
            $values['UPDATED_BY'] = ['raw' => $userId];
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, array<string, mixed>>  $columns
     * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, mixed>}
     */
    private function compileValues(array $values, array $columns): array
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
     * Ekspresi SQL + binding untuk nilai kolom (RAW/DATE/TIMESTAMP/scalar).
     *
     * @param  array<string, array<string, mixed>>  $columns
     * @return array{0: string, 1: mixed}
     */
    private function valueExpr(string $col, mixed $val, array $columns): array
    {
        if (is_array($val) && isset($val['sql'])) {
            return [(string) $val['sql'], self::NO_BIND];
        }
        if (is_array($val) && isset($val['raw'])) {
            return $val['raw'] === null || $val['raw'] === ''
                ? ['NULL', self::NO_BIND]
                : ['HEXTORAW(?)', strtoupper((string) $val['raw'])];
        }

        $type = (string) ($columns[$col]['dataType'] ?? '');

        if ($val === null) {
            return ['NULL', self::NO_BIND];
        }
        if (ColumnMapper::isBinary($type)) {
            return ['HEXTORAW(?)', strtoupper((string) $val)];
        }
        if ($type === 'DATE') {
            $fmt = str_contains((string) $val, 'T') ? 'YYYY-MM-DD"T"HH24:MI' : 'YYYY-MM-DD';

            return ["TO_DATE(?, '{$fmt}')", (string) $val];
        }
        if (str_starts_with($type, 'TIMESTAMP')) {
            $fmt = str_contains((string) $val, 'T') ? 'YYYY-MM-DD"T"HH24:MI' : 'YYYY-MM-DD';

            return ["TO_TIMESTAMP(?, '{$fmt}')", (string) $val];
        }

        return ['?', $val];
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

    /**
     * Jalankan operasi tulis dengan pesan error yang ramah (FK/constraint).
     *
     * @template T
     *
     * @param  callable(): T  $operation
     * @return T
     */
    private function execWrite(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (QueryException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'ORA-02292')) {
                throw new RuntimeException('Tidak dapat menghapus: baris masih direferensikan baris lain (foreign key).');
            }
            if (str_contains($message, 'ORA-02291')) {
                throw new RuntimeException('Referensi tidak valid: nilai foreign key tidak ditemukan.');
            }
            if (str_contains($message, 'ORA-00001')) {
                throw new RuntimeException('Nilai duplikat: melanggar constraint unik.');
            }
            if (str_contains($message, 'ORA-01400')) {
                throw new RuntimeException('Kolom wajib tidak boleh kosong (NOT NULL).');
            }
            throw new RuntimeException('Operasi gagal: '.$this->firstLine($message));
        }
    }

    private function firstLine(string $text): string
    {
        $line = strtok($text, "\n");

        return $line === false ? $text : $line;
    }

    private function guessObjectName(array $row): ?string
    {
        $candidates = ['OBJECT_NAME', 'NAME', 'USERNAME', 'CUSTOMER_NAME', 'TITLE', 'CODE', 'EVENT_CODE', 'DATASET_CODE'];
        foreach ($candidates as $c) {
            // Cek case-insensitive
            $val = $row[$c] ?? $row[strtolower($c)] ?? null;
            if ($val !== null && is_scalar($val)) {
                return (string) $val;
            }
        }
        return null;
    }

    private function audit(string $table, string $eventType, array $key, ?string $objectName, array $before, array $after, ?string $userId): void
    {
        try {
            $engine = app(\Odaf\Engine\Audit\Contracts\AuditEngineInterface::class);
            $context = new class($table, $userId) implements \Odaf\Runtime\Contracts\ExecutionContextInterface {
                public function __construct(private string $t, private ?string $u) {}
                public function userId(): ?string { return $this->u; }
                public function applicationId(): string { return ''; }
                public function locale(): string { return 'id'; }
                public function roleIds(): array { return []; }
                public function attributes(): array { return ['datasetCode' => $this->t]; }
            };
            
            $objectId = implode('-', $key);
            
            $engine->record($context, $eventType, $objectId, $objectName, $before, $after);
        } catch (\Throwable $e) {
            // Ignore audit errors so it doesn't fail the business operation
        }
    }
}
