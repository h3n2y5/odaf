<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\RuntimeSession;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Odaf\Engine\Dataset\Contracts\DatasetEngineInterface;
use Odaf\Engine\Document\DocumentRendererService;
use Odaf\Engine\QrCode\Contracts\QrCodeServiceInterface;

/**
 * Controller untuk mencetak dokumen (Template HTML).
 */
final class DocumentPrintController
{
    public function print(
        Request $request,
        RuntimeSession $session,
        DatasetEngineInterface $datasetEngine,
        QrCodeServiceInterface $qrService,
        DocumentRendererService $renderer,
        string $appCode,
        string $pageCode,
        string $key,
    ): Response {
        $package = $session->boot($appCode);
        $context = $session->context($package->applicationId());
        
        $graph = $package->toArray();
        $pages = $graph['pages'] ?? [];
        
        // Cari page
        $page = null;
        foreach ($pages as $p) {
            if (($p['code'] ?? '') === $pageCode) {
                $page = $p;
                break;
            }
        }
        
        if (! $page) {
            abort(404, 'Halaman tidak ditemukan.');
        }

        $templates = $page['rptTemplates'] ?? [];
        if (empty($templates)) {
            abort(404, 'Dokumen ini tidak memiliki template cetakan.');
        }

        // Ambil template default, atau template pertama
        $template = $templates[0];
        foreach ($templates as $t) {
            if ($t['isDefault']) {
                $template = $t;
                break;
            }
        }

        $datasetId = $page['datasetId'] ?? null;
        if (! $datasetId) {
            abort(400, 'Halaman tidak terkait dengan dataset.');
        }

        // 1. Ambil data Header
        $headerData = $datasetEngine->find($context, $datasetId, $key);
        if (! $headerData) {
            abort(404, 'Data dokumen tidak ditemukan.');
        }

        // 2. Ambil data Detail (bila ada)
        $detailsConfig = $page['details'] ?? [];
        foreach ($detailsConfig as $detail) {
            $childDatasetId = $detail['datasetId'];
            $fkColumn = $detail['fkColumn'];
            $detailCode = $detail['pageCode']; // Digunakan sebagai nama iterasi {{#DETAIL_CODE}}
            
            // Ambil struktur table untuk raw query
            $ds = null;
            foreach ($graph['datasets'] as $d) {
                if ($d['id'] === $childDatasetId) {
                    $ds = $d;
                    break;
                }
            }
            
            if ($ds && isset($ds['sourceObject'])) {
                // Untuk kesederhanaan, kita langsung select dari tabel detail
                $childRows = DB::table($ds['sourceObject'])
                    ->where($fkColumn, $key)
                    ->get()
                    ->map(fn($row) => (array) $row)
                    ->toArray();
                    
                $headerData[$detailCode] = $childRows;
            }
        }

        // 3. Inject QR Code HTML jika ada
        $qrConfigs = $page['qrConfigs'] ?? [];
        foreach ($qrConfigs as $qrConf) {
            if ((bool) ($qrConf['showOnPrint'] ?? true)) {
                $qrSvgData = $qrService->buildFromConfig($qrConf, $key, $headerData);
                if ($qrSvgData) {
                    // Masukkan ke data sebagai raw HTML
                    $headerData['QR_CODE'] = $qrSvgData['svg'] ?? '';
                    $headerData['QR_URL'] = $qrSvgData['url'] ?? '';
                    // Berhenti di QR pertama yang showOnPrint
                    break;
                }
            }
        }

        // 4. Render HTML
        $htmlBody = $renderer->render($template['htmlContent'] ?? '', $headerData);
        $cssBody = $template['cssContent'] ?? '';
        $pageSize = $template['pageSize'] ?? 'A4';
        $orientation = strtolower($template['orientation'] ?? 'portrait');
        $title = $template['name'] ?? 'Dokumen';

        // 5. Susun ke dalam layout blank untuk printing
        $finalHtml = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - {$key}</title>
    <style>
        /* Base Reset */
        body { margin: 0; padding: 0; font-family: sans-serif; background: #e2e8f0; }
        
        /* Document Container (untuk preview di browser) */
        .page-container {
            background: white;
            margin: 20px auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
        }

        /* Ukuran Kertas */
        .page-size-A4 { width: 210mm; min-height: 297mm; }
        .page-size-LETTER { width: 215.9mm; min-height: 279.4mm; }
        
        /* Orientasi Kertas (Preview) */
        .orient-landscape.page-size-A4 { width: 297mm; min-height: 210mm; }
        .orient-landscape.page-size-LETTER { width: 279.4mm; min-height: 215.9mm; }

        /* Print Media Rules */
        @media print {
            body { background: white; margin: 0; }
            .page-container { margin: 0; box-shadow: none; border: none; }
            /* Memaksa orientasi di Chrome/Edge */
            @page { size: {$pageSize} {$orientation}; margin: 10mm; }
        }
        
        /* CSS Tambahan dari Template */
        {$cssBody}
    </style>
</head>
<body>
    <div class="page-container page-size-{$pageSize} orient-{$orientation}">
        <!-- Konten HTML Rendered -->
        {$htmlBody}
    </div>
    <script>
        // Otomatis print saat halaman selesai dimuat
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
HTML;

        return response($finalHtml)->header('Content-Type', 'text/html');
    }
}
