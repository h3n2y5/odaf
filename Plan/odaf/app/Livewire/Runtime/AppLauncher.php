<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Odaf\Runtime\RuntimePackageRepository;

#[Layout('layouts.odaf')]
final class AppLauncher extends Component
{
    public function render(RuntimePackageRepository $repository): View
    {
        $packages = $repository->loadAllActive();

        $apps = [];
        foreach ($packages as $pkg) {
            $payload = $pkg->toArray();
            $apps[] = [
                'code' => $payload['application']['code'] ?? 'UNKNOWN',
                'name' => $payload['application']['name'] ?? 'Unknown App',
            ];
        }

        // Jika hanya ada 1 aplikasi dan user BUKAN superuser, kita bisa langsung redirect?
        // User minta "pilih dari yang tersedia", jadi kita tampilkan saja daftarnya.

        return view('livewire.runtime.app-launcher', [
            'apps' => $apps,
            'user' => Auth::guard('web')->user(),
        ]);
    }
}
