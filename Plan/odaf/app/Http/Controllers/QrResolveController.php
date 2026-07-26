<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\RuntimeSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Odaf\Engine\QrCode\Contracts\QrCodeServiceInterface;

/**
 * Resolve QR Code URL — validate signature, log scan, redirect ke form.
 *
 * Route: GET /qr/{appCode}/{pageCode}/{key}
 *
 * Query params:
 *   sig    = HMAC signature (required)
 *   ts     = timestamp (required)
 *   act    = workflow action to prompt (optional)
 *   tapp   = target app code jika beda dari source (optional)
 *   tpage  = target page code jika cross-document (optional)
 *   fk     = FK column name for cross-document prefill (optional)
 *   data   = base64-encoded custom field data (optional)
 */
final class QrResolveController
{
    public function resolve(
        Request $request,
        QrCodeServiceInterface $qrService,
        RuntimeSession $session,
        string $appCode,
        string $pageCode,
        string $key,
    ): RedirectResponse {
        // Rebuild the URL path for validation.
        $path = "/qr/{$appCode}/{$pageCode}/{$key}";
        $queryParams = $request->query();

        // Validate HMAC signature.
        $result = $qrService->validateUrl(url($path) . '?' . http_build_query($queryParams));

        if (! $result['valid']) {
            abort(403, 'QR Code tidak valid: ' . ($result['reason'] ?? 'signature invalid'));
        }

        // Log scan to RT_QR_SCAN_LOG.
        try {
            $package = $session->boot($appCode);
            $context = $session->context($package->applicationId());

            $qrService->logScan(
                context: $context,
                qrConfigId: null,
                appCode: $appCode,
                pageCode: $pageCode,
                entityKey: $key,
                scanSource: $this->detectScanSource($request),
                actionTaken: $request->query('act'),
                qrUrl: $request->fullUrl(),
                userAgent: $request->userAgent(),
            );
        } catch (\Throwable $e) {
            // Log failure should not prevent redirect.
            logger()->warning('ODAF QR scan log failed', ['error' => $e->getMessage()]);
        }

        // Determine target: same page or cross-document.
        $targetApp = $request->query('tapp', $appCode);
        $targetPage = $request->query('tpage', $pageCode);
        $action = $request->query('act');
        $fkColumn = $request->query('fk');

        // Build redirect URL.
        $redirectParams = [
            'appCode' => $targetApp,
            'pageCode' => $targetPage,
        ];

        // Cross-document: redirect ke form create dengan prefill.
        if ($targetPage !== $pageCode || ($targetApp !== $appCode && $request->has('tapp'))) {
            // Navigasi ke form baru (create) dengan FK prefill via session flash.
            if ($fkColumn !== null && $fkColumn !== '') {
                session()->flash('odaf.qr.prefill', [
                    'column' => strtoupper($fkColumn),
                    'value' => $key,
                    'sourceApp' => $appCode,
                    'sourcePage' => $pageCode,
                ]);
            }

            // Jika ada action, flash sebagai pending action.
            if ($action !== null && $action !== '') {
                session()->flash('odaf.qr.action', $action);
            }

            return redirect()->route('odaf.form', $redirectParams);
        }

        // Same document: redirect ke form edit dengan pending action.
        $redirectParams['key'] = $key;

        if ($action !== null && $action !== '') {
            session()->flash('odaf.qr.action', $action);
        }

        session()->flash('odaf.status', 'Dokumen dibuka dari QR Code.');

        return redirect()->route('odaf.form', $redirectParams);
    }

    /**
     * Detect scan source from User-Agent.
     */
    private function detectScanSource(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?? '');

        if (str_contains($ua, 'android') || str_contains($ua, 'mobile')) {
            return 'ANDROID';
        }

        return 'WEB';
    }
}
