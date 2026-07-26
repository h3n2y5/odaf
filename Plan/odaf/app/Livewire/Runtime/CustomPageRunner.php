<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use Livewire\Attributes\Layout;
use Livewire\Component;

class CustomPageRunner extends Component
{
    public string $appCode;
    public string $pageCode;

    public function mount(string $appCode, string $pageCode): void
    {
        $this->appCode = $appCode;
        $this->pageCode = $pageCode;
    }

    #[Layout('layouts.odaf')]
    public function render()
    {
        $className = 'App\\Livewire\\Runtime\\Custom\\' . \Illuminate\Support\Str::studly($this->pageCode);
        
        return view('livewire.runtime.custom-page-runner', [
            'componentClass' => $className
        ]);
    }
}
