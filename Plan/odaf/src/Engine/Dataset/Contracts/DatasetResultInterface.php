<?php

declare(strict_types=1);

namespace Odaf\Engine\Dataset\Contracts;

/**
 * Hasil query dataset beserta metadata paginasi.
 */
interface DatasetResultInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array;

    public function total(): int;

    public function page(): int;

    public function pageSize(): int;
}
