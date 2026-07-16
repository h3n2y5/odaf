<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Odaf\Studio\TableIntrospector;

#[Layout('layouts.odaf')]
final class AppWizard extends Component
{
    use NormalizesRows;

    public int $step = 1;

    // Step 1: App Info
    public string $appName = '';
    public string $appCode = '';

    // Step 2: Data Source
    public bool $isNewTable = true;
    public string $tableName = '';
    public string $pageTitle = '';
    public string $pageCode = '';

    // Step 3: Admin User
    public string $adminUsername = '';
    public string $adminName = '';
    public string $adminEmail = '';
    public string $adminPassword = '';

    public function mount(StudioAccess $access): void
    {
        $access->ensureAdmin();
    }

    #[\Livewire\Attributes\Computed]
    public function availableTables(): array
    {
        $tables = DB::select("
            SELECT TABLE_NAME AS TABLE_NAME
            FROM USER_TABLES
            WHERE TABLE_NAME NOT LIKE 'RT_%'
            ORDER BY TABLE_NAME
        ");
        return $this->normalizeRows($tables);
    }

    public function updatedAppName(): void
    {
        if (trim($this->appName) !== '' && trim($this->appCode) === '') {
            $this->appCode = 'APP_' . strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '_', trim($this->appName)));
        }
        if (trim($this->appName) !== '' && trim($this->pageTitle) === '') {
            $this->pageTitle = 'Master ' . $this->appName;
        }
        if (trim($this->appName) !== '' && trim($this->adminName) === '') {
            $this->adminName = 'Admin ' . $this->appName;
        }
    }

    public function updatedTableName(): void
    {
        $this->tableName = strtoupper(preg_replace('/[^A-Za-z0-9_]+/', '_', trim($this->tableName)));
        if (trim($this->tableName) !== '' && trim($this->pageCode) === '') {
            $this->pageCode = 'PAGE_' . strtoupper($this->tableName);
        }
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'appName' => 'required|max:200',
                'appCode' => 'required|max:100|unique:APP_APPLICATION,OBJECT_CODE',
            ]);
        } elseif ($this->step === 2) {
            $this->validate([
                'tableName' => 'required|regex:/^[A-Z][A-Z0-9_]*$/|max:30',
                'pageTitle' => 'required|max:200',
                'pageCode' => 'required|max:100', 
            ]);

            $exists = DB::selectOne("SELECT 1 AS X FROM USER_TABLES WHERE TABLE_NAME = ?", [strtoupper($this->tableName)]);
            if ($this->isNewTable && $exists) {
                $this->addError('tableName', 'Tabel dengan nama ini sudah ada di database.');
                return;
            } elseif (!$this->isNewTable && !$exists) {
                $this->addError('tableName', 'Tabel tidak ditemukan di database.');
                return;
            }
        } elseif ($this->step === 3) {
            $this->validate([
                'adminUsername' => 'required|max:100|unique:SEC_USER,OBJECT_CODE',
                'adminName' => 'required|max:200',
                'adminEmail' => 'nullable|email|max:200',
                'adminPassword' => 'required|min:6',
            ]);
        }
        $this->step++;
    }

    public function prevStep(): void
    {
        $this->step--;
    }

    public function generateApp(TableIntrospector $introspector): void
    {
        $this->validate([
            'appName' => 'required|max:200',
            'appCode' => 'required|max:100',
            'tableName' => 'required',
            'pageTitle' => 'required|max:200',
            'pageCode' => 'required|max:100',
            'adminUsername' => 'required|max:100|unique:SEC_USER,OBJECT_CODE',
            'adminName' => 'required|max:200',
            'adminEmail' => 'nullable|email|max:200',
            'adminPassword' => 'required|min:6',
        ]);

        try {
            DB::beginTransaction();

            $appId = strtoupper(bin2hex(random_bytes(16)));
            $moduleId = strtoupper(bin2hex(random_bytes(16)));
            $menuId = strtoupper(bin2hex(random_bytes(16)));
            $datasetId = strtoupper(bin2hex(random_bytes(16)));
            $pageId = strtoupper(bin2hex(random_bytes(16)));
            
            $safeTableName = strtoupper($this->tableName);

            // 0. Create Table if new
            if ($this->isNewTable) {
                DB::statement("
                    CREATE TABLE {$safeTableName} (
                        ID RAW(16) DEFAULT SYS_GUID() NOT NULL,
                        CREATED_AT TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
                        UPDATED_AT TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
                        CREATED_BY RAW(16),
                        UPDATED_BY RAW(16),
                        VERSION_NO NUMBER(10) DEFAULT 1 NOT NULL,
                        CONSTRAINT PK_{$safeTableName} PRIMARY KEY (ID)
                    )
                ");
            }

            // 1. Create App (DRAFT)
            DB::insert("
                INSERT INTO APP_APPLICATION (
                    OBJECT_ID, OBJECT_CODE, OBJECT_NAME, STATUS
                ) VALUES (
                    HEXTORAW(?), ?, ?, 'DRAFT'
                )
            ", [$appId, $this->appCode, $this->appName]);

            // 2. Create Default Module
            DB::insert("
                INSERT INTO APP_MODULE (
                    OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, STATUS
                ) VALUES (
                    HEXTORAW(?), HEXTORAW(?), ?, ?, 'PUBLISHED'
                )
            ", [$moduleId, $appId, 'MOD_MAIN', 'Main Module']);

            // Find Primary Key
            $primaryKeyColumn = 'ID'; // Default for new table
            
            if (!$this->isNewTable && $introspector->tableExists($this->tableName)) {
                $schema = $introspector->schema($this->tableName);
                if (!empty($schema['primaryKey'])) {
                    $primaryKeyColumn = $schema['primaryKey'][0];
                } else {
                    // Fallback to first column or OBJECT_ID
                    $firstCol = array_key_first($schema['columns'] ?? []);
                    $primaryKeyColumn = $firstCol ?: 'OBJECT_ID';
                }
            }

            // 3. Create Dataset
            $datasetCode = 'DS_' . strtoupper($this->tableName);
            DB::insert("
                INSERT INTO DS_DATASET (
                    OBJECT_ID, OBJECT_CODE, OBJECT_NAME, SOURCE_TYPE, SOURCE_OBJECT, PRIMARY_KEY_COLUMN, STATUS
                ) VALUES (
                    HEXTORAW(?), ?, ?, 'TABLE', ?, ?, 'PUBLISHED'
                )
            ", [$datasetId, $datasetCode, $this->tableName . ' Dataset', $this->tableName, $primaryKeyColumn]);

            // 4. Create Page
            DB::insert("
                INSERT INTO UI_PAGE (
                    OBJECT_ID, APPLICATION_ID, DATASET_ID, OBJECT_CODE, OBJECT_NAME, TITLE, PAGE_TYPE, STATUS
                ) VALUES (
                    HEXTORAW(?), HEXTORAW(?), HEXTORAW(?), ?, ?, ?, 'GRID', 'PUBLISHED'
                )
            ", [$pageId, $appId, $datasetId, $this->pageCode, $this->pageTitle, $this->pageTitle]);

            // 5. Create Menu linking to Page
            DB::insert("
                INSERT INTO APP_MENU (
                    OBJECT_ID, MODULE_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME, ICON, STATUS
                ) VALUES (
                    HEXTORAW(?), HEXTORAW(?), HEXTORAW(?), ?, ?, 'M4 6h16M4 12h16M4 18h16', 'PUBLISHED'
                )
            ", [$menuId, $moduleId, $pageId, 'MNU_' . $this->pageCode, $this->pageTitle]);

            // 6. Introspect Table and Auto-Generate UI_FIELD
            if ($introspector->tableExists($this->tableName)) {
                $schema = $introspector->schema($this->tableName);
                $order = 10;
                foreach ($schema['columns'] as $colName => $meta) {
                    // Skip technical columns
                    if (in_array(strtoupper($colName), ['CREATED_AT', 'CREATED_BY', 'UPDATED_AT', 'UPDATED_BY', 'DELETED_AT', 'DELETED_BY', 'VERSION_NO'])) {
                        continue;
                    }
                    
                    $fieldId = strtoupper(bin2hex(random_bytes(16)));
                    $fieldCode = 'FLD_' . $this->pageCode . '_' . $colName;
                    if (strlen($fieldCode) > 100) {
                        $fieldCode = substr($fieldCode, 0, 100);
                    }
                    
                    $label = ucwords(strtolower(str_replace('_', ' ', $colName)));
                    
                    // Infer Field Type
                    $type = (string) $meta['dataType'];
                    $fieldType = 'TEXT';
                    if (in_array($type, ['NUMBER', 'INTEGER', 'DECIMAL'], true)) {
                        $fieldType = 'NUMBER';
                    } elseif (in_array($type, ['DATE'], true)) {
                        $fieldType = 'DATE';
                    } elseif (str_starts_with($type, 'TIMESTAMP')) {
                        $fieldType = 'DATETIME';
                    } elseif (in_array($type, ['CLOB', 'NCLOB', 'LONG'], true)) {
                        $fieldType = 'TEXTAREA';
                    }
                    
                    $dataType = 'VARCHAR2';
                    if ($fieldType === 'NUMBER') {
                        $dataType = 'NUMBER';
                    } elseif (in_array($fieldType, ['DATE', 'DATETIME'], true)) {
                        $dataType = 'DATE';
                    }

                    $isPk = (bool) ($meta['isPk'] ?? false);
                    $isBinary = (bool) ($meta['isBinary'] ?? false);

                    // If it is binary PK, typically we hide it.
                    $visibleFlag = ($isPk && $isBinary) ? 0 : 1;
                    $readonlyFlag = ($isPk) ? 1 : 0;
                    
                    DB::insert("
                        INSERT INTO UI_FIELD (
                            OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME, LABEL, COLUMN_NAME,
                            FIELD_TYPE, DATA_TYPE, DISPLAY_ORDER, REQUIRED_FLAG, VISIBLE_FLAG, READONLY_FLAG, STATUS
                        ) VALUES (
                            HEXTORAW(?), HEXTORAW(?), ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 'PUBLISHED'
                        )
                    ", [
                        $fieldId,
                        $pageId,
                        $fieldCode,
                        $label,
                        $label,
                        $colName,
                        $fieldType,
                        $dataType,
                        $order,
                        $visibleFlag,
                        $readonlyFlag,
                    ]);
                    $order += 10;
                }
            }

            // 7. Create Admin Role & User
            $roleId = strtoupper(bin2hex(random_bytes(16)));
            $userId = strtoupper(bin2hex(random_bytes(16)));
            $roleCode = 'ROLE_ADMIN_' . $this->appCode;

            DB::insert("
                INSERT INTO SEC_ROLE (
                    OBJECT_ID, OBJECT_CODE, OBJECT_NAME, STATUS
                ) VALUES (
                    HEXTORAW(?), ?, ?, 'PUBLISHED'
                )
            ", [$roleId, $roleCode, 'Admin - ' . $this->appName]);

            DB::insert("
                INSERT INTO SEC_USER (
                    OBJECT_ID, OBJECT_CODE, OBJECT_NAME, EMAIL, PASSWORD_HASH, STATUS
                ) VALUES (
                    HEXTORAW(?), ?, ?, ?, ?, 'PUBLISHED'
                )
            ", [
                $userId,
                $this->adminUsername,
                $this->adminName,
                $this->adminEmail ?: null,
                \Illuminate\Support\Facades\Hash::make($this->adminPassword)
            ]);

            DB::insert("
                INSERT INTO SEC_USER_ROLE (
                    USER_ID, ROLE_ID
                ) VALUES (
                    HEXTORAW(?), HEXTORAW(?)
                )
            ", [$userId, $roleId]);

            DB::commit();

            // 8. Compile application
            $exit = \Artisan::call('odaf:compile', [
                'application' => $this->appCode,
                '--activate' => true,
            ]);

            if ($exit === 0) {
                session()->flash('success', "Aplikasi {$this->appName} berhasil dibuat dan diaktifkan!");
                $this->redirect("/app/{$this->appCode}", navigate: true);
            } else {
                $output = trim(\Artisan::output());
                session()->flash('error', "Aplikasi berhasil dibuat, tetapi kompilasi gagal: " . $output);
                $this->redirect("/studio/designer", navigate: true);
            }

        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal membuat aplikasi: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.studio.designer.app-wizard');
    }
}
