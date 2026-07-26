<?php

declare(strict_types=1);

namespace Odaf\Engine\QrCode;

/**
 * HMAC-SHA256 signature generator & validator untuk QR Code URLs.
 *
 * Memastikan URL QR tidak bisa dipalsukan. Signature dihitung dari path + query
 * (tanpa parameter sig) menggunakan APP_KEY Laravel sebagai secret.
 *
 * URL expiry: signature valid selama 365 hari secara default (configurable).
 */
final class QrSignatureValidator
{
    /** Default TTL: 365 hari dalam detik. */
    private const DEFAULT_TTL = 365 * 24 * 3600;

    private readonly string $secret;

    private readonly int $ttl;

    public function __construct(?string $secret = null, ?int $ttlSeconds = null)
    {
        $this->secret = $secret ?? (string) config('app.key', 'odaf-qr-secret');
        $this->ttl = $ttlSeconds ?? (int) config('odaf.qr.ttl', self::DEFAULT_TTL);
    }

    /**
     * Sign a QR URL path + query params.
     *
     * @param  string  $path       URL path (mis. /qr/ODAF_DEMO/FRM_PO/ABC123)
     * @param  array<string, string>  $params  Query params (tanpa sig)
     * @return string  HMAC signature hex
     */
    public function sign(string $path, array $params): string
    {
        // Sort params untuk determinisme.
        ksort($params);
        $data = $path . '?' . http_build_query($params);

        return hash_hmac('sha256', $data, $this->secret);
    }

    /**
     * Verify a QR URL.
     *
     * @param  string  $path
     * @param  array<string, string>  $params   Query params termasuk 'sig'
     * @return array{valid: bool, reason: ?string}
     */
    public function verify(string $path, array $params): array
    {
        $signature = $params['sig'] ?? '';
        if ($signature === '') {
            return ['valid' => false, 'reason' => 'Missing signature'];
        }

        // Hapus sig dari params untuk recompute.
        $paramsWithoutSig = $params;
        unset($paramsWithoutSig['sig']);

        $expected = $this->sign($path, $paramsWithoutSig);

        if (! hash_equals($expected, $signature)) {
            return ['valid' => false, 'reason' => 'Invalid signature'];
        }

        // Check TTL.
        $ts = isset($params['ts']) ? (int) $params['ts'] : 0;
        if ($ts > 0 && $this->ttl > 0 && (time() - $ts) > $this->ttl) {
            return ['valid' => false, 'reason' => 'QR Code expired'];
        }

        return ['valid' => true, 'reason' => null];
    }

    /**
     * Append signature ke params dan kembalikan query string lengkap.
     *
     * @param  string  $path
     * @param  array<string, string>  $params
     * @return string  Query string dengan sig
     */
    public function signedQueryString(string $path, array $params): string
    {
        $sig = $this->sign($path, $params);
        $params['sig'] = $sig;

        return http_build_query($params);
    }
}
