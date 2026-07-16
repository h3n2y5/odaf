<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Odaf\Studio\TableIntrospector;

/**
 * Application Dashboard - Visual entry point untuk ODAF Studio Designer (F3).
 * 
 * Menampilkan applications sebagai cards dengan stats & quick actions,
 * menggantikan tampilan table-based di Studio Data Manager.
 */
#[Layout('layouts.odaf')]
final class ApplicationDashboard extends Component
{
    use NormalizesRows, WithFileUploads;

    /** @var array<int, array<string, mixed>> */
    public array $applications = [];

    public $backupFile;
    public bool $showImportModal = false;

    public function mount(StudioAccess $access): void
    {
        $access->ensureAdmin();
        
        // Load applications dengan stats
        $this->loadApplications();
    }

    public function render()
    {
        return view('livewire.studio.designer.application-dashboard', [
            'applications' => $this->applications,
        ]);
    }

    private function loadApplications(): void
    {
        // Query applications dengan stats (simplified - no module/menu counts if tables don't exist)
        $apps = DB::select("
            SELECT 
                RAWTOHEX(APP.OBJECT_ID) AS ID,
                APP.OBJECT_CODE,
                APP.OBJECT_NAME,
                APP.DESCRIPTION,
                APP.STATUS,
                APP.CREATED_AT,
                APP.UPDATED_AT,
                (SELECT COUNT(*) 
                 FROM APP_MODULE M 
                 WHERE M.APPLICATION_ID = APP.OBJECT_ID) AS MODULE_COUNT,
                (SELECT COUNT(*) 
                 FROM APP_MENU MN 
                 WHERE MN.MODULE_ID IN (
                     SELECT M.OBJECT_ID FROM APP_MODULE M WHERE M.APPLICATION_ID = APP.OBJECT_ID
                 )) AS MENU_COUNT,
                (SELECT COUNT(DISTINCT P.OBJECT_ID) 
                 FROM UI_PAGE P 
                 WHERE P.APPLICATION_ID = APP.OBJECT_ID) AS PAGE_COUNT,
                (SELECT COUNT(*) 
                 FROM UI_FIELD F 
                 JOIN UI_PAGE P ON F.PAGE_ID = P.OBJECT_ID 
                 WHERE P.APPLICATION_ID = APP.OBJECT_ID) AS FIELD_COUNT,
                (SELECT COUNT(*) 
                 FROM DS_LOV L 
                 WHERE L.OBJECT_ID IN (
                     SELECT F.LOV_ID FROM UI_FIELD F 
                     JOIN UI_PAGE P ON F.PAGE_ID = P.OBJECT_ID 
                     WHERE P.APPLICATION_ID = APP.OBJECT_ID AND F.LOV_ID IS NOT NULL
                 )) AS LOV_COUNT,
                (SELECT PKG.PACKAGE_VERSION 
                 FROM RT_PACKAGE PKG 
                 WHERE PKG.APPLICATION_ID = APP.OBJECT_ID 
                 AND PKG.ACTIVE_FLAG = 1 
                 FETCH FIRST 1 ROWS ONLY) AS ACTIVE_VERSION,
                (SELECT RAWTOHEX(P2.OBJECT_ID) 
                 FROM UI_PAGE P2 
                 WHERE P2.APPLICATION_ID = APP.OBJECT_ID 
                 ORDER BY P2.OBJECT_CODE 
                 FETCH FIRST 1 ROWS ONLY) AS FIRST_PAGE_ID
            FROM APP_APPLICATION APP
            ORDER BY APP.OBJECT_NAME
        ");

        $this->applications = $this->normalizeRows($apps);
    }

    public function createApplication(): void
    {
        // Redirect ke wizard (akan dibuat nanti)
        $this->redirect('/studio/designer/app/new', navigate: true);
    }

    public function openApp(string $appId): void
    {
        // Get first page of the app to open form builder
        $firstPage = DB::selectOne("
            SELECT RAWTOHEX(P.OBJECT_ID) AS PAGE_ID
            FROM UI_PAGE P
            WHERE P.APPLICATION_ID = HEXTORAW(?)
            ORDER BY P.OBJECT_CODE
            FETCH FIRST 1 ROWS ONLY
        ", [$appId]);

        if ($firstPage) {
            $pageId = $this->normalizeRow($firstPage)['PAGE_ID'];
            $this->redirect("/studio/designer/form/{$pageId}", navigate: true);
        } else {
            // No pages yet - redirect to app overview (stub for now)
            $this->redirect("/studio/designer/app/{$appId}", navigate: true);
        }
    }

    public function compileApp(string $appId): void
    {
        try {
            // Get app code from ID
            $row = DB::selectOne("SELECT OBJECT_CODE FROM APP_APPLICATION WHERE OBJECT_ID = HEXTORAW(?)", [$appId]);
            
            if (!$row) {
                session()->flash('error', 'Application not found.');
                return;
            }

            $appCode = $this->normalizeRow($row)['OBJECT_CODE'];

            // Compile via artisan command
            \Artisan::call('odaf:compile', [
                'application' => $appCode,
                '--activate' => true,
            ]);

            session()->flash('success', "Application {$appCode} compiled successfully!");
            $this->loadApplications(); // Reload untuk update stats
        } catch (\Throwable $e) {
            session()->flash('error', 'Compilation failed: ' . $e->getMessage());
        }
    }

    public function deleteApp(string $appId): void
    {
        try {
            DB::beginTransaction();

            $app = DB::selectOne("SELECT OBJECT_CODE, OBJECT_NAME FROM APP_APPLICATION WHERE OBJECT_ID = HEXTORAW(?)", [$appId]);
            if (!$app) {
                throw new \Exception("Aplikasi tidak ditemukan.");
            }
            $appCode = $this->normalizeRow($app)['OBJECT_CODE'];
            
            // get pages
            $pages = DB::select("SELECT RAWTOHEX(OBJECT_ID) AS ID, RAWTOHEX(DATASET_ID) AS DS_ID FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?)", [$appId]);
            
            // Delete all menus related to this app's modules
            DB::delete("
                DELETE FROM APP_MENU WHERE MODULE_ID IN (
                    SELECT OBJECT_ID FROM APP_MODULE WHERE APPLICATION_ID = HEXTORAW(?)
                )
            ", [$appId]);

            foreach ($pages as $page) {
                $page = $this->normalizeRow($page);
                DB::delete("DELETE FROM VAL_RULE WHERE FIELD_ID IN (SELECT OBJECT_ID FROM UI_FIELD WHERE PAGE_ID = HEXTORAW(?))", [$page['ID']]);
                DB::delete("DELETE FROM UI_FIELD WHERE PAGE_ID = HEXTORAW(?)", [$page['ID']]);
            }
            DB::delete("DELETE FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?)", [$appId]);
            
            foreach ($pages as $page) {
                $page = $this->normalizeRow($page);
                if (!empty($page['DS_ID'])) {
                    $ds = DB::selectOne("SELECT SOURCE_OBJECT FROM DS_DATASET WHERE OBJECT_ID = HEXTORAW(?)", [$page['DS_ID']]);
                    if ($ds) {
                        $sourceObj = $this->normalizeRow($ds)['SOURCE_OBJECT'];
                        if (!empty($sourceObj)) {
                            $table = strtoupper($sourceObj);
                            try {
                                DB::statement("DROP TABLE {$table} CASCADE CONSTRAINTS");
                            } catch (\Exception $e) {}
                        }
                    }
                    DB::delete("DELETE FROM DS_DATASET WHERE OBJECT_ID = HEXTORAW(?)", [$page['DS_ID']]);
                }
            }
            
            // Delete Role & User for this app
            $roleCode = 'ROLE_ADMIN_' . $appCode;
            $role = DB::selectOne("SELECT RAWTOHEX(OBJECT_ID) AS ID FROM SEC_ROLE WHERE OBJECT_CODE = ?", [$roleCode]);
            if ($role) {
                $roleId = $this->normalizeRow($role)['ID'];
                $userRoles = DB::select("SELECT RAWTOHEX(USER_ID) AS UID FROM SEC_USER_ROLE WHERE ROLE_ID = HEXTORAW(?)", [$roleId]);
                DB::delete("DELETE FROM SEC_USER_ROLE WHERE ROLE_ID = HEXTORAW(?)", [$roleId]);
                foreach($userRoles as $ur) {
                    $uid = $this->normalizeRow($ur)['UID'];
                    DB::delete("DELETE FROM SEC_USER WHERE OBJECT_ID = HEXTORAW(?)", [$uid]);
                }
                DB::delete("DELETE FROM SEC_ROLE WHERE OBJECT_ID = HEXTORAW(?)", [$roleId]);
            }
            
            DB::delete("DELETE FROM APP_MODULE WHERE APPLICATION_ID = HEXTORAW(?)", [$appId]);
            DB::delete("DELETE FROM RT_PACKAGE WHERE APPLICATION_ID = HEXTORAW(?)", [$appId]); // In case it was compiled
            DB::delete("DELETE FROM APP_APPLICATION WHERE OBJECT_ID = HEXTORAW(?)", [$appId]);
            
            DB::commit();
            
            session()->flash('success', "Aplikasi {$appCode} berhasil dihapus beserta tabel dan metadatanya.");
            $this->loadApplications();
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus aplikasi: ' . $e->getMessage());
        }
    }

    public function exportApp(string $appId, TableIntrospector $introspector)
    {
        $backup = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'metadata' => [
                'application' => null,
                'modules' => [],
                'menus' => [],
                'pages' => [],
                'fields' => [],
                'val_rules' => [],
                'datasets' => [],
                'roles' => [],
                'users' => [],
                'user_roles' => [],
            ],
            'tables' => [],
        ];

        $getRows = function($table, $whereClause, $bindings) {
            $rows = DB::select("SELECT * FROM {$table} {$whereClause}", $bindings);
            $out = [];
            foreach ($rows as $r) {
                $r = $this->normalizeRow($r);
                foreach ($r as $k => $v) {
                    if (str_ends_with($k, '_ID') || str_ends_with($k, '_BY') || $k === 'ID' || $k === 'OBJECT_ID' || $k === 'APPLICATION_ID' || $k === 'MODULE_ID' || $k === 'PAGE_ID' || $k === 'DATASET_ID' || $k === 'ROLE_ID' || $k === 'USER_ID' || $k === 'PERMISSION_ID' || $k === 'LOV_ID') {
                        if ($v !== null && (strlen($v) === 16 || strlen($v) === 32)) {
                            // If it's binary string (16 bytes), convert to hex. If it's already hex, leave it.
                            if (strlen($v) === 16) {
                                $r[$k] = strtoupper(bin2hex($v));
                            }
                        }
                    }
                }
                $out[] = $r;
            }
            return $out;
        };

        // App
        $apps = $getRows("APP_APPLICATION", "WHERE OBJECT_ID = HEXTORAW(?)", [$appId]);
        if (empty($apps)) {
            session()->flash('error', 'Aplikasi tidak ditemukan.');
            return;
        }
        $backup['metadata']['application'] = $apps[0];
        $appCode = $apps[0]['OBJECT_CODE'];

        $backup['metadata']['modules'] = $getRows("APP_MODULE", "WHERE APPLICATION_ID = HEXTORAW(?)", [$appId]);
        $backup['metadata']['menus'] = $getRows("APP_MENU", "WHERE MODULE_ID IN (SELECT OBJECT_ID FROM APP_MODULE WHERE APPLICATION_ID = HEXTORAW(?))", [$appId]);
        $backup['metadata']['pages'] = $getRows("UI_PAGE", "WHERE APPLICATION_ID = HEXTORAW(?)", [$appId]);
        
        $dsIds = [];
        foreach ($backup['metadata']['pages'] as $page) {
            $pageId = $page['OBJECT_ID'];
            $fields = $getRows("UI_FIELD", "WHERE PAGE_ID = HEXTORAW(?)", [$pageId]);
            $backup['metadata']['fields'] = array_merge($backup['metadata']['fields'], $fields);
            
            foreach ($fields as $f) {
                $rules = $getRows("VAL_RULE", "WHERE FIELD_ID = HEXTORAW(?)", [$f['OBJECT_ID']]);
                $backup['metadata']['val_rules'] = array_merge($backup['metadata']['val_rules'], $rules);
            }
            
            if (!empty($page['DATASET_ID'])) {
                $dsIds[] = $page['DATASET_ID'];
            }
        }

        $dsIds = array_unique($dsIds);
        $physicalTables = [];
        foreach ($dsIds as $dsId) {
            $ds = $getRows("DS_DATASET", "WHERE OBJECT_ID = HEXTORAW(?)", [$dsId]);
            if (!empty($ds)) {
                $backup['metadata']['datasets'][] = $ds[0];
                $src = $ds[0]['SOURCE_OBJECT'] ?? '';
                if (!empty($src) && $introspector->tableExists($src)) {
                    $physicalTables[] = strtoupper($src);
                }
            }
        }

        // Roles & Users
        $roleCode = 'ROLE_ADMIN_' . $appCode;
        $roles = $getRows("SEC_ROLE", "WHERE OBJECT_CODE = ?", [$roleCode]);
        if (!empty($roles)) {
            $role = $roles[0];
            $backup['metadata']['roles'][] = $role;
            $userRoles = $getRows("SEC_USER_ROLE", "WHERE ROLE_ID = HEXTORAW(?)", [$role['OBJECT_ID']]);
            $backup['metadata']['user_roles'] = array_merge($backup['metadata']['user_roles'], $userRoles);
            
            foreach ($userRoles as $ur) {
                $users = $getRows("SEC_USER", "WHERE OBJECT_ID = HEXTORAW(?)", [$ur['USER_ID']]);
                if (!empty($users)) {
                    $backup['metadata']['users'][] = $users[0];
                }
            }
        }

        // Physical Tables (Structure & Data)
        foreach (array_unique($physicalTables) as $tbl) {
            $schema = $introspector->schema($tbl);
            $ddl = "CREATE TABLE {$tbl} (\n";
            $colDefs = [];
            foreach ($schema['columns'] as $col) {
                $type = $col['dataType'];
                if (in_array($type, ['VARCHAR2', 'NVARCHAR2', 'CHAR', 'RAW'])) {
                    $type .= "(" . $col['length'] . ")";
                } elseif ($type === 'NUMBER' && $col['precision']) {
                    $scale = $col['scale'] ? "," . $col['scale'] : "";
                    $type .= "(" . $col['precision'] . $scale . ")";
                }
                $null = $col['nullable'] ? "" : " NOT NULL";
                $default = "";
                if ($col['name'] === 'ID') $default = " DEFAULT SYS_GUID()";
                if ($col['name'] === 'CREATED_AT' || $col['name'] === 'UPDATED_AT') $default = " DEFAULT SYSTIMESTAMP";
                if ($col['name'] === 'VERSION_NO') $default = " DEFAULT 1";
                $colDefs[] = "    " . $col['name'] . " " . $type . $default . $null;
            }
            if (!empty($schema['primaryKey'])) {
                $pk = implode(", ", $schema['primaryKey']);
                $colDefs[] = "    CONSTRAINT PK_{$tbl} PRIMARY KEY ({$pk})";
            }
            $ddl .= implode(",\n", $colDefs);
            $ddl .= "\n)";

            $data = $getRows($tbl, "FETCH FIRST 100 ROWS ONLY", []); // Limit to 100 for safety
            
            $backup['tables'][] = [
                'name' => $tbl,
                'ddl' => $ddl,
                'data' => $data,
            ];
        }

        $json = json_encode($backup, JSON_PRETTY_PRINT);
        $filename = 'backup_' . strtolower($appCode) . '_' . date('Ymd_His') . '.json';
        
        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function openImportModal(): void
    {
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->reset('backupFile');
    }

    public function importApp(): void
    {
        $this->validate([
            'backupFile' => 'required|file|max:10240',
        ]);

        try {
            $json = file_get_contents($this->backupFile->getRealPath());
            $backup = json_decode($json, true);
            if (!$backup || !isset($backup['metadata'])) {
                throw new \Exception("Format file backup tidak valid.");
            }

            DB::beginTransaction();

            $insertTable = function($table, $rows) {
                foreach ($rows as $row) {
                    $columns = array_keys($row);
                    $colsStr = implode(", ", $columns);
                    $vals = [];
                    $bindings = [];
                    foreach ($columns as $c) {
                        $val = $row[$c];
                        if (str_ends_with($c, '_ID') || str_ends_with($c, '_BY') || $c === 'ID' || $c === 'OBJECT_ID' || $c === 'APPLICATION_ID' || $c === 'MODULE_ID' || $c === 'PAGE_ID' || $c === 'DATASET_ID' || $c === 'ROLE_ID' || $c === 'USER_ID' || $c === 'PERMISSION_ID' || $c === 'LOV_ID') {
                            if ($val !== null && strlen($val) === 32 && ctype_xdigit($val)) {
                                $vals[] = "HEXTORAW(?)";
                                $bindings[] = $val;
                            } else {
                                $vals[] = "?";
                                $bindings[] = $val;
                            }
                        } else {
                            $vals[] = "?";
                            $bindings[] = $val;
                        }
                    }
                    $valsStr = implode(", ", $vals);
                    DB::insert("INSERT INTO {$table} ({$colsStr}) VALUES ({$valsStr})", $bindings);
                }
            };

            // 1. Tables
            if (!empty($backup['tables'])) {
                foreach ($backup['tables'] as $tbl) {
                    $exists = DB::selectOne("SELECT 1 FROM USER_TABLES WHERE TABLE_NAME = ?", [$tbl['name']]);
                    if (!$exists) {
                        DB::statement($tbl['ddl']);
                    }
                    if (!empty($tbl['data'])) {
                        DB::delete("DELETE FROM {$tbl['name']}");
                        $insertTable($tbl['name'], $tbl['data']);
                    }
                }
            }

            // 2. Metadata
            $meta = $backup['metadata'];
            if (!empty($meta['application'])) {
                // Remove existing if any (by app id)
                $appId = $meta['application']['OBJECT_ID'];
                try {
                    $this->deleteApp($appId);
                } catch (\Exception $e) {}
                
                $insertTable('APP_APPLICATION', [$meta['application']]);
                $insertTable('APP_MODULE', $meta['modules'] ?? []);
                $insertTable('DS_DATASET', $meta['datasets'] ?? []);
                $insertTable('UI_PAGE', $meta['pages'] ?? []);
                $insertTable('UI_FIELD', $meta['fields'] ?? []);
                $insertTable('VAL_RULE', $meta['val_rules'] ?? []);
                $insertTable('APP_MENU', $meta['menus'] ?? []);
                $insertTable('SEC_ROLE', $meta['roles'] ?? []);
                $insertTable('SEC_USER', $meta['users'] ?? []);
                $insertTable('SEC_USER_ROLE', $meta['user_roles'] ?? []);
            }

            DB::commit();
            $this->closeImportModal();
            session()->flash('success', 'Aplikasi berhasil di-restore dari backup.');
            $this->loadApplications();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->addError('backupFile', 'Gagal restore: ' . $e->getMessage());
        }
    }

    public function downloadDataCsv(string $appId)
    {
        try {
            $app = DB::selectOne("SELECT OBJECT_CODE FROM APP_APPLICATION WHERE OBJECT_ID = HEXTORAW(?)", [$appId]);
            if (!$app) throw new \Exception("Aplikasi tidak ditemukan.");
            $appCode = $this->normalizeRow($app)['OBJECT_CODE'];
            
            $pages = DB::select("SELECT RAWTOHEX(DATASET_ID) AS DS_ID FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?) AND DATASET_ID IS NOT NULL", [$appId]);
            $dsIds = [];
            foreach ($pages as $p) {
                $dsIds[] = $this->normalizeRow($p)['DS_ID'];
            }
            $dsIds = array_unique(array_filter($dsIds));
            
            $tables = [];
            foreach ($dsIds as $dsId) {
                $ds = DB::selectOne("SELECT SOURCE_OBJECT FROM DS_DATASET WHERE OBJECT_ID = HEXTORAW(?)", [$dsId]);
                if ($ds) {
                    $tbl = $this->normalizeRow($ds)['SOURCE_OBJECT'];
                    if (!empty($tbl)) $tables[] = strtoupper($tbl);
                }
            }
            $tables = array_unique($tables);
            
            if (empty($tables)) {
                session()->flash('error', 'Aplikasi ini tidak memiliki tabel fisik/dataset.');
                return;
            }
            
            if (count($tables) === 1) {
                $tableName = $tables[0];
                $filename = strtolower($appCode) . '_' . strtolower($tableName) . '.csv';
                
                return response()->streamDownload(function () use ($tableName) {
                    $out = fopen('php://output', 'w');
                    $cols = DB::select("SELECT COLUMN_NAME FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ? ORDER BY COLUMN_ID", [$tableName]);
                    $headers = array_map(fn($c) => $this->normalizeRow($c)['COLUMN_NAME'], $cols);
                    fputcsv($out, $headers);
                    
                    $query = DB::table($tableName);
                    if (in_array('ID', $headers)) $query->orderBy('ID');
                    
                    foreach ($query->cursor() as $row) {
                        $rowArr = (array) $row;
                        foreach ($rowArr as $k => $v) {
                            if ($v !== null && (strlen($v) === 16 || strlen($v) === 32)) {
                                if (strlen($v) === 16) {
                                    $rowArr[$k] = strtoupper(bin2hex($v));
                                }
                            }
                        }
                        fputcsv($out, array_values($rowArr));
                    }
                    fclose($out);
                }, $filename, ['Content-Type' => 'text/csv']);
            } else {
                $zipFile = storage_path('app/' . strtolower($appCode) . '_data_' . time() . '.zip');
                $zip = new \ZipArchive();
                if ($zip->open($zipFile, \ZipArchive::CREATE) !== true) {
                    throw new \Exception("Gagal membuat file ZIP.");
                }
                
                foreach ($tables as $tableName) {
                    $csvTemp = tempnam(sys_get_temp_dir(), 'csv');
                    $out = fopen($csvTemp, 'w');
                    $cols = DB::select("SELECT COLUMN_NAME FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ? ORDER BY COLUMN_ID", [$tableName]);
                    $headers = array_map(fn($c) => $this->normalizeRow($c)['COLUMN_NAME'], $cols);
                    fputcsv($out, $headers);
                    
                    $query = DB::table($tableName);
                    if (in_array('ID', $headers)) $query->orderBy('ID');
                    
                    foreach ($query->cursor() as $row) {
                        $rowArr = (array) $row;
                        foreach ($rowArr as $k => $v) {
                            if ($v !== null && (strlen($v) === 16 || strlen($v) === 32)) {
                                if (strlen($v) === 16) {
                                    $rowArr[$k] = strtoupper(bin2hex($v));
                                }
                            }
                        }
                        fputcsv($out, array_values($rowArr));
                    }
                    fclose($out);
                    
                    $zip->addFile($csvTemp, strtolower($appCode) . '_' . strtolower($tableName) . '.csv');
                }
                $zip->close();
                
                return response()->download($zipFile)->deleteFileAfterSend(true);
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal mendownload data CSV: ' . $e->getMessage());
        }
    }
}
