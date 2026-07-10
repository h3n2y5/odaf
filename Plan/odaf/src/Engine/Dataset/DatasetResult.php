<?php

declare(strict_types=1);

namespace Odaf\Engine\Dataset;

use Odaf\Engine\Dataset\Contracts\DatasetResultInterface;

/**
 * Hasil query dataset beserta metadata paginasi.
 */
final class DatasetResult implements DatasetResultInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        private readonly array $rows,
        private readonly int $total,
        private readonly int $page,
        private readonly int $pageSize,
    ) {}

    public function rows(): array
    {
        return $this->rows;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function pageSize(): int
    {
        return $this->pageSize;
    }

    public function lastPage(): int
    {
        return $this->pageSize > 0 ? (int) max(1, ceil($this->total / $this->pageSize)) : 1;
    }
}
