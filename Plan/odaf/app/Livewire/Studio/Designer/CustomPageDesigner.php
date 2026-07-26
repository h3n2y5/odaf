<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.odaf')]
final class CustomPageDesigner extends Component
{
    public string $appId;
    public string $pageId;
    
    public string $pageName = '';
    public string $pageCode = '';
    public string $customViewBlade = '';
    public string $customLogicPhp = '';
    public array $blocks = [];
    
    public bool $developerMode = false;

    public array $application = [];

    public function mount(StudioAccess $access, string $pageId): void
    {
        $access->ensureAdmin();

        $this->pageId = $pageId;

        $page = DB::selectOne("
            SELECT RAWTOHEX(APPLICATION_ID) as APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, CUSTOM_VIEW_BLADE, CUSTOM_LOGIC_PHP, PAGE_CONFIG
            FROM ODAF.UI_PAGE 
            WHERE OBJECT_ID = HEXTORAW(?)
        ", [$this->pageId]);

        if (!$page) abort(404, 'Custom Page not found');
        
        $arr = (array)$page;
        $this->appId = $arr['application_id'] ?? $arr['APPLICATION_ID'] ?? '';

        $appRow = DB::selectOne("SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, OBJECT_CODE, OBJECT_NAME FROM ODAF.APP_APPLICATION WHERE OBJECT_ID = HEXTORAW(?)", [$this->appId]);
        if (!$appRow) abort(404, 'App not found');
        $this->application = (array) $appRow;
        
        $this->pageName = $arr['object_name'] ?? $arr['OBJECT_NAME'] ?? '';
        $this->pageCode = $arr['object_code'] ?? $arr['OBJECT_CODE'] ?? '';
        
        $view = $arr['custom_view_blade'] ?? $arr['CUSTOM_VIEW_BLADE'] ?? '';
        if (is_resource($view)) $view = stream_get_contents($view);
        $this->customViewBlade = $view;

        $logic = $arr['custom_logic_php'] ?? $arr['CUSTOM_LOGIC_PHP'] ?? '';
        if (is_resource($logic)) $logic = stream_get_contents($logic);
        $this->customLogicPhp = $logic;
        
        // Setup initial placeholders if empty
        if (empty($this->customViewBlade)) {
            $this->customViewBlade = <<<BLADE
<!-- Your Custom HTML/Blade UI goes here -->
<div class="p-6 bg-white rounded-lg shadow">
    <h2 class="text-xl font-bold text-slate-800">Hello {{ \$name }}!</h2>
    <p class="text-slate-500 mt-2">This is a custom page. Edit the blade template and PHP logic below to build your UI.</p>
    
    <button wire:click="doSomething" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
        Click Me
    </button>
</div>
BLADE;
        }

        if (empty($this->customLogicPhp)) {
            $this->customLogicPhp = <<<PHP
    // Your Livewire properties and methods go here
    public string \$name = 'ODAF Developer';
    
    public function doSomething()
    {
        \$this->name = 'Awesome ' . now()->format('H:i:s');
    }
PHP;
        }

        $pageConfig = $arr['page_config'] ?? $arr['PAGE_CONFIG'] ?? null;
        if (is_resource($pageConfig)) $pageConfig = stream_get_contents($pageConfig);
        if (!empty($pageConfig) && $pageConfig !== '[]') {
            $this->blocks = json_decode($pageConfig, true) ?? [];
        } else {
            // Check if there's actual custom code (not just the default placeholder)
            $isPlaceholder = str_contains($this->customViewBlade, '<!-- Your Custom HTML/Blade UI goes here -->') &&
                             str_contains($this->customLogicPhp, 'public string $name = \'ODAF Developer\';');
                             
            if (!$isPlaceholder && (trim($this->customViewBlade) !== '' || trim($this->customLogicPhp) !== '')) {
                $this->blocks = [
                    [
                        'id' => uniqid('blk_'),
                        'type' => 'custom',
                        'config' => []
                    ]
                ];
            } else {
                $this->blocks = [];
            }
        }
    }
    
    public function addBlock(string $type): void
    {
        $this->blocks[] = [
            'id' => uniqid('blk_'),
            'type' => $type,
            'config' => [],
        ];
    }
    
    public function removeBlock(int $index): void
    {
        if (isset($this->blocks[$index])) {
            unset($this->blocks[$index]);
            $this->blocks = array_values($this->blocks);
        }
    }
    
    public function moveBlock(int $index, int $direction): void
    {
        if ($direction === -1 && $index > 0) {
            $temp = $this->blocks[$index];
            $this->blocks[$index] = $this->blocks[$index - 1];
            $this->blocks[$index - 1] = $temp;
        } elseif ($direction === 1 && $index < count($this->blocks) - 1) {
            $temp = $this->blocks[$index];
            $this->blocks[$index] = $this->blocks[$index + 1];
            $this->blocks[$index + 1] = $temp;
        }
    }

    public function toggleDeveloperMode(): void
    {
        $this->developerMode = !$this->developerMode;
    }

    public function save(): void
    {
        $pageConfigJson = json_encode($this->blocks);
        
        DB::update("
            UPDATE ODAF.UI_PAGE 
            SET CUSTOM_VIEW_BLADE = ?, CUSTOM_LOGIC_PHP = ?, PAGE_CONFIG = ?, UPDATED_AT = SYSTIMESTAMP 
            WHERE OBJECT_ID = HEXTORAW(?)
        ", [$this->customViewBlade, $this->customLogicPhp, $pageConfigJson, $this->pageId]);
        
        session()->flash('success', 'Custom Page berhasil disimpan. Silakan Kompilasi Aplikasi untuk melihat hasilnya.');
    }

    public function render()
    {
        return view('livewire.studio.designer.custom-page-designer');
    }
}
