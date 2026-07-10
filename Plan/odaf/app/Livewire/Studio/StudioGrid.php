<?php

declare(strict_types=1);

namespace App\Livewire\Studio;

use App\Support\StudioAccess;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Odaf\Studio\TableDataManager;
use Odaf\Studio\TableIntrospector;
use Throwable;

/**
 * Grid data generik untuk sebuah tabel (list/cari/paginasi + hapus).
 */
#[Layout('layouts.odaf')]
final class StudioGrid extends Component
{
    public string $table = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'p')]
    public int $pageNo = 1;

    #[Url(as: 'sort')]
    public string $sortColumn = '';

    #[Url(as: 'dir')]
    public string $sortDir = 'ASC';

    #[Url(as: 'ps')]
    public int $pageSize = 15;

    /** @var array<string, string> filter per kolom. */
    public array $filters = [];

    /** @var array<int, string> kolom yang disembunyikan. */
    public array $hiddenColumns = [];

    /** @var array<int, string> token key baris terpilih (untuk clone). */
    public array $selected = [];

    public function mount(string $table): void
    {
        $this->table = strtoupper($table);
    }

    public function toggleSelectAll(array $keys): void
    {
        $allSelected = $keys !== [] && count(array_intersect($keys, $this->selected)) === count($keys);
        if ($allSelected) {
            $this->selected = array_values(array_diff($this->selected, $keys));
        } else {
            $this->selected = array_values(array_unique([...$this->selected, ...$keys]));
        }
    }

    public function cloneSelected(StudioAccess $access, TableDataManager $manager): void
    {
        $access->ensureAdmin();

        if ($this->selected === []) {
            return;
        }

        $userId = auth()->user()?->getAuthIdentifier();
        $userId = is_string($userId) ? $userId : null;

        $cloned = 0;
        foreach ($this->selected as $token) {
            try {
                $manager->cloneRow($this->table, TableDataManager::decodeKey($token), $userId);
                $cloned++;
            } catch (Throwable $e) {
                $this->addError('grid', 'Clone gagal: ' . $e->getMessage());
            }
        }

        $this->selected = [];
        $this->pageNo = 1;

        if ($cloned > 0) {
            session()->flash('studio.status', "{$cloned} baris di-clone sebagai DRAFT.");
        }
    }

    public function updatedSearch(): void
    {
        $this->pageNo = 1;
    }

    public function updatedFilters(): void
    {
        $this->pageNo = 1;
    }

    public function updatedPageSize(): void
    {
        $this->pageNo = 1;
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->search = '';
        $this->pageNo = 1;
    }

    public function toggleColumn(string $column): void
    {
        $column = strtoupper($column);
        if (in_array($column, $this->hiddenColumns, true)) {
            $this->hiddenColumns = array_values(array_filter($this->hiddenColumns, fn ($c) => $c !== $column));
        } else {
            $this->hiddenColumns[] = $column;
        }
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDir = $this->sortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $this->sortColumn = $column;
            $this->sortDir = 'ASC';
        }
    }

    public function nextPage(int $lastPage): void
    {
        $this->pageNo = min($lastPage, $this->pageNo + 1);
    }

    public function prevPage(): void
    {
        $this->pageNo = max(1, $this->pageNo - 1);
    }

    public function delete(string $key, StudioAccess $access, TableDataManager $manager): void
    {
        $access->ensureAdmin();
        try {
            $manager->delete($this->table, TableDataManager::decodeKey($key));
            session()->flash('studio.status', 'Baris berhasil dihapus.');
        } catch (Throwable $e) {
            $this->addError('grid', $e->getMessage());
        }
    }

    public function render(StudioAccess $access, TableIntrospector $introspector, TableDataManager $manager)
    {
        $access->ensureAdmin();

        $schema = $introspector->schema($this->table);
        $allColumns = $this->displayColumns($schema);
        // Terapkan pilihan sembunyikan kolom (column chooser).
        $displayColumns = array_values(array_filter(
            $allColumns,
            fn (string $c): bool => ! in_array(strtoupper($c), $this->hiddenColumns, true),
        ));

        $result = $manager->list(
            $this->table,
            $this->search,
            $this->sortColumn ?: null,
            $this->sortDir,
            $this->pageNo,
            $this->pageSize,
            $this->filters,
        );

        // Peta label untuk kolom yang tampil (FK dinamis atau opsi statis enum/flag).
        $fkLabels = [];
        foreach ($displayColumns as $name) {
            $meta = $schema['columns'][$name] ?? [];
            $fk = $meta['fk'] ?? null;
            $options = $fk !== null ? $introspector->fkOptions($fk) : ($meta['options'] ?? []);
            foreach ($options as $opt) {
                $fkLabels[$name][$opt['value']] = $opt['label'];
            }
        }

        $rows = array_map(function (array $row) use ($manager): array {
            return [
                'key' => TableDataManager::encodeKey($manager->keyFromRow($this->table, $row)),
                'data' => $row,
            ];
        }, $result['rows']);

        return view('livewire.studio.grid', [
            'groups' => $introspector->groupedTables(),
            'table' => $this->table,
            'schema' => $schema,
            'displayColumns' => $displayColumns,
            'allColumns' => $allColumns,
            'fkLabels' => $fkLabels,
            'rows' => $rows,
            'total' => $result['total'],
            'lastPage' => $result['lastPage'],
            'canEdit' => $schema['primaryKey'] !== [],
        ]);
    }

    /**
     * Pilih kolom yang ditampilkan pada grid (maks 7, tanpa LOB & audit raw).
     *
     * @param  array{columns: array<string, array<string, mixed>>, primaryKey: array<int, string>, displayColumn: string}  $schema
     * @return array<int, string>
     */
    private function displayColumns(array $schema): array
    {
        $skipTypes = ['CLOB', 'NCLOB', 'BLOB', 'LONG'];
        $skipCols = ['CREATED_BY', 'UPDATED_BY', 'DELETED_BY', 'CREATED_AT', 'UPDATED_AT', 'DELETED_AT'];

        $picked = [];
        foreach ($schema['columns'] as $name => $meta) {
            if (in_array((string) $meta['dataType'], $skipTypes, true) || in_array($name, $skipCols, true)) {
                continue;
            }
            $picked[] = $name;
        }

        return $picked === [] ? array_slice(array_keys($schema['columns']), 0, 10) : $picked;
    }
}
