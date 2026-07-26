<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use App\Support\RuntimeSession;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Odaf\Engine\Audit\Contracts\AuditEngineInterface;
use Odaf\Engine\Dataset\Contracts\DatasetEngineInterface;
use Odaf\Engine\Lov\Contracts\LovEngineInterface;
use Odaf\Engine\QrCode\Contracts\QrCodeServiceInterface;
use Odaf\Engine\Render\Contracts\RendererInterface;
use Odaf\Engine\Security\Contracts\SecurityEngineInterface;
use Odaf\Engine\Validation\Contracts\ValidationEngineInterface;
use Odaf\Engine\Workflow\Contracts\WorkflowEngineInterface;
use Odaf\Engine\Workflow\Contracts\WorkflowException;

/**
 * Form generik (create/update) yang digenerate dari package terkompilasi.
 *
 * Alur simpan mematuhi urutan Vol.1 Bab 09: authorize -> validate -> persist
 * -> audit. Bila dataset diatur workflow (BB-06), instance dimulai saat create
 * dan aksi transisi (submit/approve/reject) tersedia saat edit.
 */
#[Layout('layouts.odaf')]
final class DatasetForm extends Component
{
    use WithFileUploads;

    /** Zona waktu lokal untuk nilai default tanggal (SYSDATE) pada field buatan. */
    private const LOCAL_TZ = 'Asia/Jakarta';

    public string $appCode = '';

    public string $pageCode = '';

    public ?string $key = null;

    /** @var array<string, mixed> nilai form berkunci COLUMN_NAME */
    public array $form = [];

    public bool $isEdit = false;

    /** Komentar untuk aksi workflow (approve/reject). */
    public string $workflowComment = '';

    public function mount(string $appCode, string $pageCode, RuntimeSession $session, DatasetEngineInterface $dataset, SecurityEngineInterface $security, ?string $key = null): void
    {
        $this->appCode = $appCode;
        $this->pageCode = $pageCode;
        $this->key = $key;
        $this->isEdit = $key !== null;

        $package = $session->boot($appCode);
        $page = $session->kernel()->pageByCode($package->applicationId(), $pageCode);
        if ($page === null || $page['datasetId'] === null) {
            abort(404, "Halaman form tidak ditemukan: {$pageCode}");
        }

        // Akses halaman NONE -> tolak (defense-in-depth; menu juga disembunyikan).
        if ($security->accessLevel($session->context($package->applicationId()), SecurityEngineInterface::OBJ_PAGE, (string) ($page['id'] ?? '')) === SecurityEngineInterface::LEVEL_NONE) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Inisialisasi nilai default dari metadata field (termasuk SYSDATE).
        foreach ($page['fields'] as $field) {
            $col = strtoupper((string) $field['column']);
            $this->form[$col] = $this->defaultFormValue($field);
        }

        if ($this->isEdit) {
            $context = $session->context($package->applicationId());
            $record = $dataset->find($context, $page['datasetId'], $key);
            if ($record === null) {
                abort(404, 'Data tidak ditemukan.');
            }
            foreach ($page['fields'] as $field) {
                $col = strtoupper((string) $field['column']);
                $this->form[$col] = $this->castFieldForForm($field, $record[$col] ?? null);
            }
            // Simpan VERSION_NO untuk optimistic locking bila ada.
            if (array_key_exists('VERSION_NO', $record)) {
                $this->form['VERSION_NO'] = $record['VERSION_NO'];
            }
            if (array_key_exists(strtoupper((string) ($this->datasetPk($session, $package->applicationId(), $page['datasetId']))), $record)) {
                // biarkan key dari argumen
            }
        }
    }

    public function save(
        RuntimeSession $session,
        DatasetEngineInterface $dataset,
        ValidationEngineInterface $validation,
        SecurityEngineInterface $security,
        AuditEngineInterface $audit,
        WorkflowEngineInterface $workflow,
    ): void {
        $package = $session->boot($this->appCode);
        $kernel = $session->kernel();
        $context = $session->context($package->applicationId());

        $page = $kernel->pageByCode($package->applicationId(), $this->pageCode);
        if ($page === null || $page['datasetId'] === null) {
            abort(404);
        }
        $isAdmin = $security->isSuperuser($context);

        // Page status check
        $pageStatus = $page['status'] ?? 'PUBLISHED';
        if (! $isAdmin && $pageStatus !== 'PUBLISHED') {
            $this->addError('form', 'Halaman ini belum dipublikasikan atau tidak tersedia.');
            return;
        }

        $datasetId = $page['datasetId'];
        $operation = $this->isEdit ? 'UPDATE' : 'CREATE';

        // 1. Authorize (akses halaman berbutir-halus).
        $pageLevel = $security->accessLevel($context, SecurityEngineInterface::OBJ_PAGE, (string) ($page['id'] ?? ''));
        if ($this->isEdit && $pageLevel !== SecurityEngineInterface::LEVEL_FULL) {
            $this->addError('form', 'Akses hanya-baca: perubahan tidak dapat disimpan.');

            return;
        }
        if (! $this->isEdit && ! in_array($pageLevel, [SecurityEngineInterface::LEVEL_FULL, SecurityEngineInterface::LEVEL_APPEND], true)) {
            $this->addError('form', 'Akses ditolak: tidak dapat membuat data.');

            return;
        }

        // 2. Validate (metadata-driven).
        $rules = [];
        $messages = [];
        foreach ($page['fields'] as $f) {
            $col = strtoupper((string) $f['column']);
            if (isset($f['fieldType']) && strtoupper((string) $f['fieldType']) === 'PHOTO') {
                if (($this->form[$col] ?? null) instanceof \Illuminate\Http\UploadedFile) {
                    $rules["form.$col"] = 'image|max:10240';
                    $messages["form.$col.image"] = "File " . ($f['label'] ?? $col) . " harus berupa gambar.";
                    $messages["form.$col.max"] = "Ukuran file " . ($f['label'] ?? $col) . " maksimal 10MB.";
                }
            }
        }
        if (!empty($rules)) {
            $this->validate($rules, $messages);
        }

        $payload = $this->writablePayload($page['fields']);
        // Kolom label pendamping LOV (mis. CUSTGROUPDESC) ikut disimpan walau
        // field-nya readonly — nilainya berasal dari sinkronisasi label saat render.
        foreach ($page['fields'] as $f) {
            $target = $f['lovLabelColumn'] ?? null;
            if ($target !== null && ($f['lovId'] ?? null) !== null) {
                $payload[strtoupper($target)] = $this->form[strtoupper($target)] ?? null;
            }
        }

        // Jangan tulis field yang dibatasi akses (NONE/MASKED/READONLY per role).
        $fieldLevels = $security->accessLevels(
            $context,
            SecurityEngineInterface::OBJ_FIELD,
            array_map(static fn (array $f): string => (string) $f['id'], $page['fields']),
        );
        foreach ($page['fields'] as $f) {
            $lvl = $fieldLevels[strtoupper((string) $f['id'])] ?? SecurityEngineInterface::LEVEL_FULL;
            if ($lvl !== SecurityEngineInterface::LEVEL_FULL) {
                unset($payload[strtoupper((string) $f['column'])]);
            }
        }

        if ($this->isEdit) {
            $pk = strtoupper((string) $this->datasetPk($session, $package->applicationId(), $datasetId));
            $payload[$pk] = $this->key;
        }
        $result = $validation->validate($context, $datasetId, $payload, $operation);
        if ($result->fails()) {
            foreach ($result->errors() as $column => $messages) {
                foreach ($messages as $message) {
                    $this->addError('form.'.strtoupper((string) $column), $message);
                }
            }

            return;
        }

        // 3. Persist.
        if ($this->isEdit) {
            $update = $payload;
            unset($update[strtoupper((string) $this->datasetPk($session, $package->applicationId(), $datasetId))]);
            if (isset($this->form['VERSION_NO'])) {
                $update['VERSION_NO'] = $this->form['VERSION_NO'];
            }
            $dataset->update($context, $datasetId, (string) $this->key, $update);
            $savedKey = (string) $this->key;
            $eventType = 'DATA_UPDATE';
        } else {
            $savedKey = $dataset->create($context, $datasetId, $payload);
            $eventType = 'DATA_CREATE';
        }

        // 4. Audit.
        // Audit is now handled automatically by DatasetEngine

        // 5. Workflow: mulai instance saat record baru dibuat (bila diatur workflow).
        if (! $this->isEdit && $workflow->hasWorkflow($context, $datasetId)) {
            $workflow->start($context, $datasetId, $savedKey);
        }

        session()->flash('odaf.status', $this->isEdit ? 'Data berhasil diperbarui.' : 'Data berhasil dibuat.');

        // Setelah create dengan workflow, arahkan ke form edit agar aksi tersedia.
        if (! $this->isEdit && $workflow->hasWorkflow($context, $datasetId)) {
            $this->redirectRoute('odaf.form', ['appCode' => $this->appCode, 'pageCode' => $this->pageCode, 'key' => $savedKey], navigate: true);

            return;
        }

        $this->redirectRoute('odaf.grid', ['appCode' => $this->appCode, 'pageCode' => $this->pageCode], navigate: true);
    }

    /**
     * Hook Livewire: dipanggil setiap property berubah.
     * Sinkronisasi kolom label pendamping LOV (mis. CUSTGROUPDESC) saat dropdown
     * LOV dipilih — sehingga deskripsi langsung terisi tanpa perlu round-trip penuh.
     */
    public function updated(string $property, $value): void
    {
        // Hanya proses field form.* (bukan workflow, dll).
        if (! str_starts_with($property, 'form.')) {
            return;
        }

        $column = strtoupper(substr($property, 5)); // "form.CUSTGROUP" -> "CUSTGROUP"

        // Resolusi lazy: cek apakah kolom ini punya LOV + companion target.
        try {
            $session = app(RuntimeSession::class);
            $lov = app(LovEngineInterface::class);

            $package = $session->boot($this->appCode);
            $kernel = $session->kernel();
            $context = $session->context($package->applicationId());
            $page = $kernel->pageByCode($package->applicationId(), $this->pageCode);

            if ($page === null) {
                return;
            }

            foreach ($page['fields'] as $field) {
                if (strtoupper((string) $field['column']) !== $column) {
                    continue;
                }
                $lovId = $field['lovId'] ?? null;
                $target = $field['lovLabelColumn'] ?? null;
                if ($lovId === null || $target === null) {
                    break; // field ditemukan tapi bukan LOV atau tidak punya companion
                }

                // Resolusi label dari nilai terpilih.
                if ($value !== null && $value !== '') {
                    $label = $lov->label($context, (string) $lovId, (string) $value, $this->form);
                    if ($label !== null) {
                        $this->form[strtoupper($target)] = $label;
                    }
                } else {
                    // Kosongkan companion bila LOV di-clear.
                    $this->form[strtoupper($target)] = null;
                }

                break;
            }
            
            $this->evaluateCalculations($page['fields']);
        } catch (\Throwable) {
            // Suppress error — ini optimization, tidak boleh menggagalkan update.
        }
    }

    /**
     * Evaluasi formula kalkulasi matematika pada runtime.
     * Menggunakan regex sanitasi ketat untuk menghindari eksekusi script arbitrary.
     */
    private function evaluateCalculations(array $fields): void
    {
        $hasChanges = false;
        
        foreach ($fields as $field) {
            $config = is_array($field['config'] ?? null) ? $field['config'] : [];
            $calc = $config['calculation'] ?? null;
            
            if ($calc === null || trim((string) $calc) === '') {
                continue;
            }
            
            $colName = strtoupper((string) $field['column']);
            $formula = strtoupper((string) $calc);
            
            // Resolusi token variabel [NAMA_KOLOM] dengan nilainya
            $formula = preg_replace_callback('/\[([A-Z0-9_]+)\]/', function($m) {
                // Kosongkan koma (pemisah ribuan) jika ada sisa
                $raw = $this->form[$m[1]] ?? 0;
                if (is_string($raw)) {
                    $raw = str_replace(',', '', $raw);
                }
                return (float) (is_numeric($raw) ? $raw : 0);
            }, $formula);
            
            // Sanitasi mutlak: hanya izinkan angka, desimal, dan operasi matematika (+, -, *, /)
            $clean = preg_replace('/[^0-9\.\+\-\*\/\(\)\s]/', '', $formula);
            
            if (empty(trim($clean))) {
                continue;
            }
            
            try {
                // @codingStandardsIgnoreStart
                $result = eval("return $clean;");
                // @codingStandardsIgnoreEnd
                
                if (is_numeric($result)) {
                    $decimals = $config['decimals'] ?? null;
                    if ($decimals !== null && is_numeric($decimals)) {
                        $result = round((float) $result, (int) $decimals);
                    }
                    
                    if (($this->form[$colName] ?? null) !== $result) {
                        $this->form[$colName] = $result;
                        $hasChanges = true;
                    }
                }
            } catch (\Throwable $e) {
                // Abaikan kesalahan evaluasi (mis. divide by zero)
            }
        }
        
        // Re-evaluasi 1 level kedalaman untuk mendukung kalkulasi berantai sederhana (A -> B -> C)
        static $depth = 0;
        if ($hasChanges && $depth < 1) {
            $depth++;
            $this->evaluateCalculations($fields);
            $depth--;
        }
    }

    /**
     * Jalankan aksi transisi workflow (submit/approve/reject) pada record aktif.
     */
    public function performWorkflow(
        string $action,
        RuntimeSession $session,
        WorkflowEngineInterface $workflow,
    ): void {
        if (! $this->isEdit || $this->key === null) {
            return;
        }

        $package = $session->boot($this->appCode);
        $context = $session->context($package->applicationId());
        $page = $session->kernel()->pageByCode($package->applicationId(), $this->pageCode);
        if ($page === null || $page['datasetId'] === null) {
            abort(404);
        }

        try {
            $state = $workflow->perform($context, $page['datasetId'], (string) $this->key, $action, $this->workflowComment ?: null);
            $this->workflowComment = '';
            session()->flash('odaf.status', "Workflow: {$state->stateName()}.");
        } catch (WorkflowException $e) {
            $this->addError('workflow', $e->getMessage());
        }
    }

    public function render(
        RuntimeSession $session,
        RendererInterface $renderer,
        SecurityEngineInterface $security,
        WorkflowEngineInterface $workflow,
        LovEngineInterface $lov,
    ) {
        $package = $session->boot($this->appCode);
        $kernel = $session->kernel();
        $context = $session->context($package->applicationId());
        $page = $kernel->pageByCode($package->applicationId(), $this->pageCode);
        if ($page === null) {
            abort(404);
        }

        $isAdmin = $security->isSuperuser($context);

        // Filter fields by status
        $page['fields'] = array_values(array_filter(
            $page['fields'],
            function (array $f) use ($isAdmin): bool {
                return $isAdmin || ($f['status'] ?? 'PUBLISHED') === 'PUBLISHED';
            }
        ));

        // View-model presentasi dari renderer, nilai diisi dari state form.
        $viewModel = $renderer->renderPage($context, $page, $this->form);

        // Field-level security: sembunyikan field yang tidak boleh dilihat.
        $visible = $security->visibleFields(
            $context,
            array_map(static fn (array $f): string => (string) $f['id'], $page['fields']),
        );
        $viewModel['fields'] = array_values(array_filter(
            $viewModel['fields'],
            static fn (array $f): bool => in_array((string) $f['id'], $visible, true),
        ));

        // Access-level (SEC_ACCESS): buang NONE, kunci READONLY, samarkan MASKED.
        $fieldIds = array_map(static fn (array $f): string => (string) $f['id'], $viewModel['fields']);
        $fieldLevels = $security->accessLevels($context, SecurityEngineInterface::OBJ_FIELD, $fieldIds);
        $pageLevel = $security->accessLevel($context, SecurityEngineInterface::OBJ_PAGE, (string) ($page['id'] ?? ''));
        
        $canSave = ($this->isEdit && $pageLevel === SecurityEngineInterface::LEVEL_FULL) 
                || (! $this->isEdit && in_array($pageLevel, [SecurityEngineInterface::LEVEL_FULL, SecurityEngineInterface::LEVEL_APPEND], true));

        $viewModel['fields'] = array_values(array_filter(
            $viewModel['fields'],
            static fn (array $f): bool => ($fieldLevels[strtoupper((string) $f['id'])] ?? SecurityEngineInterface::LEVEL_FULL) !== SecurityEngineInterface::LEVEL_NONE,
        ));

        $viewModel['fields'] = array_map(function (array $f) use ($fieldLevels, $canSave): array {
            $lvl = $fieldLevels[strtoupper((string) $f['id'])] ?? SecurityEngineInterface::LEVEL_FULL;
            if (! $canSave || $lvl === SecurityEngineInterface::LEVEL_READONLY) {
                $f['readonly'] = true;
            }
            if ($lvl === SecurityEngineInterface::LEVEL_MASKED) {
                $f['readonly'] = true;
                $f['masked'] = true;
                $f['value'] = null;
                // Jangan kirim nilai rahasia ke browser.
                $this->form[strtoupper((string) $f['column'])] = null;
            }

            return $f;
        }, $viewModel['fields']);

        // Kolom yang menjadi parameter LOV ({{TOKEN}} pada query LOV) -> ditandai
        // agar field-nya reaktif (blur) sehingga dropdown dependen ikut ter-refresh.
        $paramColumns = [];
        foreach ($page['fields'] as $f) {
            $lid = $f['lovId'] ?? null;
            if ($lid !== null) {
                $def = $kernel->lov($package->applicationId(), (string) $lid);
                $src = $def['sourceQuery'] ?? null;
                if ($src !== null && preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', (string) $src, $m) > 0) {
                    foreach ($m[1] as $tok) {
                        $paramColumns[strtoupper($tok)] = true;
                    }
                }
            }
            
            // Tangkap juga variabel yang digunakan dalam perhitungan matematika
            $config = is_array($f['config'] ?? null) ? $f['config'] : [];
            $calc = $config['calculation'] ?? null;
            if ($calc !== null && preg_match_all('/\[([A-Za-z0-9_]+)\]/', (string) $calc, $m) > 0) {
                foreach ($m[1] as $tok) {
                    $paramColumns[strtoupper($tok)] = true;
                }
            }
        }

        // LOV: muat opsi dropdown (parametrik dari nilai form saat ini) untuk
        // field yang mereferensi LOV, dan sinkronkan kolom label pendamping.
        $viewModel['fields'] = array_map(function (array $field) use ($lov, $context, $paramColumns): array {
            $field['isLovParam'] = isset($paramColumns[strtoupper((string) $field['column'])]);

            if (($field['lovId'] ?? null) === null) {
                return $field;
            }
            $lovId = (string) $field['lovId'];
            $field['options'] = $lov->options($context, $lovId, $this->form);

            // Companion: isi kolom label (mis. CUSTGROUPDESC) dari nilai terpilih.
            $target = $field['lovLabelColumn'] ?? null;
            $selected = $this->form[$field['column']] ?? null;
            if ($target !== null && $selected !== null && $selected !== '') {
                $label = $lov->label($context, $lovId, (string) $selected, $this->form);
                if ($label !== null) {
                    $this->form[$target] = $label;
                }
            }

            return $field;
        }, $viewModel['fields']);

        // Workflow (BB-06): status + aksi + riwayat (hanya saat edit).
        $wf = null;
        if ($this->isEdit && $this->key !== null && $workflow->hasWorkflow($context, $page['datasetId'])) {
            $state = $workflow->currentState($context, $page['datasetId'], (string) $this->key);
            $wf = [
                'stateCode' => $state?->stateCode(),
                'stateName' => $state?->stateName(),
                'status' => $state?->instanceStatus(),
                'final' => $state?->isFinal() ?? false,
                'transitions' => $workflow->availableTransitions($context, $page['datasetId'], (string) $this->key),
                'history' => $workflow->history($context, $page['datasetId'], (string) $this->key),
            ];
        }

        $ds = $kernel->dataset($package->applicationId(), $page['datasetId']);

        // Tab-level security: sembunyikan tab (detail) jika akses page-nya NONE.
        $details = $page['details'] ?? [];
        if (! $isAdmin) {
            $details = array_values(array_filter($details, function(array $detail) use ($security, $context) {
                if (!isset($detail['pageId'])) return true;
                return $security->accessLevel($context, SecurityEngineInterface::OBJ_PAGE, (string) $detail['pageId']) !== SecurityEngineInterface::LEVEL_NONE;
            }));
        }

        // QR Code: generate dari QR_CONFIG terkompilasi (hanya saat edit).
        $qrCodes = [];
        if ($this->isEdit && $this->key !== null) {
            $qrConfigs = $page['qrConfigs'] ?? [];
            if (! empty($qrConfigs)) {
                try {
                    $qrService = app(QrCodeServiceInterface::class);
                    foreach ($qrConfigs as $qrConfig) {
                        $showOnForm = (bool) ($qrConfig['showOnForm'] ?? true);
                        if (! $showOnForm) {
                            continue;
                        }
                        $qrData = $qrService->buildFromConfig($qrConfig, (string) $this->key, $this->form);
                        if ($qrData !== null) {
                            $qrCodes[] = $qrData;
                        }
                    }
                } catch (\Throwable $e) {
                    // QR Code generation failure should not break form rendering.
                    logger()->warning('ODAF QR Code generation failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return view('livewire.runtime.dataset-form', [
            'nav' => $session->navItems($this->appCode, $package->applicationId()),
            'appCode' => $this->appCode,
            'appName' => $package->toArray()['application']['name'] ?? $this->appCode,
            'vm' => $viewModel,
            'wf' => $wf,
            'details' => $details,
            'canSave' => $canSave,
            'isAdmin' => $isAdmin,
            'tableName' => $ds['sourceObject'] ?? '',
            'qrCodes' => $qrCodes,
            'rptTemplates' => $page['rptTemplates'] ?? [],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function writablePayload(array $fields): array
    {
        $payload = [];
        foreach ($fields as $field) {
            if ((bool) ($field['readonly'] ?? false)) {
                continue;
            }
            $col = strtoupper((string) $field['column']);
            $val = $this->form[$col] ?? null;

            if ($val instanceof \Illuminate\Http\UploadedFile) {
                $ext = strtolower($val->getClientOriginalExtension() ?: 'tmp');
                // Path: ext / aplikasi / menu
                $path = "{$ext}/" . strtolower($this->appCode) . "/" . strtolower($this->pageCode);
                $val = $val->store($path, 'public');
            }

            // CHECKBOX disimpan sebagai 1/0 ke kolom NUMBER.
            if (strtoupper((string) ($field['fieldType'] ?? '')) === 'CHECKBOX') {
                $val = $this->boolToDb($val);
            }

            $payload[$col] = $val;
        }

        return $payload;
    }

    /**
     * Cast nilai kolom dari DB ke bentuk yang cocok untuk binding form.
     * CHECKBOX -> boolean sejati agar status centang sesuai nilai (bukan string "0"
     * yang truthy di JavaScript).
     *
     * @param  array<string, mixed>  $field
     */
    private function castFieldForForm(array $field, mixed $raw): mixed
    {
        $type = strtoupper((string) ($field['fieldType'] ?? ''));

        if ($type === 'CHECKBOX') {
            return in_array(strtoupper((string) $raw), ['1', 'Y', 'YES', 'TRUE', 'T'], true);
        }

        // DATE/DATETIME: format nilai dari DB ke format input HTML.
        if (in_array($type, ['DATE', 'DATETIME'], true)) {
            return $this->formatDateForInput($field, $raw);
        }

        return $raw;
    }

    /**
     * Nilai awal saat CREATE (belum ada record). Menangani default SYSDATE.
     *
     * @param  array<string, mixed>  $field
     */
    private function defaultFormValue(array $field): mixed
    {
        $type = strtoupper((string) ($field['fieldType'] ?? ''));
        $config = is_array($field['config'] ?? null) ? $field['config'] : [];

        if (in_array($type, ['DATE', 'DATETIME'], true)) {
            if (strtoupper((string) ($config['default'] ?? '')) === 'SYSDATE') {
                $withTime = (bool) ($config['withTime'] ?? ($type === 'DATETIME'));
                // Waktu lokal (WIB) agar sesuai jam pengguna, bukan UTC server.
                $nowLocal = \Illuminate\Support\Carbon::now(self::LOCAL_TZ);

                return $nowLocal->format($withTime ? 'Y-m-d\TH:i' : 'Y-m-d');
            }

            return $field['defaultValue'] ?? null;
        }

        return $this->castFieldForForm($field, $field['defaultValue'] ?? null);
    }

    /**
     * Format nilai tanggal dari DB ke format input HTML (date / datetime-local).
     *
     * @param  array<string, mixed>  $field
     */
    private function formatDateForInput(array $field, mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $config = is_array($field['config'] ?? null) ? $field['config'] : [];
        $withTime = (bool) ($config['withTime'] ?? (strtoupper((string) ($field['fieldType'] ?? '')) === 'DATETIME'));

        try {
            $dt = \Illuminate\Support\Carbon::parse((string) $raw);

            return $dt->format($withTime ? 'Y-m-d\TH:i' : 'Y-m-d');
        } catch (\Throwable) {
            return (string) $raw;
        }
    }

    /**
     * Normalisasi nilai checkbox (bool / string / int) menjadi 1 atau 0.
     */
    private function boolToDb(mixed $val): int
    {
        if (is_bool($val)) {
            return $val ? 1 : 0;
        }

        return in_array(strtoupper((string) $val), ['1', 'TRUE', 'ON', 'Y', 'YES', 'T'], true) ? 1 : 0;
    }

    private function datasetPk(RuntimeSession $session, string $applicationId, string $datasetId): string
    {
        $ds = $session->kernel()->dataset($applicationId, $datasetId);

        return (string) ($ds['primaryKey'] ?? '');
    }
}
