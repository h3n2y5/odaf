<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * QR Code Config Designer — konfigurasi QR Code per form/page.
 *
 * Memungkinkan admin mengatur QR Code pada sebuah form: tipe QR,
 * target dokumen (cross-document), aksi workflow, posisi, ukuran,
 * dan visibilitas di form/print.
 *
 * Route: /studio/designer/qr/{pageId}
 */
#[Layout('layouts.odaf')]
final class QrConfigDesigner extends Component
{
    use NormalizesRows;

    public string $pageId;

    /** @var array<string, mixed> */
    public array $page = [];

    /** @var array<int, array<string, mixed>> */
    public array $qrConfigs = [];

    /** Editor state. */
    public bool $showEditor = false;

    /** @var array<string, mixed> */
    public array $editor = [
        'id' => null,
        'code' => '',
        'name' => '',
        'qr_type' => 'DOCUMENT_LINK',
        'target_app_code' => '',
        'target_page_code' => '',
        'target_action' => '',
        'fk_column' => '',
        'position' => 'TOP_RIGHT',
        'size_px' => 150,
        'show_on_form' => 1,
        'show_on_print' => 1,
    ];

    /** @var array<int, array<string, mixed>> */
    public array $availablePages = [];

    /** @var array<int, array<string, mixed>> */
    public array $availableWorkflowActions = [];

    public function mount(StudioAccess $access, string $pageId): void
    {
        $access->ensureAdmin();
        $this->pageId = strtoupper($pageId);
        $this->loadPage();
        $this->loadQrConfigs();
        $this->loadAvailablePages();
    }

    /**
     * Buka editor untuk tambah QR baru.
     */
    public function addNew(): void
    {
        $this->resetEditor();
        $this->editor['code'] = 'QR_' . strtoupper(substr(md5(uniqid()), 0, 6));
        $this->editor['name'] = 'QR Code ' . ($this->page['OBJECT_NAME'] ?? '');
        $this->showEditor = true;
    }

    /**
     * Buka editor untuk edit QR yang ada.
     */
    public function edit(string $qrId): void
    {
        $row = DB::selectOne(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.PAGE_ID) AS PAGE_ID
             FROM QR_CONFIG t WHERE t.OBJECT_ID = HEXTORAW(?)',
            [strtoupper($qrId)],
        );
        if ($row === null) {
            return;
        }
        $row = $this->normalizeRow($row);

        $this->editor = [
            'id' => $row['OBJECT_ID'],
            'code' => $row['OBJECT_CODE'],
            'name' => $row['OBJECT_NAME'],
            'qr_type' => $row['QR_TYPE'] ?? 'DOCUMENT_LINK',
            'target_app_code' => $row['TARGET_APP_CODE'] ?? '',
            'target_page_code' => $row['TARGET_PAGE_CODE'] ?? '',
            'target_action' => $row['TARGET_ACTION'] ?? '',
            'fk_column' => $row['FK_COLUMN'] ?? '',
            'position' => $row['POSITION'] ?? 'TOP_RIGHT',
            'size_px' => (int) ($row['SIZE_PX'] ?? 150),
            'show_on_form' => (int) ($row['SHOW_ON_FORM'] ?? 1),
            'show_on_print' => (int) ($row['SHOW_ON_PRINT'] ?? 1),
        ];

        $this->loadWorkflowActions();
        $this->showEditor = true;
    }

    /**
     * Simpan QR config (create/update).
     */
    public function save(): void
    {
        if (trim($this->editor['code']) === '' || trim($this->editor['name']) === '') {
            $this->addError('editor', 'Kode dan Nama wajib diisi.');
            return;
        }

        if ($this->editor['id'] !== null) {
            // Update existing.
            DB::update(
                'UPDATE QR_CONFIG SET
                    OBJECT_NAME = ?, QR_TYPE = ?, TARGET_APP_CODE = ?, TARGET_PAGE_CODE = ?,
                    TARGET_ACTION = ?, FK_COLUMN = ?, POSITION = ?, SIZE_PX = ?,
                    SHOW_ON_FORM = ?, SHOW_ON_PRINT = ?, STATUS = ?, UPDATED_AT = SYSTIMESTAMP
                 WHERE OBJECT_ID = HEXTORAW(?)',
                [
                    $this->editor['name'],
                    $this->editor['qr_type'],
                    $this->editor['target_app_code'] ?: null,
                    $this->editor['target_page_code'] ?: null,
                    $this->editor['target_action'] ?: null,
                    $this->editor['fk_column'] ?: null,
                    $this->editor['position'],
                    $this->editor['size_px'],
                    $this->editor['show_on_form'],
                    $this->editor['show_on_print'],
                    'PUBLISHED',
                    strtoupper($this->editor['id']),
                ],
            );
        } else {
            // Create new.
            DB::insert(
                'INSERT INTO QR_CONFIG
                    (PAGE_ID, OBJECT_CODE, OBJECT_NAME, QR_TYPE, TARGET_APP_CODE,
                     TARGET_PAGE_CODE, TARGET_ACTION, FK_COLUMN, POSITION, SIZE_PX,
                     SHOW_ON_FORM, SHOW_ON_PRINT, STATUS)
                 VALUES (HEXTORAW(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $this->pageId,
                    $this->editor['code'],
                    $this->editor['name'],
                    $this->editor['qr_type'],
                    $this->editor['target_app_code'] ?: null,
                    $this->editor['target_page_code'] ?: null,
                    $this->editor['target_action'] ?: null,
                    $this->editor['fk_column'] ?: null,
                    $this->editor['position'],
                    $this->editor['size_px'],
                    $this->editor['show_on_form'],
                    $this->editor['show_on_print'],
                    'PUBLISHED',
                ],
            );
        }

        $this->showEditor = false;
        $this->loadQrConfigs();
        session()->flash('odaf.status', 'QR Code config disimpan. Jangan lupa compile ulang!');
    }

    /**
     * Hapus QR config.
     */
    public function delete(string $qrId): void
    {
        DB::delete('DELETE FROM QR_CONFIG WHERE OBJECT_ID = HEXTORAW(?)', [strtoupper($qrId)]);
        $this->loadQrConfigs();
        session()->flash('odaf.status', 'QR Code config dihapus.');
    }

    public function render()
    {
        return view('livewire.studio.designer.qr-config-designer');
    }

    // ---- Private helpers ---------------------------------------------------

    private function loadPage(): void
    {
        $row = DB::selectOne(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.APPLICATION_ID) AS APPLICATION_ID,
                    RAWTOHEX(t.DATASET_ID) AS DATASET_ID
             FROM UI_PAGE t WHERE t.OBJECT_ID = HEXTORAW(?)',
            [$this->pageId],
        );
        $this->page = $row !== null ? $this->normalizeRow($row) : [];
    }

    private function loadQrConfigs(): void
    {
        $rows = DB::select(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID
             FROM QR_CONFIG t WHERE t.PAGE_ID = HEXTORAW(?) ORDER BY t.OBJECT_CODE',
            [$this->pageId],
        );
        $this->qrConfigs = array_map(fn ($r) => $this->normalizeRow($r), $rows);
    }

    private function loadAvailablePages(): void
    {
        // Semua page dari aplikasi yang sama.
        if (empty($this->page)) {
            return;
        }
        $rows = DB::select(
            'SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, OBJECT_CODE, OBJECT_NAME, PAGE_TYPE
             FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?) ORDER BY OBJECT_CODE',
            [$this->page['APPLICATION_ID'] ?? ''],
        );
        $this->availablePages = array_map(fn ($r) => $this->normalizeRow($r), $rows);
    }

    private function loadWorkflowActions(): void
    {
        if (empty($this->page) || ($this->page['DATASET_ID'] ?? '') === '') {
            $this->availableWorkflowActions = [];
            return;
        }
        $rows = DB::select(
            'SELECT DISTINCT t.ACTION_CODE, t.OBJECT_NAME
             FROM WF_TRANSITION t
             JOIN WF_WORKFLOW w ON w.OBJECT_ID = t.WORKFLOW_ID
             WHERE w.DATASET_ID = HEXTORAW(?)
             ORDER BY t.ACTION_CODE',
            [$this->page['DATASET_ID']],
        );
        $this->availableWorkflowActions = array_map(fn ($r) => $this->normalizeRow($r), $rows);
    }

    private function resetEditor(): void
    {
        $this->editor = [
            'id' => null,
            'code' => '',
            'name' => '',
            'qr_type' => 'DOCUMENT_LINK',
            'target_app_code' => '',
            'target_page_code' => '',
            'target_action' => '',
            'fk_column' => '',
            'position' => 'TOP_RIGHT',
            'size_px' => 150,
            'show_on_form' => 1,
            'show_on_print' => 1,
        ];
    }
}
