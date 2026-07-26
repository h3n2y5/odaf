<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use App\Support\RuntimeSession;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.odaf')]
final class SysSecurity extends Component
{
    public string $appCode = 'POS_APP';

    public function render(RuntimeSession $session)
    {
        $package = $session->boot($this->appCode);

        return view('livewire.runtime.sys-security', [
            'appCode' => $this->appCode,
            'appName' => $package->toArray()['application']['name'] ?? $this->appCode,
            'nav' => $session->navItems($this->appCode, $package->applicationId()),
        ]);
    }
}
