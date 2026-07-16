<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ImportMetadataCommand extends Command
{
    protected $signature = 'odaf:metadata-import
        {file : Path relatif di dalam storage/app/ atau path absolut ke file JSON}';

    protected $description = 'Impor metadata aplikasi dari file JSON';

    public function handle(): int
    {
        $file = $this->argument('file');
        
        $content = null;
        if (Storage::exists($file)) {
            $content = Storage::get($file);
        } elseif (file_exists($file)) {
            $content = file_get_contents($file);
        } else {
            $this->error("File tidak ditemukan: {$file}");
            return self::FAILURE;
        }
        
        $data = json_decode($content, true);
        if (! $data || ! isset($data['application'])) {
            $this->error("Format JSON tidak valid atau bukan file metadata ODAF.");
            return self::FAILURE;
        }
        
        $this->info("Memulai impor metadata...");
        
        DB::beginTransaction();
        try {
            $this->importTable('APP_APPLICATION', [$data['application']]);
            $this->importTable('APP_MODULE', $data['modules'] ?? []);
            $this->importTable('DS_DATASET', $data['datasets'] ?? []);
            $this->importTable('DS_LOV', $data['lovs'] ?? []);
            $this->importTable('UI_PAGE', $data['pages'] ?? []);
            $this->importTable('APP_MENU', $data['menus'] ?? []);
            $this->importTable('UI_FIELD', $data['fields'] ?? []);
            $this->importTable('VAL_RULE', $data['rules'] ?? []);
            $this->importTable('WF_WORKFLOW', $data['workflows'] ?? []);
            $this->importTable('WF_ACTIVITY', $data['activities'] ?? []);
            $this->importTable('WF_TRANSITION', $data['transitions'] ?? []);
            $this->importTable('NTF_NOTIFICATION', $data['notifications'] ?? []);
            $this->importTable('NTF_SUBSCRIPTION', $data['subscriptions'] ?? []);
            
            DB::commit();
            $this->info("Metadata berhasil diimpor tanpa masalah.");
            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error("Gagal mengimpor metadata. Rollback dilakukan.");
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
    
    private function importTable(string $table, array $rows): void
    {
        if (empty($rows)) {
            return;
        }
        
        $this->info("Mengimpor " . count($rows) . " baris ke tabel {$table}...");
        
        $validColumns = array_map('strtoupper', DB::getSchemaBuilder()->getColumnListing($table));
        
        foreach ($rows as $row) {
            $objectIdHex = $row['OBJECT_ID'];
            $exists = DB::table($table)->whereRaw("OBJECT_ID = HEXTORAW(?)", [$objectIdHex])->exists();
            
            $bindings = [];
            foreach ($row as $col => $val) {
                // Abaikan kolom timestamp audit agar database menggunakan default/otomatis
                if (in_array($col, ['CREATED_AT', 'UPDATED_AT', 'CREATED_BY', 'UPDATED_BY'])) {
                    continue;
                }
                
                // Cegah error ORA-00904 jika struktur database server tertinggal
                if (!in_array(strtoupper($col), $validColumns)) {
                    continue;
                }
                
                // Konversi string HEX menjadi RAW(16) Oracle
                if (str_ends_with($col, '_ID')) {
                    if ($val === null || $val === '') {
                        $bindings[$col] = null;
                    } else {
                        $bindings[$col] = DB::raw("HEXTORAW('{$val}')");
                    }
                } else {
                    $bindings[$col] = $val;
                }
            }
            
            if ($exists) {
                unset($bindings['OBJECT_ID']); // Jangan update Primary Key
                if (!empty($bindings)) {
                    $bindings['UPDATED_AT'] = DB::raw('SYSTIMESTAMP');
                    DB::table($table)->whereRaw("OBJECT_ID = HEXTORAW(?)", [$objectIdHex])->update($bindings);
                }
            } else {
                $bindings['OBJECT_ID'] = DB::raw("HEXTORAW('{$objectIdHex}')");
                DB::table($table)->insert($bindings);
            }
        }
    }
}
