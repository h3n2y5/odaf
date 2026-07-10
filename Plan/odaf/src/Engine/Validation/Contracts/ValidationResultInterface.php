<?php

declare(strict_types=1);

namespace Odaf\Engine\Validation\Contracts;

/**
 * Hasil evaluasi validasi.
 */
interface ValidationResultInterface
{
    public function passes(): bool;

    public function fails(): bool;

    /**
     * Error per field: [ fieldCode => [ pesan, ... ] ].
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array;
}
