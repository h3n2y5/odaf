<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Odaf\Metadata\Contracts\MetadataRepositoryInterface;
use Throwable;

final class ExportMetadataCommand extends Command
{
    protected $signature = 'odaf:metadata-export
        {applicationCode : Kode Aplikasi (mis. ODAF_DEMO)}';

    protected $description = 'Ekspor metadata aplikasi (modul, menu, dll) ke file JSON';

    public function handle(MetadataRepositoryInterface $repository): int
    {
        $appCode = (string) $this->argument('applicationCode');
        
        try {
            // Cari ID Aplikasi berdasarkan kodenya
            $app = $repository->findByCode('APP', $appCode);
            if (! $app) {
                $this->error("Aplikasi dengan kode {$appCode} tidak ditemukan.");
                return self::FAILURE;
            }

            $this->info("Memuat metadata untuk aplikasi: {$appCode}...");
            $graph = $repository->loadApplicationGraph($app->objectId());
            
            $data = [
                'application' => $graph->attributes(),
                'modules' => $graph->modules(),
                'menus' => $graph->menus(),
                'pages' => $graph->pages(),
                'fields' => $graph->fields(),
                'datasets' => $graph->datasets(),
                'lovs' => $graph->lovs(),
                'rules' => $graph->rules(),
                'workflows' => $graph->workflows(),
                'activities' => $graph->activities(),
                'transitions' => $graph->transitions(),
                'notifications' => $graph->notifications(),
                'subscriptions' => $graph->subscriptions(),
            ];

            array_walk_recursive($data, function (&$value, $key) {
                // Kolom RAW(16) dari Oracle yang belum di-HEX akan terbaca sebagai string 16 byte
                if (is_string($value) && str_ends_with((string) $key, '_ID') && strlen($value) === 16) {
                    $value = strtoupper(bin2hex($value));
                }
            });
            
            // Ekspor Skema Fisik (Tabel dan Data)
            $this->info("Mengekspor skema dan data fisik tabel {$appCode}_*...");
            $schemaData = ['ddl' => [], 'dml' => []];
            $tables = \Illuminate\Support\Facades\DB::table('user_tables')
                ->where('table_name', 'LIKE', strtoupper($appCode) . '_%')
                ->pluck('table_name');
                
            $dbUser = config('database.connections.oracle.username', 'ODAF');
            
            foreach ($tables as $t) {
                try {
                    $ddl = \Illuminate\Support\Facades\DB::select("SELECT dbms_metadata.get_ddl('TABLE', ?, ?) as ddl FROM dual", [$t, strtoupper($dbUser)])[0]->ddl;
                    $schemaData['ddl'][$t] = $ddl;
                } catch (\Throwable $e) {
                    $this->warn("Gagal mengekstrak DDL untuk tabel {$t}: " . $e->getMessage());
                }
                
                $rows = \Illuminate\Support\Facades\DB::table($t)->get();
                $schemaData['dml'][$t] = [];
                foreach ($rows as $row) {
                    $cols = [];
                    $vals = [];
                    foreach ((array)$row as $k => $v) {
                        $cols[] = "\"" . strtoupper((string)$k) . "\"";
                        if ($v === null) {
                            $vals[] = "NULL";
                        } else {
                            if (str_ends_with(strtoupper((string)$k), '_ID') || str_ends_with(strtoupper((string)$k), '_BY')) {
                                $vals[] = "HEXTORAW('" . strtoupper(bin2hex((string)$v)) . "')";
                            } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d+)?$/', (string)$v)) {
                                if (str_contains((string)$v, '.')) {
                                    $vals[] = "TO_TIMESTAMP('" . $v . "', 'YYYY-MM-DD HH24:MI:SS.FF')";
                                } else {
                                    $vals[] = "TO_TIMESTAMP('" . $v . "', 'YYYY-MM-DD HH24:MI:SS')";
                                }
                            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v)) {
                                $vals[] = "TO_DATE('" . $v . "', 'YYYY-MM-DD')";
                            } else {
                                $vals[] = "'" . str_replace("'", "''", (string)$v) . "'";
                            }
                        }
                    }
                    $colStr = implode(", ", $cols);
                    $valStr = implode(", ", $vals);
                    $schemaData['dml'][$t][] = "INSERT INTO \"{$t}\" ({$colStr}) VALUES ({$valStr})";
                }
            }
            $data['schema'] = $schemaData;
            
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            
            $filename = "metadata/{$appCode}.json";
            Storage::put($filename, $json);
            
            $this->info("Metadata berhasil diekspor ke: storage/app/{$filename}");
            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error("Gagal mengekspor metadata: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
