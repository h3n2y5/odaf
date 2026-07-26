<?php

declare(strict_types=1);

namespace Odaf\Studio;

use Illuminate\Database\ConnectionInterface;

/**
 * Introspeksi struktur tabel Oracle untuk ODAF Studio Data Manager.
 *
 * Membaca kolom, primary key, constraint unik, dan foreign key (beserta kolom
 * tampilan tabel referensi) sehingga Studio dapat membangun grid & form CRUD
 * generik untuk tabel apa pun — termasuk tabel metadata (APP_*, UI_*, DS_*,
 * WF_*, NTF_*, dsb). Tidak memerlukan metadata terkompilasi.
 */
final class TableIntrospector
{
    /** @var array<string, array<string, mixed>> cache schema per tabel */
    private array $schemaCache = [];

    /** @var array<int, string>|null cache daftar tabel */
    private ?array $tableCache = null;

    /** Kandidat kolom tampilan (untuk label FK), berurutan prioritas. */
    private const DISPLAY_CANDIDATES = ['OBJECT_NAME', 'OBJECT_CODE', 'NAME', 'TITLE', 'LABEL', 'CODE'];

    public function __construct(private readonly ConnectionInterface $connection) {}

    /**
     * Daftar seluruh tabel milik schema (uppercase), terurut.
     *
     * @return array<int, string>
     */
    public function tables(): array
    {
        if ($this->tableCache !== null) {
            return $this->tableCache;
        }

        $rows = $this->connection->select(
            'SELECT TABLE_NAME FROM USER_TABLES ORDER BY TABLE_NAME'
        );

        $tables = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            if ($arr !== []) {
                $tables[] = strtoupper((string) reset($arr));
            }
        }

        return $this->tableCache = $tables;
    }

    public function tableExists(string $table): bool
    {
        return in_array(strtoupper($table), $this->tables(), true);
    }

    /**
     * Kelompokkan tabel berdasarkan prefiks domain untuk navigasi Studio.
     *
     * @return array<string, array<int, string>>
     */
    public function groupedTables(): array
    {
        $groups = [
            'Metadata' => [],
            'Runtime' => [],
            'Security' => [],
            'Audit' => [],
            'System' => [],
            'Business' => [],
        ];

        foreach ($this->tables() as $table) {
            $groups[$this->groupOf($table)][] = $table;
        }

        return array_filter($groups, static fn (array $t): bool => $t !== []);
    }

    public function groupOf(string $table): string
    {
        return match (true) {
            str_starts_with($table, 'APP_'), str_starts_with($table, 'UI_'),
            str_starts_with($table, 'DS_'), str_starts_with($table, 'VAL_'),
            str_starts_with($table, 'WF_'), str_starts_with($table, 'NTF_') => 'Metadata',
            str_starts_with($table, 'RT_') => 'Runtime',
            str_starts_with($table, 'SEC_') => 'Security',
            str_starts_with($table, 'AUD_') => 'Audit',
            str_starts_with($table, 'SYS_') => 'System',
            default => 'Business',
        };
    }

    /**
     * Skema lengkap sebuah tabel.
     *
     * @return array{
     *     table: string,
     *     columns: array<string, array<string, mixed>>,
     *     primaryKey: array<int, string>,
     *     displayColumn: string
     * }
     */
    public function schema(string $table): array
    {
        $table = strtoupper($table);
        if (isset($this->schemaCache[$table])) {
            return $this->schemaCache[$table];
        }

        $primaryKey = $this->primaryKeyColumns($table);
        $unique = $this->uniqueColumns($table);
        $foreignKeys = $this->foreignKeys($table);
        $enums = $this->checkEnums($table);

        $columns = [];
        foreach ($this->rawColumns($table) as $col) {
            $name = $col['name'];
            $fk = $foreignKeys[$name] ?? null;
            $options = $this->staticOptions($name, $col, $fk, $enums[$name] ?? null);
            $columns[$name] = [
                'name' => $name,
                'dataType' => $col['dataType'],
                'length' => $col['length'],
                'precision' => $col['precision'],
                'scale' => $col['scale'],
                'nullable' => $col['nullable'] === 'Y',
                'isPk' => in_array($name, $primaryKey, true),
                'isUnique' => in_array($name, $unique, true),
                'isSystem' => ColumnMapper::isSystemColumn($name),
                'isBinary' => ColumnMapper::isBinary($col['dataType']),
                'widget' => $this->widgetFor($col, $fk !== null, $options !== []),
                'fk' => $fk,
                'options' => $options,
            ];
        }

        return $this->schemaCache[$table] = [
            'table' => $table,
            'columns' => $columns,
            'primaryKey' => $primaryKey,
            'displayColumn' => $this->displayColumn($table, $columns, $primaryKey),
        ];
    }

    /**
     * Opsi dropdown untuk kolom foreign key: nilai (hex bila RAW) + label.
     *
     * @param  array{table: string, column: string, displayColumn: string, binary: bool}  $fk
     * @return array<int, array{value: string, label: string}>
     */
    public function fkOptions(array $fk, int $limit = 500): array
    {
        $refTable = strtoupper($fk['table']);
        $refColumn = strtoupper($fk['column']);
        $display = strtoupper($fk['displayColumn']);

        if (! preg_match('/^[A-Z0-9_$#]+$/', $refTable)
            || ! preg_match('/^[A-Z0-9_$#]+$/', $refColumn)
            || ! preg_match('/^[A-Z0-9_$#]+$/', $display)) {
            return [];
        }

        $valueExpr = $fk['binary'] ? "RAWTOHEX({$refColumn})" : $refColumn;
        $rows = $this->connection->select(
            "SELECT {$valueExpr} AS V, {$display} AS L FROM {$refTable} ORDER BY {$display} FETCH FIRST ? ROWS ONLY",
            [$limit],
        );

        $out = [];
        foreach ($rows as $r) {
            $r = array_change_key_case((array) $r, CASE_UPPER);
            $value = $r['V'] ?? null;
            if (is_resource($value)) {
                $value = stream_get_contents($value);
            }
            $label = $r['L'] ?? null;
            if (is_resource($label)) {
                $label = stream_get_contents($label);
            }
            $out[] = [
                'value' => strtoupper((string) $value),
                'label' => (string) ($label ?? $value),
            ];
        }

        return $out;
    }

    // ---- Introspeksi internal ----------------------------------------------

    /**
     * @return array<int, array{name: string, dataType: string, length: int|null, precision: int|null, scale: int|null, nullable: string}>
     */
    private function rawColumns(string $table): array
    {
        $rows = $this->connection->select(
            'SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH, DATA_PRECISION, DATA_SCALE, NULLABLE, COLUMN_ID
             FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ? ORDER BY COLUMN_ID',
            [$table],
        );

        $cols = [];
        foreach ($rows as $r) {
            $r = array_change_key_case((array) $r, CASE_UPPER);
            $cols[] = [
                'name' => strtoupper((string) $r['COLUMN_NAME']),
                'dataType' => strtoupper((string) $r['DATA_TYPE']),
                'length' => $r['DATA_LENGTH'] !== null ? (int) $r['DATA_LENGTH'] : null,
                'precision' => $r['DATA_PRECISION'] !== null ? (int) $r['DATA_PRECISION'] : null,
                'scale' => $r['DATA_SCALE'] !== null ? (int) $r['DATA_SCALE'] : null,
                'nullable' => strtoupper((string) $r['NULLABLE']),
            ];
        }

        return $cols;
    }

    /**
     * @return array<int, string>
     */
    private function primaryKeyColumns(string $table): array
    {
        $rows = $this->connection->select(
            "SELECT cc.COLUMN_NAME
             FROM USER_CONSTRAINTS c
             JOIN USER_CONS_COLUMNS cc ON cc.CONSTRAINT_NAME = c.CONSTRAINT_NAME
             WHERE c.TABLE_NAME = ? AND c.CONSTRAINT_TYPE = 'P'
             ORDER BY cc.POSITION",
            [$table],
        );

        return $this->columnList($rows);
    }

    /**
     * @return array<int, string>
     */
    private function uniqueColumns(string $table): array
    {
        $rows = $this->connection->select(
            "SELECT cc.COLUMN_NAME
             FROM USER_CONSTRAINTS c
             JOIN USER_CONS_COLUMNS cc ON cc.CONSTRAINT_NAME = c.CONSTRAINT_NAME
             WHERE c.TABLE_NAME = ? AND c.CONSTRAINT_TYPE = 'U'
               AND (SELECT COUNT(*) FROM USER_CONS_COLUMNS x WHERE x.CONSTRAINT_NAME = c.CONSTRAINT_NAME) = 1",
            [$table],
        );

        return $this->columnList($rows);
    }

    /**
     * Foreign key: kolom lokal -> {table, column, displayColumn, binary}.
     *
     * @return array<string, array{table: string, column: string, displayColumn: string, binary: bool}>
     */
    private function foreignKeys(string $table): array
    {
        $rows = $this->connection->select(
            "SELECT a.COLUMN_NAME AS LOCAL_COL, cpk.TABLE_NAME AS REF_TABLE, b.COLUMN_NAME AS REF_COL
             FROM USER_CONSTRAINTS c
             JOIN USER_CONS_COLUMNS a ON a.CONSTRAINT_NAME = c.CONSTRAINT_NAME
             JOIN USER_CONSTRAINTS cpk ON cpk.CONSTRAINT_NAME = c.R_CONSTRAINT_NAME
             JOIN USER_CONS_COLUMNS b ON b.CONSTRAINT_NAME = cpk.CONSTRAINT_NAME AND b.POSITION = a.POSITION
             WHERE c.TABLE_NAME = ? AND c.CONSTRAINT_TYPE = 'R'",
            [$table],
        );

        $fks = [];
        foreach ($rows as $r) {
            $r = array_change_key_case((array) $r, CASE_UPPER);
            $local = strtoupper((string) $r['LOCAL_COL']);
            $refTable = strtoupper((string) $r['REF_TABLE']);
            $refCol = strtoupper((string) $r['REF_COL']);
            $fks[$local] = [
                'table' => $refTable,
                'column' => $refCol,
                'displayColumn' => $this->displayColumnForTable($refTable, $refCol),
                'binary' => $this->refColumnIsBinary($refTable, $refCol),
            ];
        }

        return $fks;
    }

    private function refColumnIsBinary(string $table, string $column): bool
    {
        $type = $this->connection->scalar(
            'SELECT DATA_TYPE FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        );

        return $type !== null && ColumnMapper::isBinary((string) $type);
    }

    private function displayColumnForTable(string $table, string $fallback): string
    {
        $columns = [];
        foreach ($this->rawColumns($table) as $c) {
            $columns[$c['name']] = true;
        }

        foreach (self::DISPLAY_CANDIDATES as $candidate) {
            if (isset($columns[$candidate])) {
                return $candidate;
            }
        }
        // Kolom teks pertama selain fallback.
        foreach ($this->rawColumns($table) as $c) {
            if (in_array($c['dataType'], ['VARCHAR2', 'CHAR', 'NVARCHAR2'], true) && $c['name'] !== $fallback) {
                return $c['name'];
            }
        }

        return $fallback;
    }

    /**
     * @param  array<string, array<string, mixed>>  $columns
     * @param  array<int, string>  $primaryKey
     */
    private function displayColumn(string $table, array $columns, array $primaryKey): string
    {
        foreach (self::DISPLAY_CANDIDATES as $candidate) {
            if (isset($columns[$candidate])) {
                return $candidate;
            }
        }

        return $primaryKey[0] ?? (string) array_key_first($columns);
    }

    /**
     * Opsi statis (dropdown) untuk sebuah kolom: dari CHECK constraint (enum),
     * flag NUMBER(1) (Ya/Tidak), atau kolom STATUS (siklus hidup default).
     *
     * @param  array{name: string, dataType: string, length: int|null, precision: int|null, scale: int|null, nullable: string}  $col
     * @param  array{table: string, column: string, displayColumn: string, binary: bool}|null  $fk
     * @param  array<int, string>|null  $enumValues
     * @return array<int, array{value: string, label: string}>
     */
    private function staticOptions(string $name, array $col, ?array $fk, ?array $enumValues): array
    {
        if ($fk !== null) {
            return []; // FK: opsi dimuat dinamis via fkOptions()
        }

        $type = $col['dataType'];
        $isFlag = $type === 'NUMBER' && (int) ($col['precision'] ?? 0) === 1;

        if ($enumValues !== null && $enumValues !== []) {
            $onlyBoolean = array_diff($enumValues, ['0', '1']) === [];
            if ($isFlag || $onlyBoolean) {
                return $this->flagOptions();
            }

            return array_map(static fn (string $v): array => ['value' => $v, 'label' => $v], $enumValues);
        }

        if ($isFlag) {
            return $this->flagOptions();
        }

        if ($name === 'STATUS' && in_array($type, ['VARCHAR2', 'NVARCHAR2', 'CHAR', 'NCHAR'], true)) {
            return array_map(
                static fn (string $v): array => ['value' => $v, 'label' => $v],
                ['DRAFT', 'PUBLISHED', 'ARCHIVED', 'DEPRECATED'],
            );
        }

        return [];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function flagOptions(): array
    {
        return [
            ['value' => '1', 'label' => 'Ya (1)'],
            ['value' => '0', 'label' => 'Tidak (0)'],
        ];
    }

    /**
     * Enum per kolom dari CHECK constraint (kondisi `col IN (...)`).
     *
     * @return array<string, array<int, string>>
     */
    private function checkEnums(string $table): array
    {
        $rows = $this->connection->select(
            "SELECT cc.COLUMN_NAME, c.SEARCH_CONDITION_VC AS COND
             FROM USER_CONSTRAINTS c
             JOIN USER_CONS_COLUMNS cc ON cc.CONSTRAINT_NAME = c.CONSTRAINT_NAME
             WHERE c.TABLE_NAME = ? AND c.CONSTRAINT_TYPE = 'C' AND c.SEARCH_CONDITION_VC IS NOT NULL",
            [$table],
        );

        $enums = [];
        foreach ($rows as $r) {
            $r = array_change_key_case((array) $r, CASE_UPPER);
            $column = strtoupper((string) $r['COLUMN_NAME']);
            $cond = (string) ($r['COND'] ?? '');
            if (preg_match('/\bIN\s*\((.+)\)/is', $cond, $m) !== 1) {
                continue; // mis. NOT NULL
            }
            $values = array_values(array_filter(array_map(
                static fn (string $v): string => trim($v, " \t\n\r'\""),
                explode(',', $m[1]),
            ), static fn (string $v): bool => $v !== ''));
            if ($values !== []) {
                $enums[$column] = $values;
            }
        }

        return $enums;
    }

    /**
     * @param  array{name: string, dataType: string, length: int|null, precision: int|null, scale: int|null, nullable: string}  $col
     */
    private function widgetFor(array $col, bool $isForeignKey, bool $hasOptions = false): string
    {
        if ($isForeignKey || $hasOptions) {
            return 'select';
        }
        
        $name = strtoupper($col['name']);
        if (str_contains($name, 'PHOTO') || str_contains($name, 'FOTO') || str_contains($name, 'IMAGE') || str_contains($name, 'PIC')) {
            return 'photo';
        }
        if (str_contains($name, 'DOCUMENT') || str_contains($name, 'DOKUMEN') || str_contains($name, 'FILE') || str_contains($name, 'PDF')) {
            return 'document';
        }

        $type = $col['dataType'];
        if (ColumnMapper::isBinary($type)) {
            return 'raw';
        }
        if (in_array($type, ['CLOB', 'NCLOB', 'LONG'], true)) {
            return 'textarea';
        }
        if (in_array($type, ['VARCHAR2', 'NVARCHAR2', 'CHAR', 'NCHAR'], true)) {
            return ($col['length'] ?? 0) > 255 ? 'textarea' : 'text';
        }
        if (in_array($type, ['NUMBER', 'FLOAT', 'INTEGER', 'BINARY_FLOAT', 'BINARY_DOUBLE'], true)) {
            return 'number';
        }
        if ($type === 'DATE') {
            return 'date';
        }
        if (str_starts_with($type, 'TIMESTAMP')) {
            return 'datetime-local';
        }

        return 'text';
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, string>
     */
    private function columnList(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            if ($arr !== []) {
                $out[] = strtoupper((string) reset($arr));
            }
        }

        return $out;
    }
}
