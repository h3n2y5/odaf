<?php

declare(strict_types=1);

namespace Odaf\Engine\Lov\Contracts;

use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * Resolusi List of Values (LOV) untuk runtime (Vol.2 Bab 14, Vol.3 Bab 11).
 *
 * Mengubah definisi LOV terkompilasi (STATIC/SQL/VIEW) menjadi daftar opsi
 * {value,label} untuk dropdown form, serta memetakan sebuah nilai ke labelnya
 * untuk tampilan grid.
 */
interface LovEngineInterface
{
    /**
     * Daftar opsi LOV.
     *
     * @param  array<string, mixed>  $params  nilai untuk placeholder {{TOKEN}} pada
     *                                        LOV SQL (mis. nilai kolom record aktif). LOV dependen mengembalikan
     *                                        [] bila parameter yang dibutuhkan belum tersedia.
     * @return array<int, array{value: string, label: string}>
     */
    public function options(ExecutionContextInterface $context, string $lovId, array $params = []): array;

    /**
     * Label untuk sebuah nilai (atau null bila tak ditemukan).
     *
     * @param  array<string, mixed>  $params
     */
    public function label(ExecutionContextInterface $context, string $lovId, string $value, array $params = []): ?string;
}
