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
        $appCode = strtoupper($this->argument('applicationCode'));
        
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
            
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
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
