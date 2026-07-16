<?php

declare(strict_types=1);

namespace Odaf\Studio;

use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;
use Odaf\Support\Identity\IdentityGeneratorInterface;
use RuntimeException;

/**
 * Metadata Scaffolder (ODAF Studio, bootstrap).
 *
 * Mereverse-engineer sebuah tabel bisnis Oracle yang sudah ada menjadi metadata
 * ODAF lengkap: dataset (DS_DATASET) + halaman form/grid (UI_PAGE) + field
 * (UI_FIELD) + menu (APP_MENU) + aturan validasi (VAL_RULE). Setelah dikompilasi,
 * CRUD form & grid langsung tersedia di runtime tanpa menulis kode.
 *
 * Ini adalah "generator" bergaya framework: metadata dihasilkan dari struktur
 * tabel (kolom, PK, unique, nullability), bukan kode sumber.
 */
final class MetadataScaffolder
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly IdentityGeneratorInterface $identity,
    ) {}

    /**
     * @param  array{table: string, appCode: string, moduleCode?: string, moduleName?: string,
     *          label?: string, menuIcon?: string|null, force?: bool}  $options
     * @return array<string, mixed> ringkasan hasil scaffold
     */
    public function scaffold(array $options): array
    {
        $table = strtoupper(trim($options['table']));
        if (! preg_match('/^[A-Z0-9_$#]+$/', $table)) {
            throw new InvalidArgumentException("Nama tabel tidak valid: {$table}");
        }

        $appCode = (string) $options['appCode'];
        $moduleCode = strtoupper((string) ($options['moduleCode'] ?? 'MASTER'));
        $moduleName = (string) ($options['moduleName'] ?? ucfirst(strtolower($moduleCode)));
        $label = (string) ($options['label'] ?? ColumnMapper::humanize($table));
        $menuIcon = $options['menuIcon'] ?? null;
        $force = (bool) ($options['force'] ?? false);

        $applicationId = $this->resolveApplicationId($appCode);
        if ($applicationId === null) {
            throw new RuntimeException("Aplikasi tidak ditemukan: {$appCode}");
        }

        $columns = $this->tableColumns($table);
        if ($columns === []) {
            throw new RuntimeException("Tabel tidak ditemukan atau tanpa kolom: {$table}");
        }
        $primaryKey = $this->primaryKeyColumn($table);
        if ($primaryKey === null) {
            throw new RuntimeException("Tabel {$table} tidak memiliki primary key (wajib untuk CRUD).");
        }
        $uniqueColumns = $this->uniqueColumns($table);
        $hasSoftDelete = isset($columns['DELETED_AT']);

        $datasetCode = 'DS_'.$table;
        $pageCode = 'PAGE_'.$table;
        $menuCode = 'MENU_'.$table;

        // Idempotensi: tabel sudah pernah di-scaffold.
        if ($this->datasetExists($datasetCode)) {
            if (! $force) {
                throw new RuntimeException(
                    "Metadata untuk {$table} sudah ada ({$datasetCode}). Gunakan --force untuk menimpa."
                );
            }
        }

        return $this->connection->transaction(function () use (
            $table, $applicationId, $moduleCode, $moduleName, $label, $menuIcon,
            $columns, $primaryKey, $uniqueColumns, $hasSoftDelete,
            $datasetCode, $pageCode, $menuCode, $force,
        ): array {
            if ($force) {
                $this->deleteExisting($datasetCode, $pageCode, $menuCode, $applicationId);
            }

            $moduleId = $this->resolveOrCreateModule($applicationId, $moduleCode, $moduleName);

            $datasetId = $this->insertDataset($datasetCode, $label.' Dataset', $table, $primaryKey, $hasSoftDelete);
            $pageId = $this->insertPage($applicationId, $datasetId, $pageCode, $label, $label.' Form');

            $fieldCount = 0;
            $ruleCount = 0;
            $order = 10;
            foreach ($columns as $name => $col) {
                if (strtoupper($name) === strtoupper($primaryKey)
                    || ColumnMapper::isSystemColumn($name)
                    || ColumnMapper::isBinary($col['dataType'])) {
                    continue;
                }

                $fieldId = $this->insertField($pageId, $name, $col, $order);
                $fieldCount++;
                $order += 10;

                $ruleCount += $this->insertRulesForField(
                    $datasetId, $fieldId, $table, $name, $col,
                    required: $col['nullable'] === 'N',
                    unique: in_array(strtoupper($name), $uniqueColumns, true),
                );
            }

            $this->insertMenu($moduleId, $pageId, $menuCode, $label, $menuIcon);

            return [
                'table' => $table,
                'datasetCode' => $datasetCode,
                'pageCode' => $pageCode,
                'menuCode' => $menuCode,
                'moduleCode' => $moduleCode,
                'fields' => $fieldCount,
                'rules' => $ruleCount,
                'primaryKey' => $primaryKey,
                'softDelete' => $hasSoftDelete,
            ];
        });
    }

    /**
     * Buat tabel transaksi baru (konvensi prefix T_) lalu scaffold metadata
     * lengkap (dataset + form/grid + menu + field). Nama tabel diturunkan dari
     * nama entitas: "Supplier" -> tabel T_SUPPLIER.
     *
     * @param  array{name: string, appCode: string, moduleCode?: string, moduleName?: string,
     *          label?: string, menuIcon?: string|null}  $options
     * @return array<string, mixed>
     */
    public function createTransactionTable(array $options): array
    {
        $entity = self::standardizeEntityName((string) ($options['name'] ?? ''));
        if ($entity === '') {
            throw new InvalidArgumentException('Nama tabel/menu tidak valid.');
        }

        $appCode = (string) $options['appCode'];
        $table = strtoupper($appCode) . '_T_' . $entity;
        if (! preg_match('/^[A-Z][A-Z0-9_$#]{0,120}$/', $table)) {
            throw new InvalidArgumentException("Nama tabel tidak valid: {$table}");
        }

        if ($this->resolveApplicationId($appCode) === null) {
            throw new RuntimeException("Aplikasi tidak ditemukan: {$appCode}");
        }

        // Buat tabel fisik bila belum ada (idempotent).
        if (! $this->tableExistsPhysical($table)) {
            $this->createPhysicalTable($table, $entity);
        }

        $label = (string) ($options['label'] ?? '') !== ''
            ? (string) $options['label']
            : ColumnMapper::humanize($entity);

        return $this->scaffold([
            'table' => $table,
            'appCode' => $appCode,
            'moduleCode' => strtoupper((string) ($options['moduleCode'] ?? 'TRANSAKSI')),
            'moduleName' => (string) ($options['moduleName'] ?? 'Transaksi'),
            'label' => $label,
            'menuIcon' => $options['menuIcon'] ?? null,
            'force' => false,
        ]);
    }

    public function createHeaderDetail(array $options): array
    {
        $headerEntity = self::standardizeEntityName((string) ($options['headerName'] ?? ''));
        $detailEntity = self::standardizeEntityName((string) ($options['detailName'] ?? ''));
        if ($headerEntity === '' || $detailEntity === '') {
            throw new InvalidArgumentException('Nama header/detail tidak valid.');
        }

        $appCode = (string) $options['appCode'];
        $headerTable = strtoupper($appCode) . '_T_' . $headerEntity;
        $detailTable = strtoupper($appCode) . '_T_' . $detailEntity;
        $headerPk = 'ID';
        $fkColumn = strtoupper((string) ($options['fkColumn'] ?? 'HEADER_ID')); // FK di detail menunjuk PK header

        if (! preg_match('/^[A-Z][A-Z0-9_$#]{0,120}$/', $detailTable)) {
            throw new InvalidArgumentException("Nama tabel detail tidak valid: {$detailTable}");
        }

        // 1. Header.
        $headerDatasetCode = 'DS_' . $headerTable;
        if ($this->datasetExists($headerDatasetCode)) {
            $header = [
                'table' => $headerTable,
                'pageCode' => 'PAGE_' . $headerTable,
                'datasetCode' => $headerDatasetCode,
            ];
        } else {
            $header = $this->createTransactionTable([
                'name' => $options['headerName'],
                'appCode' => $appCode,
            ]);
        }

        // 2. Detail (tabel fisik dengan FK) + scaffold.
        if (! $this->tableExistsPhysical($detailTable)) {
            $this->createDetailTable($detailTable, $detailEntity, $fkColumn, $headerTable, $headerPk);
        }
        $detail = $this->scaffold([
            'table' => $detailTable,
            'appCode' => $appCode,
            'moduleCode' => 'TRANSAKSI',
            'moduleName' => 'Transaksi',
            'label' => (string) ($options['detailLabel'] ?? ColumnMapper::humanize($detailEntity)),
            'force' => false,
        ]);

        // 3. Tautkan: set DETAIL_CONFIG pada page header (append mode).
        $appId = $this->resolveApplicationId($appCode);
        
        $existingConfigJson = $this->connection->scalar(
            'SELECT DETAIL_CONFIG FROM UI_PAGE WHERE OBJECT_CODE = ? AND APPLICATION_ID = HEXTORAW(?)',
            [$header['pageCode'], $appId]
        );
        $config = $existingConfigJson ? json_decode((string) $existingConfigJson, true) : [];
        if (!is_array($config)) {
            $config = [];
        }

        $newDetailConfig = [
            'pageCode' => $detail['pageCode'],
            'fkColumn' => $fkColumn,
            'title' => (string) ($options['detailLabel'] ?? ColumnMapper::humanize($detailEntity)),
        ];

        $found = false;
        foreach ($config as $k => $c) {
            if (isset($c['pageCode']) && $c['pageCode'] === $detail['pageCode']) {
                $config[$k] = $newDetailConfig;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $config[] = $newDetailConfig;
        }

        $this->connection->update(
            'UPDATE UI_PAGE SET DETAIL_CONFIG = ? WHERE OBJECT_CODE = ? AND APPLICATION_ID = HEXTORAW(?)',
            [(string) json_encode($config), $header['pageCode'], $appId],
        );

        // Detail hanya diakses lewat header -> hapus menu standalone-nya agar
        // tidak membingungkan (baris muncul sesuai header via grid detail).
        $this->connection->delete(
            'DELETE FROM APP_MENU WHERE PAGE_ID = (SELECT OBJECT_ID FROM UI_PAGE WHERE OBJECT_CODE = ? AND APPLICATION_ID = HEXTORAW(?))',
            [$detail['pageCode'], $appId],
        );

        return [
            'header' => $header,
            'detail' => $detail,
            'fkColumn' => $fkColumn,
            'headerPageCode' => $header['pageCode'],
        ];
    }

    /**
     * Buat pasangan tabel HEADER + MULTIPLE DETAIL (master-detail) sekaligus:
     * Fitur tab untuk menampilkan detail dari grup/table yang berbeda
     * (contoh: tab 1 data pajak, tab 2 data pembayaran).
     *
     * @param  array{
     *   headerName: string,
     *   details: array<int, array{detailName: string, detailLabel?: string, fkColumn?: string}>,
     *   appCode: string
     * }  $options
     * @return array<string, mixed>
     */
    public function createHeaderDetails(array $options): array
    {
        $headerEntity = self::standardizeEntityName((string) ($options['headerName'] ?? ''));
        if ($headerEntity === '') {
            throw new InvalidArgumentException('Nama header tidak valid.');
        }

        $appCode = (string) $options['appCode'];
        $headerTable = strtoupper($appCode) . '_T_' . $headerEntity;
        $headerPk = 'ID';

        $headerDatasetCode = 'DS_' . $headerTable;
        if ($this->datasetExists($headerDatasetCode)) {
            $header = [
                'table' => $headerTable,
                'pageCode' => 'PAGE_' . $headerTable,
                'datasetCode' => $headerDatasetCode,
            ];
        } else {
            $header = $this->createTransactionTable([
                'name' => $options['headerName'],
                'appCode' => $appCode,
            ]);
        }

        $appId = $this->resolveApplicationId($appCode);
        $existingConfigJson = $this->connection->scalar(
            'SELECT DETAIL_CONFIG FROM UI_PAGE WHERE OBJECT_CODE = ? AND APPLICATION_ID = HEXTORAW(?)',
            [$header['pageCode'], $appId]
        );
        $config = $existingConfigJson ? json_decode((string) $existingConfigJson, true) : [];
        if (!is_array($config)) {
            $config = [];
        }

        $detailResults = [];
        $detailsInput = $options['details'] ?? [];

        foreach ($detailsInput as $detInput) {
            $detailEntity = self::standardizeEntityName((string) ($detInput['detailName'] ?? ''));
            if ($detailEntity === '') {
                continue;
            }

            $detailTable = strtoupper($appCode) . '_T_' . $detailEntity;
            $fkColumn = strtoupper((string) ($detInput['fkColumn'] ?? 'HEADER_ID'));

            if (! preg_match('/^[A-Z][A-Z0-9_$#]{0,120}$/', $detailTable)) {
                throw new InvalidArgumentException("Nama tabel detail tidak valid: {$detailTable}");
            }

            if (! $this->tableExistsPhysical($detailTable)) {
                $this->createDetailTable($detailTable, $detailEntity, $fkColumn, $headerTable, $headerPk);
            }
            $detail = $this->scaffold([
                'table' => $detailTable,
                'appCode' => $appCode,
                'moduleCode' => 'TRANSAKSI',
                'moduleName' => 'Transaksi',
                'label' => (string) ($detInput['detailLabel'] ?? ColumnMapper::humanize($detailEntity)),
                'force' => false,
            ]);

            $detailResults[] = $detail;

            $newDetailConfig = [
                'pageCode' => $detail['pageCode'],
                'fkColumn' => $fkColumn,
                'title' => (string) ($detInput['detailLabel'] ?? ColumnMapper::humanize($detailEntity)),
            ];

            $found = false;
            foreach ($config as $k => $c) {
                if (isset($c['pageCode']) && $c['pageCode'] === $detail['pageCode']) {
                    $config[$k] = $newDetailConfig;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $config[] = $newDetailConfig;
            }

            $this->connection->delete(
                'DELETE FROM APP_MENU WHERE PAGE_ID = (SELECT OBJECT_ID FROM UI_PAGE WHERE OBJECT_CODE = ? AND APPLICATION_ID = HEXTORAW(?))',
                [$detail['pageCode'], $appId],
            );
        }

        $this->connection->update(
            'UPDATE UI_PAGE SET DETAIL_CONFIG = ? WHERE OBJECT_CODE = ? AND APPLICATION_ID = HEXTORAW(?)',
            [(string) json_encode($config), $header['pageCode'], $appId],
        );

        return [
            'header' => $header,
            'details' => $detailResults,
            'headerPageCode' => $header['pageCode'],
        ];
    }

    /**
     * DDL tabel detail: PK + kolom FK RAW(16) ke header + kolom nama + STATUS + audit.
     */
    private function createDetailTable(string $table, string $entity, string $fkColumn, string $headerTable, string $headerPk): void
    {
        $pk = 'ID';
        $nameCol = $entity.'_NAME';
        // Nama constraint FK dipangkas agar aman.
        $fkName = 'FK_'.substr($table, 0, 24).'_HDR';

        // Tanpa kolom STATUS: baris detail mengikuti header, bukan draft personal
        // (agar tidak tersembunyi oleh row-level draft visibility).
        $ddl = "CREATE TABLE {$table} (
            {$pk} RAW(16) DEFAULT SYS_GUID() NOT NULL,
            {$fkColumn} RAW(16) NOT NULL,
            {$nameCol} VARCHAR2(200 CHAR),
            CREATED_AT DATE,
            CREATED_BY RAW(16),
            UPDATED_AT DATE,
            UPDATED_BY RAW(16),
            VERSION_NO NUMBER(10) DEFAULT 1 NOT NULL,
            CONSTRAINT PK_{$table} PRIMARY KEY ({$pk}),
            CONSTRAINT {$fkName} FOREIGN KEY ({$fkColumn}) REFERENCES {$headerTable} ({$headerPk})
        )";

        $this->connection->statement($ddl);
    }

    /**
     * Standarisasi nama entitas menjadi identifier Oracle (tanpa prefix T_).
     * "Purchase Order" -> "PURCHASE_ORDER"; membuang prefix t_ bila diketik user.
     */
    public static function standardizeEntityName(string $name): string
    {
        $s = trim($name);
        if ($s === '') {
            return '';
        }

        $translit = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        if ($translit !== false) {
            $s = $translit;
        }

        $s = strtoupper($s);
        $s = preg_replace('/[^A-Z0-9]+/', '_', $s) ?? '';
        $s = trim($s, '_');
        // Buang prefix T_ agar tidak dobel (mis. user mengetik "t_supplier").
        $s = preg_replace('/^T_/', '', $s) ?? $s;
        $s = trim($s, '_');

        // Sisakan ruang untuk prefix "T_" dan sufiks "_ID".
        if (strlen($s) > 115) {
            $s = rtrim(substr($s, 0, 115), '_');
        }

        return $s;
    }

    private function tableExistsPhysical(string $table): bool
    {
        return (int) $this->connection->scalar(
            'SELECT COUNT(*) FROM USER_TABLES WHERE TABLE_NAME = ?',
            [$table],
        ) > 0;
    }

    /**
     * DDL tabel transaksi standar: PK RAW(16) + satu kolom nama + kolom audit
     * (DATE, sesuai konvensi tabel buatan) + VERSION_NO.
     */
    private function createPhysicalTable(string $table, string $entity): void
    {
        $pk = 'ID';
        $nameCol = $entity.'_NAME';

        $ddl = "CREATE TABLE {$table} (
            {$pk} RAW(16) DEFAULT SYS_GUID() NOT NULL,
            {$nameCol} VARCHAR2(200 CHAR),
            STATUS VARCHAR2(30) DEFAULT 'DRAFT' NOT NULL,
            CREATED_AT DATE,
            CREATED_BY RAW(16),
            UPDATED_AT DATE,
            UPDATED_BY RAW(16),
            VERSION_NO NUMBER(10) DEFAULT 1 NOT NULL,
            CONSTRAINT PK_{$table} PRIMARY KEY ({$pk})
        )";

        $this->connection->statement($ddl);
    }

    // ---- Introspeksi --------------------------------------------------------

    /**
     * @return array<string, array{name: string, dataType: string, length: int|null, precision: int|null, scale: int|null, nullable: string}>
     */
    private function tableColumns(string $table): array
    {
        $rows = $this->connection->select(
            'SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH, CHAR_LENGTH, DATA_PRECISION, DATA_SCALE, NULLABLE, COLUMN_ID
             FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ? ORDER BY COLUMN_ID',
            [$table],
        );

        $cols = [];
        foreach ($rows as $r) {
            $r = array_change_key_case((array) $r, CASE_UPPER);
            $name = strtoupper((string) $r['COLUMN_NAME']);
            // Panjang dalam KARAKTER (CHAR_LENGTH) lebih tepat daripada DATA_LENGTH
            // (byte) untuk kolom char-semantic; menghindari salah klasifikasi
            // VARCHAR2(200 CHAR) menjadi TEXTAREA.
            $charLength = isset($r['CHAR_LENGTH']) && (int) $r['CHAR_LENGTH'] > 0 ? (int) $r['CHAR_LENGTH'] : null;
            $length = $charLength ?? ($r['DATA_LENGTH'] !== null ? (int) $r['DATA_LENGTH'] : null);
            $cols[$name] = [
                'name' => $name,
                'dataType' => strtoupper((string) $r['DATA_TYPE']),
                'length' => $length,
                'precision' => $r['DATA_PRECISION'] !== null ? (int) $r['DATA_PRECISION'] : null,
                'scale' => $r['DATA_SCALE'] !== null ? (int) $r['DATA_SCALE'] : null,
                'nullable' => strtoupper((string) $r['NULLABLE']),
            ];
        }

        return $cols;
    }

    private function primaryKeyColumn(string $table): ?string
    {
        $value = $this->connection->scalar(
            "SELECT cc.COLUMN_NAME
             FROM USER_CONSTRAINTS c
             JOIN USER_CONS_COLUMNS cc ON cc.CONSTRAINT_NAME = c.CONSTRAINT_NAME
             WHERE c.TABLE_NAME = ? AND c.CONSTRAINT_TYPE = 'P'
             ORDER BY cc.POSITION
             FETCH FIRST 1 ROWS ONLY",
            [$table],
        );

        return $value !== null ? strtoupper((string) $value) : null;
    }

    /**
     * Kolom yang memiliki constraint UNIQUE satu-kolom.
     *
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

        $out = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            if ($arr !== []) {
                $out[] = strtoupper((string) reset($arr));
            }
        }

        return $out;
    }

    private function datasetExists(string $datasetCode): bool
    {
        return (int) $this->connection->scalar(
            'SELECT COUNT(*) FROM DS_DATASET WHERE OBJECT_CODE = ?',
            [$datasetCode],
        ) > 0;
    }

    private function resolveApplicationId(string $appCode): ?string
    {
        $value = $this->connection->scalar(
            'SELECT RAWTOHEX(OBJECT_ID) FROM APP_APPLICATION WHERE OBJECT_CODE = ?',
            [$appCode],
        );

        return $value !== null ? strtoupper((string) $value) : null;
    }

    // ---- Generasi -----------------------------------------------------------

    private function resolveOrCreateModule(string $applicationId, string $moduleCode, string $moduleName): string
    {
        $existing = $this->connection->scalar(
            'SELECT RAWTOHEX(OBJECT_ID) FROM APP_MODULE WHERE APPLICATION_ID = HEXTORAW(?) AND OBJECT_CODE = ?',
            [$applicationId, $moduleCode],
        );
        if ($existing !== null) {
            return strtoupper((string) $existing);
        }

        $id = $this->newId();
        $this->connection->insert(
            "INSERT INTO APP_MODULE (OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, DISPLAY_ORDER, STATUS)
             VALUES (HEXTORAW(?), HEXTORAW(?), ?, ?, 10, 'PUBLISHED')",
            [$id, $applicationId, $moduleCode, $moduleName],
        );

        return $id;
    }

    private function insertDataset(string $code, string $name, string $table, string $primaryKey, bool $softDelete): string
    {
        $id = $this->newId();
        $this->connection->insert(
            "INSERT INTO DS_DATASET
                (OBJECT_ID, OBJECT_CODE, OBJECT_NAME, SOURCE_TYPE, SOURCE_OBJECT, PRIMARY_KEY_COLUMN, SOFT_DELETE_FLAG, STATUS)
             VALUES (HEXTORAW(?), ?, ?, 'TABLE', ?, ?, ?, 'PUBLISHED')",
            [$id, $code, $name, $table, $primaryKey, $softDelete ? 1 : 0],
        );

        return $id;
    }

    private function insertPage(string $applicationId, string $datasetId, string $code, string $title, string $name): string
    {
        $id = $this->newId();
        $this->connection->insert(
            "INSERT INTO UI_PAGE
                (OBJECT_ID, APPLICATION_ID, DATASET_ID, OBJECT_CODE, OBJECT_NAME, TITLE, PAGE_TYPE, LAYOUT_TYPE, STATUS)
             VALUES (HEXTORAW(?), HEXTORAW(?), HEXTORAW(?), ?, ?, ?, 'FORM', 'TWO_COLUMN', 'PUBLISHED')",
            [$id, $applicationId, $datasetId, $code, $name, $title],
        );

        return $id;
    }

    /**
     * @param  array{name: string, dataType: string, length: int|null, precision: int|null, scale: int|null, nullable: string}  $col
     */
    private function insertField(string $pageId, string $name, array $col, int $order): string
    {
        $mapped = ColumnMapper::map($col);
        // Kolom STATUS: record baru default 'DRAFT' (diubah manual ke PUBLISHED).
        $defaultValue = strtoupper($name) === 'STATUS' ? 'DRAFT' : null;
        $id = $this->newId();
        $this->connection->insert(
            "INSERT INTO UI_FIELD
                (OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME, LABEL, COLUMN_NAME, FIELD_TYPE, DATA_TYPE,
                 DISPLAY_ORDER, REQUIRED_FLAG, VISIBLE_FLAG, READONLY_FLAG, DEFAULT_VALUE, STATUS)
             VALUES (HEXTORAW(?), HEXTORAW(?), ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, 'PUBLISHED')",
            [
                $id, $pageId, $name, ColumnMapper::humanize($name), ColumnMapper::humanize($name),
                $name, $mapped['fieldType'], $mapped['dataType'], $order, $col['nullable'] === 'N' ? 1 : 0,
                $defaultValue,
            ],
        );

        return $id;
    }

    /**
     * @param  array{name: string, dataType: string, length: int|null, precision: int|null, scale: int|null, nullable: string}  $col
     */
    private function insertRulesForField(
        string $datasetId,
        string $fieldId,
        string $table,
        string $column,
        array $col,
        bool $required,
        bool $unique,
    ): int {
        $count = 0;
        $label = ColumnMapper::humanize($column);

        if ($required) {
            $this->insertRule($datasetId, $fieldId, $table, $column, 'REQUIRED', null, "{$label} wajib diisi.");
            $count++;
        }
        if ($unique) {
            $this->insertRule($datasetId, $fieldId, $table, $column, 'UNIQUE', null, "{$label} sudah digunakan.");
            $count++;
        }
        if (in_array($col['dataType'], ['VARCHAR2', 'NVARCHAR2', 'CHAR', 'NCHAR'], true) && $col['length'] !== null && $col['length'] <= 4000) {
            $this->insertRule($datasetId, $fieldId, $table, $column, 'MAX_LENGTH', (string) $col['length'], "{$label} maksimal {$col['length']} karakter.");
            $count++;
        }

        return $count;
    }

    private function insertRule(
        string $datasetId,
        string $fieldId,
        string $table,
        string $column,
        string $ruleType,
        ?string $expression,
        string $message,
    ): void {
        $code = sprintf('VAL_%s_%s_%s', $table, $column, $ruleType);
        $this->connection->insert(
            "INSERT INTO VAL_RULE
                (OBJECT_ID, OBJECT_CODE, OBJECT_NAME, TARGET_TYPE, DATASET_ID, FIELD_ID, RULE_TYPE, RULE_EXPRESSION, ERROR_MESSAGE, ACTIVE_FLAG, STATUS)
             VALUES (HEXTORAW(?), ?, ?, 'FIELD', HEXTORAW(?), HEXTORAW(?), ?, ?, ?, 1, 'PUBLISHED')",
            [$this->newId(), $code, $code, $datasetId, $fieldId, $ruleType, $expression, $message],
        );
    }

    private function insertMenu(string $moduleId, string $pageId, string $code, string $name, ?string $icon): void
    {
        // DISPLAY_ORDER = jumlah menu existing pada modul + 10.
        $order = ((int) $this->connection->scalar(
            'SELECT COUNT(*) FROM APP_MENU WHERE MODULE_ID = HEXTORAW(?)',
            [$moduleId],
        ) + 1) * 10;

        $this->connection->insert(
            "INSERT INTO APP_MENU
                (OBJECT_ID, MODULE_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME, ICON, DISPLAY_ORDER, VISIBLE_FLAG, STATUS)
             VALUES (HEXTORAW(?), HEXTORAW(?), HEXTORAW(?), ?, ?, ?, ?, 1, 'PUBLISHED')",
            [$this->newId(), $moduleId, $pageId, $code, $name, $icon, $order],
        );
    }

    private function deleteExisting(string $datasetCode, string $pageCode, string $menuCode, string $applicationId): void
    {
        $datasetId = $this->connection->scalar('SELECT RAWTOHEX(OBJECT_ID) FROM DS_DATASET WHERE OBJECT_CODE = ?', [$datasetCode]);
        $pageId = $this->connection->scalar(
            'SELECT RAWTOHEX(OBJECT_ID) FROM UI_PAGE WHERE OBJECT_CODE = ? AND APPLICATION_ID = HEXTORAW(?)',
            [$pageCode, $applicationId],
        );

        // Urutan hapus mengikuti dependensi FK.
        if ($datasetId !== null) {
            $this->connection->delete('DELETE FROM VAL_RULE WHERE DATASET_ID = HEXTORAW(?)', [strtoupper((string) $datasetId)]);
        }
        if ($pageId !== null) {
            $pid = strtoupper((string) $pageId);
            $this->connection->delete(
                'DELETE FROM VAL_RULE WHERE FIELD_ID IN (SELECT OBJECT_ID FROM UI_FIELD WHERE PAGE_ID = HEXTORAW(?))',
                [$pid],
            );
            $this->connection->delete('DELETE FROM APP_MENU WHERE PAGE_ID = HEXTORAW(?)', [$pid]);
            $this->connection->delete('DELETE FROM UI_FIELD WHERE PAGE_ID = HEXTORAW(?)', [$pid]);
            $this->connection->delete('DELETE FROM UI_PAGE WHERE OBJECT_ID = HEXTORAW(?)', [$pid]);
        }
        if ($datasetId !== null) {
            $this->connection->delete('DELETE FROM DS_DATASET WHERE OBJECT_ID = HEXTORAW(?)', [strtoupper((string) $datasetId)]);
        }
    }

    private function newId(): string
    {
        return strtoupper($this->identity->generate());
    }
}
