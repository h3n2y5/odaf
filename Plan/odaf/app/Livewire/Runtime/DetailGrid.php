<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use App\Support\RuntimeSession;
use Livewire\Component;
use Odaf\Engine\Audit\Contracts\AuditEngineInterface;
use Odaf\Engine\Dataset\Contracts\DatasetEngineInterface;
use Odaf\Engine\Security\Contracts\SecurityEngineInterface;

/**
 * Grid detail (baris) untuk pola header-detail. Ditanam di dalam form header.
 *
 * Menampilkan baris anak yang berelasi ke header melalui kolom FK, dan
 * memungkinkan tambah/edit/hapus baris satu per satu (line 1, line 2, dst).
 */
final class DetailGrid extends Component
{
    public string $appCode;
    public string $childPageCode;
    public string $fkColumn;
    public string $parentKey;
    public array $parentForm = [];
    public string $title = 'Detail';

    /** @var array<int, array<string, mixed>> field metadata grid (dari page anak). */
    public array $fields = [];

    /** @var array<int, array<string, mixed>> baris data (tiap item: kolom + __pk). */
    public array $lines = [];

    public string $childDatasetId = '';
    public string $childPk = '';

    public bool $isAdmin = false;
    public string $tableName = '';

    public function mount(
        string $appCode,
        string $childPageCode,
        string $fkColumn,
        string $parentKey,
        array $parentForm,
        string $title,
        RuntimeSession $session,
        DatasetEngineInterface $dataset,
        \Odaf\Engine\Lov\Contracts\LovEngineInterface $lov,
    ): void {
        $this->appCode = $appCode;
        $this->childPageCode = $childPageCode;
        $this->fkColumn = strtoupper($fkColumn);
        $this->parentKey = $parentKey;
        $this->parentForm = $parentForm;
        $this->title = $title;

        $package = $session->boot($appCode);
        $kernel = $session->kernel();
        $page = $kernel->pageByCode($package->applicationId(), $childPageCode);
        if ($page === null || ($page['datasetId'] ?? null) === null) {
            return;
        }

        $this->childDatasetId = (string) $page['datasetId'];
        $ds = $kernel->dataset($package->applicationId(), $this->childDatasetId);
        $this->childPk = strtoupper((string) ($ds['primaryKey'] ?? ''));
        $this->tableName = $ds['sourceObject'] ?? '';

        $this->isAdmin = app(\Odaf\Engine\Security\Contracts\SecurityEngineInterface::class)->isSuperuser($session->context($package->applicationId()));
        $isAdmin = $this->isAdmin;

        // Field grid = field page anak, minus STATUS. (fkColumn tidak difilter agar bisa tampil jika user menginginkannya)
        $this->fields = array_values(array_filter(
            $page['fields'],
            function (array $f) use ($isAdmin): bool {
                if (! $isAdmin && ($f['status'] ?? 'PUBLISHED') !== 'PUBLISHED') {
                    return false;
                }
                return strtoupper((string) $f['column']) !== 'STATUS';
            },
        ));

        // Resolusi opsi LOV untuk kolom-kolom grid & set inherited
        $context = $session->context($package->applicationId());
        foreach ($this->fields as &$f) {
            $col = strtoupper((string) $f['column']);
            $lovId = $f['lovId'] ?? null;
            if ($lovId !== null && $lovId !== '') {
                $f['options'] = $lov->options($context, (string) $lovId, []);
            }
            
            // Jika kolom adalah FK, ATAU kolom ada di parentForm, jadikan read-only & auto-inherit
            if ($col === $this->fkColumn || array_key_exists($col, $this->parentForm)) {
                $f['readonly'] = true;
                $f['inherited'] = true;
                $f['inheritSource'] = $col === $this->fkColumn ? 'FK' : 'PARENT';
            }
        }
        unset($f);

        $this->loadLines($session, $dataset);
    }

    public function render()
    {
        return view('livewire.runtime.detail-grid');
    }

    private function loadLines(RuntimeSession $session, DatasetEngineInterface $dataset): void
    {
        if ($this->childDatasetId === '' || $this->parentKey === '') {
            $this->lines = [];
            return;
        }

        $package = $session->boot($this->appCode);
        $context = $session->context($package->applicationId());

        $result = $dataset->query($context, $this->childDatasetId, [$this->fkColumn => $this->parentKey], 1, 500);

        $lines = [];
        foreach ($result->rows() as $row) {
            $line = ['__pk' => $row[$this->childPk] ?? null];
            foreach ($this->fields as $f) {
                $col = strtoupper((string) $f['column']);
                $line[$col] = $this->castForInput($f, $row[$col] ?? null);
            }
            $lines[] = $line;
        }
        $this->lines = $lines;
    }

    public function addLine(): void
    {
        $line = ['__pk' => null];
        foreach ($this->fields as $f) {
            $col = strtoupper((string) $f['column']);
            if ($f['inherited'] ?? false) {
                if (($f['inheritSource'] ?? '') === 'FK') {
                    $line[$col] = $this->parentKey;
                } else {
                    $line[$col] = $this->parentForm[$col] ?? null;
                }
            } else {
                $line[$col] = null;
            }
        }
        $this->lines[] = $line;
    }

    public function removeLine(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $pk = $this->lines[$index]['__pk'] ?? null;
        if ($pk !== null && $pk !== '') {
            $session = app(RuntimeSession::class);
            $dataset = app(DatasetEngineInterface::class);
            $security = app(SecurityEngineInterface::class);
            $audit = app(AuditEngineInterface::class);
            $package = $session->boot($this->appCode);
            $context = $session->context($package->applicationId());
            $page = $session->kernel()->pageByCode($package->applicationId(), $this->childPageCode);
            $pageLevel = $security->accessLevel($context, SecurityEngineInterface::OBJ_PAGE, (string) ($page['id'] ?? ''));
            
            if ($pageLevel !== SecurityEngineInterface::LEVEL_FULL) {
                $this->addError('detail', 'Akses ditolak: hapus baris membutuhkan akses penuh.');
                return;
            }
            try {
                $dataset->delete($context, $this->childDatasetId, (string) $pk);
                $audit->record($context, 'DATA_DELETE', (string) $pk, ['detail' => $this->fkColumn], []);
            } catch (\Throwable $e) {
                $this->addError('detail', 'Hapus baris gagal: ' . $e->getMessage());
                return;
            }
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /** Simpan semua baris (create untuk baru, update untuk yang punya PK). */
    public function saveLines(
        RuntimeSession $session,
        DatasetEngineInterface $dataset,
        SecurityEngineInterface $security,
        AuditEngineInterface $audit,
    ): void {
        if ($this->childDatasetId === '' || $this->parentKey === '') {
            return;
        }

        $package = $session->boot($this->appCode);
        $context = $session->context($package->applicationId());
        $page = $session->kernel()->pageByCode($package->applicationId(), $this->childPageCode);
        $pageLevel = $security->accessLevel($context, SecurityEngineInterface::OBJ_PAGE, (string) ($page['id'] ?? ''));

        $saved = 0;
        foreach ($this->lines as $line) {
            // Lewati baris yang benar-benar kosong (semua field null/'' dan belum ada PK).
            if (($line['__pk'] ?? null) === null && $this->isBlankLine($line)) {
                continue;
            }

            $payload = [$this->fkColumn => $this->parentKey];
            foreach ($this->fields as $f) {
                $col = strtoupper((string) $f['column']);
                if ($f['inherited'] ?? false) {
                    $payload[$col] = $this->parentForm[$col] ?? null;
                } else {
                    $val = $line[$col] ?? null;
                    if (strtoupper((string) ($f['fieldType'] ?? '')) === 'CHECKBOX') {
                        $val = in_array(strtoupper((string) $val), ['1', 'TRUE', 'ON', 'Y', 'YES', 'T'], true) ? 1 : 0;
                    }
                    $payload[$col] = $val;
                }
            }

            try {
                $pk = $line['__pk'] ?? null;
                if ($pk !== null && $pk !== '') {
                    if ($pageLevel !== SecurityEngineInterface::LEVEL_FULL) {
                        $this->addError('detail', 'Akses ditolak: mengubah baris membutuhkan akses penuh.');
                        continue;
                    }
                    $dataset->update($context, $this->childDatasetId, (string) $pk, $payload);
                    $audit->record($context, 'DATA_UPDATE', (string) $pk, null, [], $payload);
                } else {
                    if (! in_array($pageLevel, [SecurityEngineInterface::LEVEL_FULL, SecurityEngineInterface::LEVEL_APPEND], true)) {
                        $this->addError('detail', 'Akses ditolak: menambah baris tidak diizinkan.');
                        continue;
                    }
                    $newKey = $dataset->create($context, $this->childDatasetId, $payload);
                    $audit->record($context, 'DATA_CREATE', $newKey, null, [], $payload);
                }
                $saved++;
            } catch (\Throwable $e) {
                $this->addError('detail', 'Simpan baris gagal: ' . $e->getMessage());
            }
        }

        $this->loadLines($session, $dataset);
        session()->flash('detail.status', "{$saved} baris tersimpan.");
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function isBlankLine(array $line): bool
    {
        foreach ($this->fields as $f) {
            $v = $line[strtoupper((string) $f['column'])] ?? null;
            if ($v !== null && $v !== '' && $v !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function castForInput(array $field, mixed $raw): mixed
    {
        $type = strtoupper((string) ($field['fieldType'] ?? ''));

        if ($type === 'CHECKBOX') {
            return in_array(strtoupper((string) $raw), ['1', 'Y', 'YES', 'TRUE', 'T'], true);
        }
        if (in_array($type, ['DATE', 'DATETIME'], true) && $raw !== null && $raw !== '') {
            try {
                $withTime = (bool) (($field['config']['withTime'] ?? false) || $type === 'DATETIME');
                return \Illuminate\Support\Carbon::parse((string) $raw)->format($withTime ? 'Y-m-d\TH:i' : 'Y-m-d');
            } catch (\Throwable) {
                return $raw;
            }
        }

        return $raw;
    }
}
