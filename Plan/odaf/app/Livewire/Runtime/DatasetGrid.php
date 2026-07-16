<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use App\Support\RuntimeSession;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Odaf\Engine\Audit\Contracts\AuditEngineInterface;
use Odaf\Engine\Dataset\Contracts\DatasetEngineInterface;
use Odaf\Engine\Lov\Contracts\LovEngineInterface;
use Odaf\Engine\Security\Contracts\SecurityEngineInterface;

/**
 * Grid/list generik yang digenerate dari package terkompilasi.
 *
 * Tidak ada kode khusus entity: kolom, dataset, dan halaman semuanya berasal
 * dari model halaman GRID/FORM di package (BB-04 + BB-05).
 */
#[Layout('layouts.odaf')]
final class DatasetGrid extends Component
{
    public string $appCode = '';

    public string $pageCode = '';

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

    /** @var array<string, string> filter per kolom, keyed by COLUMN_NAME (upper). */
    public array $filters = [];

    /** @var array<int, string> kolom yang disembunyikan (COLUMN_NAME upper). */
    public array $hiddenColumns = [];

    public bool $showColumnChooser = false;

    /** @var array<int, string> key baris terpilih (untuk clone). */
    public array $selected = [];

    public function mount(string $appCode, string $pageCode): void
    {
        $this->appCode = $appCode;
        $this->pageCode = $pageCode;
    }

    public function updatedSearch(): void
    {
        $this->pageNo = 1;
    }

    /** Reset ke halaman 1 setiap kali filter per-kolom berubah. */
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

    public function delete(string $key, RuntimeSession $session, DatasetEngineInterface $dataset, SecurityEngineInterface $security, AuditEngineInterface $audit): void
    {
        $package = $session->boot($this->appCode);
        $context = $session->context($package->applicationId());
        $page = $session->kernel()->pageByCode($package->applicationId(), $this->pageCode);

        if ($page === null || $page['datasetId'] === null) {
            return;
        }

        if ($security->accessLevel($context, SecurityEngineInterface::OBJ_PAGE, (string) ($page['id'] ?? '')) !== SecurityEngineInterface::LEVEL_FULL) {
            session()->flash('odaf.status', 'Akses hanya-baca: menghapus data tidak diizinkan.');

            return;
        }

        $dataset->delete($context, $page['datasetId'], $key);

        session()->flash('odaf.status', 'Data berhasil dihapus.');
    }

    /**
     * Clone (duplikat) baris terpilih menjadi record baru berstatus DRAFT
     * milik pengguna aktif. Setiap clone dicatat ke audit history.
     */
    public function cloneSelected(
        RuntimeSession $session,
        DatasetEngineInterface $dataset,
        SecurityEngineInterface $security,
        AuditEngineInterface $audit,
    ): void {
        if ($this->selected === []) {
            return;
        }

        $package = $session->boot($this->appCode);
        $context = $session->context($package->applicationId());
        $page = $session->kernel()->pageByCode($package->applicationId(), $this->pageCode);

        if ($page === null || $page['datasetId'] === null) {
            return;
        }

        $datasetId = $page['datasetId'];

        if (! in_array($security->accessLevel($context, SecurityEngineInterface::OBJ_PAGE, (string) ($page['id'] ?? '')), [SecurityEngineInterface::LEVEL_FULL, SecurityEngineInterface::LEVEL_APPEND], true)) {
            session()->flash('odaf.status', 'Akses ditolak: clone tidak diizinkan.');

            return;
        }

        $cloned = 0;
        foreach ($this->selected as $key) {
            try {
                $newKey = $dataset->cloneRow($context, $datasetId, (string) $key);
                $cloned++;
            } catch (\Throwable $e) {
                $this->addError('grid', 'Clone gagal: ' . $e->getMessage());
            }
        }

        $this->selected = [];
        $this->pageNo = 1;

        if ($cloned > 0) {
            session()->flash('odaf.status', "{$cloned} data di-clone sebagai DRAFT (hanya Anda yang dapat melihatnya sampai diset PUBLISHED).");
        }
    }

    public function performWorkflow(
        string $key,
        string $action,
        RuntimeSession $session,
        \Odaf\Engine\Workflow\Contracts\WorkflowEngineInterface $workflow,
    ): void {
        $package = $session->boot($this->appCode);
        $context = $session->context($package->applicationId());
        $page = $session->kernel()->pageByCode($package->applicationId(), $this->pageCode);
        if ($page === null || $page['datasetId'] === null) {
            abort(404);
        }

        try {
            $state = $workflow->perform($context, $page['datasetId'], $key, $action, null);
            session()->flash('odaf.status', "Workflow: {$state->stateName()}.");
        } catch (\Odaf\Engine\Workflow\Contracts\WorkflowException $e) {
            $this->addError('workflow', $e->getMessage());
        }
    }


    public function performBulkWorkflow(
        string $action,
        RuntimeSession $session,
        \Odaf\Engine\Workflow\Contracts\WorkflowEngineInterface $workflow,
    ): void {
        if ($this->selected === []) {
            return;
        }

        $package = $session->boot($this->appCode);
        $context = $session->context($package->applicationId());
        $page = $session->kernel()->pageByCode($package->applicationId(), $this->pageCode);
        if ($page === null || $page['datasetId'] === null) {
            abort(404);
        }

        $successCount = 0;
        foreach ($this->selected as $key) {
            try {
                $workflow->perform($context, $page['datasetId'], (string) $key, $action, null);
                $successCount++;
            } catch (\Odaf\Engine\Workflow\Contracts\WorkflowException $e) {
                $this->addError('workflow', "Aksi gagal pada baris $key: " . $e->getMessage());
            }
        }

        if ($successCount > 0) {
            session()->flash('odaf.status', "Aksi '$action' berhasil dijalankan untuk {$successCount} baris data.");
            $this->selected = [];
        }
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

    public function render(RuntimeSession $session, DatasetEngineInterface $dataset, SecurityEngineInterface $security, LovEngineInterface $lov, \Odaf\Engine\Workflow\Contracts\WorkflowEngineInterface $workflow)
    {
        $package = $session->boot($this->appCode);
        $kernel = $session->kernel();
        $context = $session->context($package->applicationId());

        $page = $kernel->pageByCode($package->applicationId(), $this->pageCode);
        if ($page === null || $page['datasetId'] === null) {
            abort(404, "Halaman grid tidak ditemukan: {$this->pageCode}");
        }

        $criteria = [];


        // Pencarian global (kolom teks pertama).
        if ($this->search !== '') {
            $firstText = collect($page['fields'])->firstWhere(
                fn (array $f): bool => in_array(strtoupper((string) $f['dataType']), ['STRING'], true),
            );
            if ($firstText !== null) {
                $criteria[$firstText['column']] = $this->search;
            }
        }

        // Filter per-kolom (mengisi criteria; engine memakai LIKE untuk teks).
        foreach ($this->filters as $col => $val) {
            if ($val !== '' && $val !== null) {
                $criteria[strtoupper((string) $col)] = $val;
            }
        }

        if ($this->sortColumn !== '') {
            $criteria['_sort'] = $this->sortColumn;
            $criteria['_dir'] = $this->sortDir;
        }

        $result = $dataset->query($context, $page['datasetId'], $criteria, $this->pageNo, $this->pageSize);

        // Kolom grid = field terlihat sesuai field-level security.
        $fieldIds = array_map(static fn (array $f): string => (string) $f['id'], $page['fields']);
        $visible = $security->visibleFields($context, $fieldIds);
        $allColumns = array_values(array_filter(
            $page['fields'],
            static fn (array $f): bool => in_array((string) $f['id'], $visible, true),
        ));

        // Field-level access (SEC_ACCESS): buang kolom NONE, tandai kolom MASKED.
        $fieldLevels = $security->accessLevels($context, SecurityEngineInterface::OBJ_FIELD, $fieldIds);
        $allColumns = array_values(array_filter(
            $allColumns,
            static fn (array $f): bool => ($fieldLevels[strtoupper((string) $f['id'])] ?? SecurityEngineInterface::LEVEL_FULL) !== SecurityEngineInterface::LEVEL_NONE,
        ));
        $maskedColumns = [];
        foreach ($allColumns as $f) {
            if (($fieldLevels[strtoupper((string) $f['id'])] ?? '') === SecurityEngineInterface::LEVEL_MASKED) {
                $maskedColumns[strtoupper((string) $f['column'])] = true;
            }
        }

        $isAdmin = $security->isSuperuser($context);

        // Page status check
        $pageStatus = $page['status'] ?? 'PUBLISHED';
        if (! $isAdmin && $pageStatus !== 'PUBLISHED') {
            abort(403, 'Halaman ini belum dipublikasikan atau tidak tersedia.');
        }

        // Page-level access: NONE -> 403; FULL atau APPEND boleh menulis (tambah baru).
        $pageLevel = $security->accessLevel($context, SecurityEngineInterface::OBJ_PAGE, (string) ($page['id'] ?? ''));
        if ($pageLevel === SecurityEngineInterface::LEVEL_NONE) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
        $canWrite = in_array($pageLevel, [SecurityEngineInterface::LEVEL_FULL, SecurityEngineInterface::LEVEL_APPEND], true);

        // Terapkan pilihan sembunyikan kolom (column chooser) dan status PUBLISHED.
        $columns = array_values(array_filter(
            $allColumns,
            function (array $f) use ($isAdmin): bool {
                if (! $isAdmin && ($f['status'] ?? 'PUBLISHED') !== 'PUBLISHED') {
                    return false;
                }
                return ! in_array(strtoupper((string) $f['column']), $this->hiddenColumns, true);
            },
        ));

        $ds = $kernel->dataset($package->applicationId(), $page['datasetId']);

        $lovLabels = [];
        foreach ($columns as $col) {
            $lovId = ($col['lovId'] ?? '') !== '' ? (string) $col['lovId'] : null;
            if ($lovId !== null) {
                foreach ($lov->options($context, $lovId) as $opt) {
                    $lovLabels[strtoupper((string) $col['column'])][$opt['value']] = $opt['label'];
                }
            }
        }

        $workflowTransitions = [];
        $pkCol = strtoupper((string) ($ds['primaryKey'] ?? ''));
        if ($workflow->hasWorkflow($context, $page['datasetId'])) {
            foreach ($result->rows() as $row) {
                $key = (string) ($row[$pkCol] ?? '');
                if ($key !== '') {
                    $transitions = $workflow->availableTransitions($context, $page['datasetId'], $key);
                    if ($transitions !== []) {
                        $workflowTransitions[$key] = $transitions;
                    }
                }
            }
        }

        $bulkWorkflowTransitions = [];
        if ($this->selected !== [] && $workflow->hasWorkflow($context, $page['datasetId'])) {
            $common = null;
            foreach ($this->selected as $selKey) {
                $transitions = $workflow->availableTransitions($context, $page['datasetId'], (string) $selKey);
                if ($transitions === []) {
                    $common = [];
                    break;
                }
                
                $trMap = [];
                foreach ($transitions as $tr) {
                    $trMap[$tr['action']] = $tr;
                }
                
                if ($common === null) {
                    $common = $trMap;
                } else {
                    foreach (array_keys($common) as $act) {
                        if (!isset($trMap[$act])) {
                            unset($common[$act]);
                        }
                    }
                }
                if ($common === []) {
                    break;
                }
            }
            $bulkWorkflowTransitions = array_values($common ?? []);
        }

        return view('livewire.runtime.dataset-grid', [
            'nav' => $session->navItems($this->appCode, $package->applicationId()),
            'appCode' => $this->appCode,
            'appName' => $package->toArray()['application']['name'] ?? $this->appCode,
            'page' => $page,
            'columns' => $columns,
            'allColumns' => $allColumns,
            'rows' => $result->rows(),
            'total' => $result->total(),
            'lastPage' => $result->lastPage(),
            'primaryKey' => $pkCol,
            'lovLabels' => $lovLabels,
            'maskedColumns' => $maskedColumns,
            'canWrite' => $canWrite,
            'workflowTransitions' => $workflowTransitions,
            'bulkWorkflowTransitions' => $bulkWorkflowTransitions,
            'isAdmin' => $isAdmin,
            'tableName' => $ds['sourceObject'] ?? '',
        ]);
    }
}
