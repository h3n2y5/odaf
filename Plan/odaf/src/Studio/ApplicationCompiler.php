<?php

declare(strict_types=1);

namespace Odaf\Studio;

use Odaf\Compiler\Contracts\MetadataCompilerInterface;
use Odaf\Metadata\Contracts\MetadataRepositoryInterface;
use Odaf\Runtime\RuntimePackageRepository;
use Throwable;

/**
 * Orkestrasi kompilasi aplikasi untuk ODAF Studio.
 *
 * Setelah metadata disunting via Studio, package harus dikompilasi ulang &
 * diaktifkan agar runtime menampilkannya (CORE-002: runtime hanya membaca
 * package terkompilasi, bukan tabel metadata langsung).
 */
final class ApplicationCompiler
{
    public function __construct(
        private readonly MetadataRepositoryInterface $repository,
        private readonly MetadataCompilerInterface $compiler,
        private readonly RuntimePackageRepository $packages,
    ) {}

    /**
     * Kompilasi & aktifkan seluruh aplikasi.
     *
     * @return array{compiled: array<int, string>, failed: array<string, string>}
     */
    public function compileAll(?string $compiledBy = null): array
    {
        $compiled = [];
        $failed = [];

        foreach ($this->repository->allInDomain('APP') as $app) {
            try {
                $this->compileOne($app->objectId(), $compiledBy);
                $compiled[] = $app->objectCode();
            } catch (Throwable $e) {
                $failed[$app->objectCode()] = $this->firstLine($e->getMessage());
            }
        }

        return ['compiled' => $compiled, 'failed' => $failed];
    }

    /**
     * Kompilasi & aktifkan satu aplikasi berdasarkan OBJECT_ID.
     */
    public function compileOne(string $applicationId, ?string $compiledBy = null): string
    {
        $graph = $this->repository->loadApplicationGraph($applicationId);
        $package = $this->compiler->compile($graph);
        $this->packages->store($package, $compiledBy);
        $this->packages->activate($package->applicationId(), $package->packageVersion());

        $this->generateCustomPages($graph);

        return $package->packageVersion();
    }

    private function generateCustomPages(\Odaf\Metadata\Contracts\MetadataObjectInterface $graph): void
    {
        $viewPath = resource_path('views/livewire/runtime/custom');
        $classPath = app_path('Livewire/Runtime/Custom');

        if (!is_dir($viewPath)) {
            @mkdir($viewPath, 0755, true);
        }
        if (!is_dir($classPath)) {
            @mkdir($classPath, 0755, true);
        }

        foreach ($graph->pages() as $page) {
            if (($page['PAGE_TYPE'] ?? '') === 'CUSTOM') {
                $code = $page['OBJECT_CODE'];
                $className = \Illuminate\Support\Str::studly($code);
                $viewName = \Illuminate\Support\Str::kebab($code);
                
                $viewContent = $page['CUSTOM_VIEW_BLADE'] ?? '<div></div>';
                if (is_resource($viewContent)) $viewContent = stream_get_contents($viewContent);
                
                $logicContent = $page['CUSTOM_LOGIC_PHP'] ?? '';
                if (is_resource($logicContent)) $logicContent = stream_get_contents($logicContent);

                $pageConfig = $page['PAGE_CONFIG'] ?? null;
                if (is_resource($pageConfig)) $pageConfig = stream_get_contents($pageConfig);
                
                if (!empty($pageConfig)) {
                    $blocks = json_decode($pageConfig, true) ?? [];
                    if (!empty($blocks)) {
                        $generatedBlade = "<div>\n";
                        foreach ($blocks as $block) {
                            $type = $block['type'] ?? '';
                            $config = $block['config'] ?? [];
                            
                            if ($type === 'hero') {
                                $title = htmlspecialchars($config['title'] ?? '');
                                $subtitle = htmlspecialchars($config['subtitle'] ?? '');
                                $btnText = htmlspecialchars($config['buttonText'] ?? '');
                                $btnRoute = htmlspecialchars($config['buttonRoute'] ?? '#');
                                
                                $btnHtml = '';
                                if ($btnText !== '') {
                                    $btnHtml = "<a href=\"{$btnRoute}\" wire:navigate class=\"inline-block bg-white text-indigo-600 px-6 py-2 rounded-full font-bold hover:bg-indigo-50 transition-colors shadow-sm\">{$btnText}</a>";
                                }
                                
                                $generatedBlade .= <<<HTML
<div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl p-10 mb-6 text-center shadow-lg border border-indigo-500">
    <h1 class="text-4xl font-extrabold mb-4 tracking-tight">{$title}</h1>
    <p class="text-indigo-100 text-lg mb-8 max-w-2xl mx-auto font-medium">{$subtitle}</p>
    {$btnHtml}
</div>

HTML;
                            } elseif ($type === 'stats') {
                                $generatedBlade .= "<div class=\"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6\">\n";
                                
                                for ($i = 1; $i <= 2; $i++) {
                                    $label = htmlspecialchars($config["stat{$i}_label"] ?? '');
                                    $val = htmlspecialchars($config["stat{$i}_value"] ?? '');
                                    if ($label !== '') {
                                        $generatedBlade .= <<<HTML
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500 transform origin-bottom scale-y-0 group-hover:scale-y-100 transition-transform"></div>
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">{$label}</h3>
        <p class="text-3xl font-extrabold text-slate-900 mt-2">{$val}</p>
    </div>

HTML;
                                    }
                                }
                                $generatedBlade .= "</div>\n";
                            } elseif ($type === 'custom') {
                                $generatedBlade .= $viewContent . "\n";
                            }
                        }
                        $generatedBlade .= "</div>";
                        $viewContent = $generatedBlade;
                    }
                }

                $fullClassCode = <<<PHP
<?php

namespace App\Livewire\Runtime\Custom;

use Livewire\Component;

class {$className} extends Component
{
{$logicContent}

    public function render()
    {
        return view('livewire.runtime.custom.{$viewName}');
    }
}
PHP;
                file_put_contents($viewPath . '/' . $viewName . '.blade.php', $viewContent);
                file_put_contents($classPath . '/' . $className . '.php', $fullClassCode);
            }
        }
    }

    private function firstLine(string $text): string
    {
        $line = strtok($text, "\n");

        return $line === false ? $text : $line;
    }
}
