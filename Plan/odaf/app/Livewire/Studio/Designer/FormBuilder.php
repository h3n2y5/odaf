<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Odaf\Studio\MetadataScaffolder;

/**
 * Form Builder - Visual designer untuk UI_PAGE dan UI_FIELD.
 *
 * Kolom UI_FIELD (skema aktual):
 *   OBJECT_ID, PAGE_ID, LOV_ID, OBJECT_CODE, OBJECT_NAME, LABEL, COLUMN_NAME,
 *   FIELD_TYPE, DATA_TYPE, DISPLAY_ORDER, REQUIRED_FLAG, VISIBLE_FLAG,
 *   READONLY_FLAG, DEFAULT_VALUE, VERSION_NO, STATUS, ..., LOV_LABEL_COLUMN
 */
#[Layout('layouts.odaf')]
final class FormBuilder extends Component
{
    use NormalizesRows;

    public string $pageId;

    /** @var array<string, mixed> */
    public array $page = [];

    /** @var array<int, array<string, mixed>> */
    public array $fields = [];

    /** @var array<int, array<string, mixed>> */
    public array $availableLovs = [];

    public ?string $selectedFieldId = null;

    /** @var array<string, mixed> */
    public array $fieldEditor = [
        'label' => '',
        'field_type' => 'TEXT',
        'required_flag' => 0,
        'readonly_flag' => 0,
        'visible_flag' => 1,
        'lov_id' => null,
        'default_value' => null,
        // Opsi format DATE/DATETIME.
        'date_default' => '',      // '' | 'SYSDATE'
        'date_with_time' => 0,
        // Opsi format NUMBER/DECIMAL.
        'num_decimals' => '',      // '' | angka
        'num_thousands' => 0,
        'calculation' => '',
    ];

    public string $previewMode = 'desktop'; // desktop | tablet | mobile

    /** Dialog tambah field. */
    public bool $showAddModal = false;

    /** @var array<string, mixed> */
    public array $newField = [
        'label' => '',
        'field_type' => 'TEXT',
        'length' => 255,
        'add_column' => true,
    ];

    /** Dialog tambah tab detail. */
    public bool $showAddTabModal = false;

    /** @var array<string, mixed> */
    public array $newTab = [
        'title' => '',
        'suffix' => '',
    ];

    public function mount(StudioAccess $access, string $pageId): void
    {
        $access->ensureAdmin();

        $this->pageId = $pageId;
        $this->loadPage();
        $this->loadFields();
        $this->loadAvailableLovs();
    }

    public function render()
    {
        return view('livewire.studio.designer.form-builder', [
            'fieldTypes' => $this->getFieldTypes(),
            'fieldWidgets' => $this->getFieldWidgets(),
        ]);
    }

    /**
     * Kompilasi & aktifkan aplikasi agar perubahan metadata (field baru,
     * LOV, dsb.) tercermin di runtime.
     */
    public function compile(): void
    {
        $appCode = $this->page['APPLICATION_CODE'] ?? null;

        if (!$appCode) {
            session()->flash('error', 'Kode aplikasi tidak ditemukan untuk halaman ini.');
            return;
        }

        try {
            $exit = \Artisan::call('odaf:compile', [
                'application' => $appCode,
                '--activate' => true,
            ]);

            $output = trim(\Artisan::output());

            if ($exit === 0) {
                session()->flash('success', "Aplikasi {$appCode} berhasil dikompilasi & diaktifkan. Perubahan sudah tampil di runtime.");
            } else {
                session()->flash('error', "Kompilasi gagal (kode {$exit}). " . $output);
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Kompilasi gagal: ' . $e->getMessage());
        }
    }

    private function loadPage(): void
    {
        $page = DB::selectOne("
            SELECT
                RAWTOHEX(P.OBJECT_ID) AS ID,
                P.OBJECT_CODE,
                P.OBJECT_NAME,
                P.TITLE,
                P.DESCRIPTION,
                P.PAGE_TYPE,
                P.DETAIL_CONFIG,
                RAWTOHEX(P.DATASET_ID) AS DATASET_ID,
                D.OBJECT_CODE AS DATASET_CODE,
                D.SOURCE_TYPE,
                D.SOURCE_OBJECT AS TABLE_NAME,
                RAWTOHEX(P.APPLICATION_ID) AS APPLICATION_ID,
                A.OBJECT_CODE AS APPLICATION_CODE,
                A.OBJECT_NAME AS APPLICATION_NAME
            FROM UI_PAGE P
            LEFT JOIN DS_DATASET D ON P.DATASET_ID = D.OBJECT_ID
            LEFT JOIN APP_APPLICATION A ON P.APPLICATION_ID = A.OBJECT_ID
            WHERE P.OBJECT_ID = HEXTORAW(?)
        ", [$this->pageId]);

        if (!$page) {
            abort(404, 'Page not found');
        }

        $normalized = $this->normalizeRow($page);
        
        $detailConfig = [];
        if (!empty($normalized['DETAIL_CONFIG'])) {
            $decoded = json_decode((string) $normalized['DETAIL_CONFIG'], true);
            if (is_array($decoded)) {
                $detailConfig = $decoded;

                $pageCodes = array_column($detailConfig, 'pageCode');
                if (!empty($pageCodes)) {
                    $placeholders = implode(',', array_fill(0, count($pageCodes), '?'));
                    $ids = $this->normalizeRows(DB::select("SELECT OBJECT_CODE, RAWTOHEX(OBJECT_ID) AS ID FROM UI_PAGE WHERE OBJECT_CODE IN ($placeholders)", $pageCodes));
                    $idMap = [];
                    foreach ($ids as $r) {
                        $idMap[$r['OBJECT_CODE']] = $r['ID'];
                    }
                    foreach ($detailConfig as &$c) {
                        $c['pageId'] = $idMap[$c['pageCode']] ?? null;
                    }
                }
            }
        }
        $normalized['DETAIL_CONFIG_ARRAY'] = $detailConfig;

        $this->page = $normalized;
    }

    private function loadFields(): void
    {
        $fields = DB::select("
            SELECT
                RAWTOHEX(F.OBJECT_ID) AS ID,
                F.OBJECT_CODE,
                F.OBJECT_NAME,
                F.COLUMN_NAME,
                F.LABEL,
                F.FIELD_TYPE,
                F.DATA_TYPE,
                F.DISPLAY_ORDER,
                F.REQUIRED_FLAG,
                F.READONLY_FLAG,
                F.VISIBLE_FLAG,
                F.DEFAULT_VALUE,
                F.FIELD_CONFIG,
                RAWTOHEX(F.LOV_ID) AS LOV_ID,
                L.OBJECT_CODE AS LOV_CODE,
                L.OBJECT_NAME AS LOV_NAME
            FROM UI_FIELD F
            LEFT JOIN DS_LOV L ON F.LOV_ID = L.OBJECT_ID
            WHERE F.PAGE_ID = HEXTORAW(?)
            ORDER BY F.DISPLAY_ORDER, F.COLUMN_NAME
        ", [$this->pageId]);

        $this->fields = $this->normalizeRows($fields);
    }

    private function loadAvailableLovs(): void
    {
        // Pastikan kolom APPLICATION_ID ada di DS_LOV (update skema on the fly)
        try {
            DB::statement("ALTER TABLE DS_LOV ADD (APPLICATION_ID RAW(16))");
            DB::statement("ALTER TABLE DS_LOV ADD CONSTRAINT FK_DS_LOV_APP FOREIGN KEY (APPLICATION_ID) REFERENCES APP_APPLICATION(OBJECT_ID) ON DELETE CASCADE");
        } catch (\Throwable $e) {
            // Abaikan jika kolom atau constraint sudah ada
        }

        // Tampilkan LOV yang global (APPLICATION_ID IS NULL) atau milik aplikasi ini.
        $lovs = DB::select("
            SELECT
                RAWTOHEX(OBJECT_ID) AS ID,
                OBJECT_CODE,
                OBJECT_NAME,
                LOV_TYPE
            FROM DS_LOV
            WHERE APPLICATION_ID = HEXTORAW(?) OR APPLICATION_ID IS NULL
            ORDER BY OBJECT_NAME
        ", [$this->page['APPLICATION_ID'] ?? null]);

        $this->availableLovs = $this->normalizeRows($lovs);
    }

    public function selectField(?string $fieldId): void
    {
        $this->selectedFieldId = $fieldId;

        if ($fieldId === null) {
            $this->resetFieldEditor();
            return;
        }

        $field = collect($this->fields)->firstWhere('ID', $fieldId);

        if (!$field) {
            return;
        }

        $config = [];
        if (!empty($field['FIELD_CONFIG'])) {
            $decoded = json_decode((string) $field['FIELD_CONFIG'], true);
            $config = is_array($decoded) ? $decoded : [];
        }

        $this->fieldEditor = [
            'label' => $field['LABEL'] ?? '',
            'field_type' => $field['FIELD_TYPE'] ?? 'TEXT',
            'required_flag' => (int) ($field['REQUIRED_FLAG'] ?? 0),
            'readonly_flag' => (int) ($field['READONLY_FLAG'] ?? 0),
            'visible_flag' => (int) ($field['VISIBLE_FLAG'] ?? 1),
            'lov_id' => $field['LOV_ID'],
            'default_value' => $field['DEFAULT_VALUE'],
            'date_default' => strtoupper((string) ($config['default'] ?? '')) === 'SYSDATE' ? 'SYSDATE' : '',
            'date_with_time' => (int) (bool) ($config['withTime'] ?? false),
            'num_decimals' => array_key_exists('decimals', $config) && $config['decimals'] !== null ? (string) $config['decimals'] : '',
            'num_thousands' => (int) (bool) ($config['thousandsSep'] ?? false),
            'calculation' => (string) ($config['calculation'] ?? ''),
        ];
    }

    public function updateField(): void
    {
        if (!$this->selectedFieldId) {
            session()->flash('error', 'No field selected');
            return;
        }

        try {
            $hasLov = !empty($this->fieldEditor['lov_id']);
            $fieldConfig = $this->buildFieldConfigJson($this->fieldEditor);

            $sql = "
                UPDATE UI_FIELD
                SET
                    LABEL = ?,
                    FIELD_TYPE = ?,
                    REQUIRED_FLAG = ?,
                    READONLY_FLAG = ?,
                    VISIBLE_FLAG = ?,
                    DEFAULT_VALUE = ?,
                    FIELD_CONFIG = ?,
                    LOV_ID = " . ($hasLov ? 'HEXTORAW(?)' : 'NULL') . ",
                    UPDATED_AT = SYSTIMESTAMP
                WHERE OBJECT_ID = HEXTORAW(?)
            ";

            $params = [
                $this->fieldEditor['label'],
                $this->fieldEditor['field_type'],
                (int) $this->fieldEditor['required_flag'],
                (int) $this->fieldEditor['readonly_flag'],
                (int) $this->fieldEditor['visible_flag'],
                $this->fieldEditor['default_value'],
                $fieldConfig,
            ];

            if ($hasLov) {
                $params[] = $this->fieldEditor['lov_id'];
            }
            $params[] = $this->selectedFieldId;

            DB::update($sql, $params);

            $this->loadFields();
            session()->flash('success', 'Field updated successfully');

            $this->dispatch('fields-changed');
        } catch (\Throwable $e) {
            session()->flash('error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * @param  array<int, string>  $order
     */
    public function reorderFields(array $order): void
    {
        try {
            DB::beginTransaction();

            foreach ($order as $index => $fieldId) {
                DB::update("
                    UPDATE UI_FIELD
                    SET DISPLAY_ORDER = ?,
                        UPDATED_AT = SYSTIMESTAMP
                    WHERE OBJECT_ID = HEXTORAW(?)
                ", [($index + 1) * 10, $fieldId]);
            }

            DB::commit();
            $this->loadFields();

            $this->dispatch('fields-changed');
            session()->flash('success', 'Urutan field disimpan');
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Reorder failed: ' . $e->getMessage());
        }
    }

    public function deleteField(string $fieldId): void
    {
        try {
            // Hapus rule validasi terkait dulu agar tidak melanggar FK.
            DB::delete("DELETE FROM VAL_RULE WHERE FIELD_ID = HEXTORAW(?)", [$fieldId]);
            DB::delete("DELETE FROM UI_FIELD WHERE OBJECT_ID = HEXTORAW(?)", [$fieldId]);

            $this->loadFields();
            $this->selectedFieldId = null;
            $this->resetFieldEditor();

            $this->dispatch('fields-changed');
            session()->flash('success', 'Field deleted successfully');
        } catch (\Throwable $e) {
            session()->flash('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    /**
     * Buka dialog tambah field dengan tipe terpilih dari palette.
     */
    public function openAddModal(string $fieldType): void
    {
        $this->resetErrorBag();
        $this->newField = [
            'label' => '',
            'field_type' => $fieldType,
            'length' => $this->defaultLength($fieldType),
            'add_column' => true,
        ];
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->reset(['newField']);
    }

    public function openAddTabModal(): void
    {
        $this->resetValidation();
        $this->newTab = [
            'title' => '',
            'suffix' => '',
        ];
        $this->showAddTabModal = true;
    }

    public function closeAddTabModal(): void
    {
        $this->showAddTabModal = false;
    }

    public function addTab(MetadataScaffolder $scaffolder): void
    {
        $this->validate([
            'newTab.title' => 'required|string',
            'newTab.suffix' => 'required|string|regex:/^[A-Za-z0-9_]+$/',
        ]);

        $appCode = $this->page['APPLICATION_CODE'];
        $headerTable = $this->page['TABLE_NAME']; // e.g. NEXUS_T_CUSTOMER
        if (empty($headerTable)) {
            session()->flash('error', 'Halaman ini tidak memiliki tabel sumber (TABLE_NAME kosong).');
            return;
        }

        $prefix = strtoupper($appCode) . '_T_';
        if (str_starts_with(strtoupper($headerTable), $prefix)) {
            $headerEntity = substr(strtoupper($headerTable), strlen($prefix));
        } else {
            // fallback if table does not start with APP_T_
            $headerEntity = preg_replace('/^.*?_T_/', '', strtoupper($headerTable));
        }

        $detailSuffix = strtoupper(trim($this->newTab['suffix']));
        $detailEntity = $headerEntity . '_' . $detailSuffix;

        try {
            $scaffolder->createHeaderDetail([
                'headerName' => $headerEntity,
                'detailName' => $detailEntity,
                'appCode' => $appCode,
                'detailLabel' => $this->newTab['title'],
            ]);

            session()->flash('success', "Tab detail dan tabel {$appCode}_T_{$detailEntity} berhasil ditambahkan");
            $this->closeAddTabModal();
            $this->loadPage();
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal menambahkan tab detail: ' . $e->getMessage());
        }
    }

    /**
     * Preview nama kolom terstandarisasi dari label yang sedang diketik.
     */
    #[Computed]
    public function columnNamePreview(): string
    {
        return $this->standardizeColumnName((string) ($this->newField['label'] ?? ''));
    }

    /**
     * Tambah field baru + (opsional) buat kolom fisik di tabel dataset.
     *
     * Catatan: DDL Oracle (ALTER TABLE) auto-commit, sehingga tidak dibungkus
     * transaksi. Urutan: validasi -> ALTER (bila perlu) -> INSERT UI_FIELD.
     */
    public function addField(): void
    {
        $this->validate([
            'newField.label' => 'required|string|max:100',
            'newField.field_type' => 'required|string',
        ], [], [
            'newField.label' => 'Label field',
            'newField.field_type' => 'tipe field',
        ]);

        $label = trim((string) $this->newField['label']);
        $columnName = $this->standardizeColumnName($label);

        if ($columnName === '') {
            $this->addError('newField.label', 'Label tidak dapat dijadikan nama kolom yang valid.');
            return;
        }

        $fieldType = (string) $this->newField['field_type'];
        $sourceType = strtoupper((string) ($this->page['SOURCE_TYPE'] ?? ''));
        $tableName = (string) ($this->page['TABLE_NAME'] ?? '');
        $addColumn = (bool) ($this->newField['add_column'] ?? false);

        // Cegah duplikat COLUMN_NAME dalam page ini.
        $dupe = DB::selectOne(
            "SELECT 1 AS X FROM UI_FIELD WHERE PAGE_ID = HEXTORAW(?) AND UPPER(COLUMN_NAME) = ?",
            [$this->pageId, $columnName]
        );
        if ($dupe) {
            $this->addError('newField.label', "Field dengan kolom {$columnName} sudah ada di form ini.");
            return;
        }

        try {
            $columnAdded = false;
            $columnExisted = false;

            // Tambah kolom fisik bila dataset berbasis TABLE dan diminta.
            if ($sourceType === 'TABLE' && $tableName !== '') {
                if (!$this->isValidIdentifier($tableName)) {
                    throw new \RuntimeException("Nama tabel tidak valid: {$tableName}");
                }

                $columnExisted = $this->columnExists($tableName, $columnName);

                if ($addColumn && !$columnExisted) {
                    if (!$this->isValidIdentifier($columnName)) {
                        throw new \RuntimeException("Nama kolom tidak valid: {$columnName}");
                    }
                    $ddlType = $this->oracleColumnType($fieldType, (int) ($this->newField['length'] ?? 255));
                    // ALTER TABLE auto-commit di Oracle.
                    DB::statement("ALTER TABLE {$tableName} ADD ({$columnName} {$ddlType})");
                    $columnAdded = true;
                }
            }

            $objectCode = $this->buildFieldCode($columnName);

            $maxOrder = DB::selectOne(
                "SELECT COALESCE(MAX(DISPLAY_ORDER), 0) AS MAX_ORDER FROM UI_FIELD WHERE PAGE_ID = HEXTORAW(?)",
                [$this->pageId]
            );
            $nextOrder = ((int) ($this->normalizeRow($maxOrder)['MAX_ORDER'] ?? 0)) + 10;

            DB::insert("
                INSERT INTO UI_FIELD (
                    OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME, LABEL, COLUMN_NAME,
                    FIELD_TYPE, DATA_TYPE, DISPLAY_ORDER,
                    REQUIRED_FLAG, VISIBLE_FLAG, READONLY_FLAG, STATUS
                ) VALUES (
                    SYS_GUID(), HEXTORAW(?), ?, ?, ?, ?,
                    ?, ?, ?,
                    0, 1, 0, 'PUBLISHED'
                )
            ", [
                $this->pageId,
                $objectCode,
                $label,
                $label,
                $columnName,
                $fieldType,
                $this->inferDataType($fieldType),
                $nextOrder,
            ]);

            $this->loadFields();
            $this->showAddModal = false;
            $this->dispatch('fields-changed');

            $msg = "Field \"{$label}\" ditambahkan (kolom {$columnName}).";
            if ($columnAdded) {
                $msg .= " Kolom baru dibuat di tabel {$tableName}.";
            } elseif ($columnExisted) {
                $msg .= " Menggunakan kolom yang sudah ada di tabel.";
            } elseif ($sourceType !== 'TABLE') {
                $msg .= " (Dataset non-TABLE: kolom fisik tidak dibuat otomatis.)";
            }
            $msg .= ' Klik "Compile & Activate" agar tampil di runtime.';
            session()->flash('success', $msg);
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal menambah field: ' . $e->getMessage());
        }
    }

    public function setPreviewMode(string $mode): void
    {
        $this->previewMode = $mode;
    }

    private function resetFieldEditor(): void
    {
        $this->fieldEditor = [
            'label' => '',
            'field_type' => 'TEXT',
            'required_flag' => 0,
            'readonly_flag' => 0,
            'visible_flag' => 1,
            'lov_id' => null,
            'default_value' => null,
            'date_default' => '',
            'date_with_time' => 0,
            'num_decimals' => '',
            'num_thousands' => 0,
            'calculation' => '',
        ];
    }

    /**
     * Bangun JSON FIELD_CONFIG dari state editor sesuai tipe field.
     * Mengembalikan null bila tidak ada opsi (agar kolom tetap bersih).
     *
     * @param  array<string, mixed>  $editor
     */
    private function buildFieldConfigJson(array $editor): ?string
    {
        $type = strtoupper((string) ($editor['field_type'] ?? ''));
        $config = [];

        if (in_array($type, ['DATE', 'DATETIME'], true)) {
            if (strtoupper((string) ($editor['date_default'] ?? '')) === 'SYSDATE') {
                $config['default'] = 'SYSDATE';
            }
            $config['withTime'] = (bool) ($editor['date_with_time'] ?? false);
        }

        if (in_array($type, ['NUMBER', 'INTEGER', 'DECIMAL'], true)) {
            $dec = $editor['num_decimals'] ?? '';
            if ($dec !== '' && $dec !== null && is_numeric($dec)) {
                $config['decimals'] = (int) $dec;
            }
            $config['thousandsSep'] = (bool) ($editor['num_thousands'] ?? false);
            
            $calc = trim((string) ($editor['calculation'] ?? ''));
            if ($calc !== '') {
                $config['calculation'] = $calc;
            }
        }

        return $config === [] ? null : (string) json_encode($config);
    }

    private function inferDataType(string $fieldType): string
    {
        return match ($fieldType) {
            'NUMBER', 'INTEGER', 'DECIMAL' => 'NUMBER',
            // DATE & DATETIME sama-sama disimpan sebagai kolom DATE Oracle
            // (DATE menyimpan tanggal + jam, tanpa timezone).
            'DATE', 'DATETIME' => 'DATE',
            'CHECKBOX' => 'NUMBER',
            default => 'VARCHAR2',
        };
    }

    /**
     * Standarisasi label menjadi nama kolom Oracle yang valid:
     *  - huruf besar; spasi & karakter khusus -> "_"; underscore ganda dirapikan;
     *  - harus diawali huruf; maksimal 30 karakter.
     */
    private function standardizeColumnName(string $label): string
    {
        $s = trim($label);
        if ($s === '') {
            return '';
        }

        // Buang aksen umum (é -> e, dsb.) bila memungkinkan.
        $translit = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        if ($translit !== false) {
            $s = $translit;
        }

        $s = strtoupper($s);
        $s = preg_replace('/[^A-Z0-9]+/', '_', $s) ?? '';
        $s = trim($s, '_');

        if ($s === '') {
            return '';
        }

        // Oracle: identifier harus diawali huruf.
        if (!preg_match('/^[A-Z]/', $s)) {
            $s = 'C_' . $s;
        }

        // Batas aman 128 karakter (Oracle 12.2+).
        if (strlen($s) > 128) {
            $s = rtrim(substr($s, 0, 128), '_');
        }

        return $s;
    }

    private function isValidIdentifier(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9_$#]{0,127}$/', $name);
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT 1 AS X FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?",
            [strtoupper($table), strtoupper($column)]
        );

        return $row !== null;
    }

    /**
     * Peta tipe field UI -> tipe kolom Oracle untuk ALTER TABLE ADD.
     */
    private function oracleColumnType(string $fieldType, int $length = 255): string
    {
        $len = max(1, min($length, 4000));

        return match ($fieldType) {
            'CHECKBOX' => 'NUMBER(1) DEFAULT 0',
            'NUMBER', 'INTEGER' => 'NUMBER(38)',
            'DECIMAL' => 'NUMBER(18,2)',
            // DATE & DATETIME -> kolom DATE Oracle (tanggal + jam, tanpa timezone).
            'DATE', 'DATETIME' => 'DATE',
            'TEXTAREA' => 'VARCHAR2(4000 CHAR)',
            default => "VARCHAR2({$len} CHAR)",
        };
    }

    private function defaultLength(string $fieldType): int
    {
        return match ($fieldType) {
            'EMAIL' => 150,
            'PASSWORD' => 100,
            'TEXTAREA' => 4000,
            default => 255,
        };
    }

    /**
     * OBJECT_CODE unik: FLD_<PAGECODE>_<COLUMN>, dipotong <= 100 char.
     */
    private function buildFieldCode(string $columnName): string
    {
        $pageCode = (string) ($this->page['OBJECT_CODE'] ?? 'PAGE');
        $code = 'FLD_' . $pageCode . '_' . $columnName;

        if (strlen($code) > 100) {
            $code = substr($code, 0, 100);
        }

        return $code;
    }

    /**
     * @return array<string, string>
     */
    private function getFieldTypes(): array
    {
        return [
            'TEXT' => 'Text',
            'EMAIL' => 'Email',
            'NUMBER' => 'Number',
            'INTEGER' => 'Integer',
            'DECIMAL' => 'Decimal',
            'DATE' => 'Date',
            'DATETIME' => 'Date & Time',
            'CHECKBOX' => 'Checkbox',
            'TEXTAREA' => 'Text Area',
            'SELECT' => 'Dropdown',
            'LOV' => 'List of Values',
            'PASSWORD' => 'Password',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getFieldWidgets(): array
    {
        return [
            'text' => ['icon' => 'M4 6h16M4 12h16M4 18h16', 'label' => 'Text', 'type' => 'TEXT'],
            'email' => ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'label' => 'Email', 'type' => 'EMAIL'],
            'number' => ['icon' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14', 'label' => 'Number', 'type' => 'NUMBER'],
            'date' => ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label' => 'Date', 'type' => 'DATE'],
            'checkbox' => ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Checkbox', 'type' => 'CHECKBOX'],
            'textarea' => ['icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', 'label' => 'Text Area', 'type' => 'TEXTAREA'],
            'select' => ['icon' => 'M19 9l-7 7-7-7', 'label' => 'Dropdown', 'type' => 'SELECT'],
        ];
    }
}
