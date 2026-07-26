<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * QR Code Scanner — halaman pemindai QR Code menggunakan kamera perangkat.
 *
 * Menggunakan library JavaScript html5-qrcode untuk akses kamera browser.
 * Setelah QR terdeteksi, URL divalidasi dan user diarahkan ke form terkait.
 */
#[Layout('layouts.odaf')]
final class QrScanner extends Component
{
    /** Kode QR yang terdeteksi (dari JavaScript). */
    public string $scannedUrl = '';

    /** Status scanner: idle | scanning | success | error */
    public string $scanStatus = 'idle';

    /** Pesan error (jika ada). */
    public string $errorMessage = '';

    /** Manual input mode. */
    public string $manualCode = '';

    /**
     * Dipanggil dari JavaScript saat QR Code berhasil di-scan.
     */
    public function onQrDetected(string $url): void
    {
        $this->scannedUrl = $url;
        $this->scanStatus = 'success';

        // Validasi apakah URL ini adalah ODAF QR.
        if ($this->isOdafQrUrl($url)) {
            $this->redirect($url, navigate: true);
        } else {
            $this->errorMessage = 'QR Code bukan dari sistem ODAF.';
            $this->scanStatus = 'error';
        }
    }

    /**
     * Submit manual QR code input.
     */
    public function submitManual(): void
    {
        $url = trim($this->manualCode);
        if ($url === '') {
            return;
        }

        $this->onQrDetected($url);
    }

    /**
     * Reset scanner ke idle.
     */
    public function resetScanner(): void
    {
        $this->scannedUrl = '';
        $this->scanStatus = 'idle';
        $this->errorMessage = '';
        $this->manualCode = '';
    }

    public function render()
    {
        return view('livewire.runtime.qr-scanner');
    }

    /**
     * Cek apakah URL ini adalah QR Code ODAF (mengandung /qr/ path).
     */
    private function isOdafQrUrl(string $url): bool
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';

        // Harus mengandung /qr/ di path.
        return str_contains($path, '/qr/');
    }
}
