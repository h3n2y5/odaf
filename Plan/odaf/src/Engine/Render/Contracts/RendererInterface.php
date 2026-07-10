<?php

declare(strict_types=1);

namespace Odaf\Engine\Render\Contracts;

use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * BB-04 Renderer Engine (Vol.3 Bab 14).
 *
 * Mengubah objek runtime (RT_PAGE, RT_FIELD, dst) menjadi artefak presentasi.
 * Renderer TIDAK boleh memuat logika bisnis (Vol.1 Bab 10 §9).
 *
 * Target render awal: HTML (via Blade/Livewire). Target lain (JSON, PDF, mobile)
 * ditambahkan melalui plugin renderer.
 */
interface RendererInterface
{
    /**
     * Nama target yang didukung renderer ini, mis. "html", "json".
     */
    public function target(): string;

    /**
     * Render sebuah halaman runtime menjadi payload presentasi.
     *
     * @param  array<string, mixed>  $runtimePage  objek RT_PAGE terkompilasi
     * @param  array<string, mixed>  $data  data yang akan ditampilkan
     * @return mixed payload presentasi (string HTML, array JSON, dsb)
     */
    public function renderPage(ExecutionContextInterface $context, array $runtimePage, array $data = []): mixed;
}
