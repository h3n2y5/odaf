<?php

declare(strict_types=1);

namespace Odaf\Engine\QrCode\Contracts;

use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * BB-04 extension — QR Code service contract.
 *
 * Menghasilkan QR Code image (SVG) dan URL bertanda-tangan HMAC untuk
 * dokumen/form runtime. Juga memvalidasi URL yang di-scan.
 */
interface QrCodeServiceInterface
{
    /**
     * Generate signed URL untuk QR Code.
     *
     * @param  array<string, mixed>  $extra  Parameter tambahan (act, fk, fields)
     * @return string  URL bertanda-tangan HMAC
     */
    public function generateUrl(string $appCode, string $pageCode, string $entityKey, array $extra = []): string;

    /**
     * Generate QR Code image sebagai SVG string.
     */
    public function generateSvg(string $data, int $size = 150): string;

    /**
     * Generate QR Code image sebagai data URI (untuk embedding di HTML).
     */
    public function generateDataUri(string $data, int $size = 150): string;

    /**
     * Validasi HMAC signature pada URL QR.
     *
     * @return array{valid: bool, appCode: string, pageCode: string, key: string, action: ?string, extra: array<string, mixed>}
     */
    public function validateUrl(string $url): array;

    /**
     * Bangun URL dan SVG dari QR_CONFIG metadata + data form aktif.
     *
     * @param  array<string, mixed>  $qrConfig   Konfigurasi QR dari compiled package
     * @param  array<string, mixed>  $formData    Nilai field form saat ini
     * @return array{url: string, svg: string, config: array<string, mixed>}|null
     */
    public function buildFromConfig(array $qrConfig, string $entityKey, array $formData = []): ?array;

    /**
     * Log scan QR ke RT_QR_SCAN_LOG.
     */
    public function logScan(
        ExecutionContextInterface $context,
        ?string $qrConfigId,
        string $appCode,
        string $pageCode,
        string $entityKey,
        string $scanSource,
        ?string $actionTaken,
        ?string $qrUrl,
        ?string $userAgent,
    ): void;
}
