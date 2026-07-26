<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use App\Support\RuntimeSession;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

/**
 * Halaman beranda aplikasi runtime: menampilkan menu dari package terkompilasi.
 */
#[Layout('layouts.odaf')]
final class AppHome extends Component
{
    public string $appCode = '';

    public function mount(string $appCode): void
    {
        $this->appCode = $appCode;
    }

    public function render(RuntimeSession $session)
    {
        try {
            $package = $session->boot($this->appCode);
        } catch (Throwable $e) {
            return view('livewire.runtime.error', [
                'appCode' => $this->appCode,
                'message' => $e->getMessage(),
            ]);
        }

        $reports = \Illuminate\Support\Facades\DB::select("
            SELECT RAWTOHEX(REPORT_ID) AS ID, REPORT_NAME, REPORT_DESC 
            FROM ODAF.APP_REPORT 
            WHERE APP_CODE = ?
            ORDER BY REPORT_NAME
        ", [$this->appCode]);

        return view('livewire.runtime.app-home', [
            'appCode' => $this->appCode,
            'appName' => $package->toArray()['application']['name'] ?? $this->appCode,
            'nav' => $session->navItems($this->appCode, $package->applicationId()),
            'packageVersion' => $package->packageVersion(),
            'reports' => array_map(function($r) {
                return [
                    'id' => $r->id,
                    'name' => $r->report_name,
                    'desc' => $r->report_desc,
                ];
            }, $reports),
        ]);
    }
}
