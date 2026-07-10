<?php

declare(strict_types=1);

namespace App\Livewire\Studio;

use App\Support\StudioAccess;
use Livewire\Component;
use Odaf\Studio\ApplicationCompiler;

/**
 * Tombol "Kompilasi & Aktifkan" di header Studio. Mengompilasi ulang seluruh
 * aplikasi agar perubahan metadata (menu/form/dataset/dll) tampil di runtime.
 */
final class StudioCompileButton extends Component
{
    public string $message = '';

    public string $status = '';

    public function recompile(StudioAccess $access, ApplicationCompiler $compiler): void
    {
        $access->ensureAdmin();

        $result = $compiler->compileAll($access->userId());
        $compiled = $result['compiled'];
        $failed = $result['failed'];

        if ($failed !== []) {
            $this->status = 'error';
            $this->message = 'Sebagian gagal: '.implode('; ', array_map(
                static fn (string $code, string $err): string => "{$code}: {$err}",
                array_keys($failed),
                array_values($failed),
            ));

            return;
        }

        $this->status = 'ok';
        $this->message = $compiled === []
            ? 'Tidak ada aplikasi untuk dikompilasi.'
            : 'Aktif: '.implode(', ', $compiled);
    }

    public function render()
    {
        return view('livewire.studio.compile-button');
    }
}
