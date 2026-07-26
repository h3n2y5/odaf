<?php

declare(strict_types=1);

namespace App\Livewire\Studio;

use App\Support\StudioAccess;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Odaf\Studio\ColumnMapper;
use Odaf\Studio\TableDataManager;
use Odaf\Studio\TableIntrospector;
use Throwable;

/**
 * Form generik create/edit untuk sebuah baris tabel. Field, widget, dan dropdown
 * FK dibangun otomatis dari introspeksi tabel — inilah "form generator" web.
 */
#[Layout('layouts.odaf')]
final class StudioForm extends Component
{
    use WithFileUploads;

    public string $table = '';

    public ?string $key = null;

    public bool $isEdit = false;

    /** @var array<string, mixed> nilai form berkunci nama kolom */
    public array $form = [];

    /** Kolom yang tidak ditampilkan di form (dikelola engine). */
    private const HIDDEN_COLUMNS = [
        'CREATED_AT', 'CREATED_BY', 'UPDATED_AT', 'UPDATED_BY', 'VERSION_NO', 'DELETED_AT', 'DELETED_BY',
    ];

    public function mount(string $table, StudioAccess $access, TableIntrospector $introspector, TableDataManager $manager, ?string $key = null): void
    {
        $access->ensureAdmin();
        $this->table = strtoupper($table);
        $this->key = $key;
        $this->isEdit = $key !== null;

        $schema = $introspector->schema($this->table);

        // Inisialisasi nilai kosong untuk setiap kolom editable.
        foreach ($schema['columns'] as $name => $meta) {
            $this->form[$name] = null;
        }

        if ($this->isEdit) {
            $record = $manager->find($this->table, TableDataManager::decodeKey($key));
            if ($record === null) {
                abort(404, 'Baris tidak ditemukan.');
            }
            foreach ($record as $col => $val) {
                if (strtoupper($col) === 'PASSWORD_HASH') {
                    $this->form[$col] = null;
                } else {
                    $this->form[$col] = $val;
                }
            }
        }
    }

    public function save(StudioAccess $access, TableDataManager $manager, TableIntrospector $introspector): void
    {
        $access->ensureAdmin();

        $rules = [];
        $messages = [];
        $schema = $introspector->schema($this->table);
        foreach ($schema['columns'] as $name => $meta) {
            if (isset($meta['widget']) && $meta['widget'] === 'photo') {
                if ($this->form[$name] instanceof \Illuminate\Http\UploadedFile) {
                    $rules["form.$name"] = 'image|max:10240';
                    $messages["form.$name.image"] = "File " . ColumnMapper::humanize($name) . " harus berupa gambar.";
                    $messages["form.$name.max"] = "Ukuran file " . ColumnMapper::humanize($name) . " maksimal 10MB.";
                }
            }
        }
        if (!empty($rules)) {
            $this->validate($rules, $messages);
        }

        $payload = $this->editablePayload($access, $manager);

        try {
            $verb = $this->isEdit ? 'diperbarui' : 'dibuat';
            if ($this->isEdit) {
                $manager->update($this->table, TableDataManager::decodeKey((string) $this->key), $payload, $access->userId());
            } else {
                $manager->create($this->table, $payload, $access->userId());
            }

            $message = "Baris {$this->table} berhasil {$verb}.";
            if ($this->isCompiledMetadataTable($this->table)) {
                $message .= " Ini metadata terkompilasi — klik \u{26A1} 'Kompilasi & Aktifkan' di kanan atas agar perubahan tampil di aplikasi.";
            }
            session()->flash('studio.status', $message);
        } catch (Throwable $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        $this->redirectRoute('studio.grid', ['table' => $this->table], navigate: true);
    }

    public function render(StudioAccess $access, TableIntrospector $introspector)
    {
        $access->ensureAdmin();

        $schema = $introspector->schema($this->table);
        $fields = [];

        foreach ($schema['columns'] as $name => $meta) {
            if (in_array($name, self::HIDDEN_COLUMNS, true)) {
                continue;
            }
            $isPk = (bool) $meta['isPk'];
            $singleRawPk = $isPk && count($schema['primaryKey']) === 1 && (bool) $meta['isBinary'];

            // PK RAW tunggal digenerate otomatis -> sembunyikan saat create.
            if ($singleRawPk && ! $this->isEdit) {
                continue;
            }

            $fk = $meta['fk'] ?? null;
            $options = $fk !== null
                ? $introspector->fkOptions($fk)
                : ($meta['options'] ?? []);

            $fields[] = [
                'column' => $name,
                'label' => ColumnMapper::humanize($name),
                'widget' => (string) $meta['widget'],
                'required' => ! $meta['nullable'] && ! $isPk,
                'readonly' => $this->isEdit && $isPk,
                'options' => $options,
                'isFk' => $fk !== null,
            ];
        }

        return view('livewire.studio.form', [
            'groups' => $introspector->groupedTables(),
            'table' => $this->table,
            'fields' => $fields,
        ]);
    }

    /**
     * Apakah tabel termasuk metadata terkompilasi (perlu recompile agar aktif
     * di runtime). Tabel RT_, SEC_, SYS_, AUD_ dan tabel bisnis tidak perlu.
     */
    private function isCompiledMetadataTable(string $table): bool
    {
        foreach (['APP_', 'UI_', 'DS_', 'VAL_', 'WF_', 'NTF_'] as $prefix) {
            if (str_starts_with($table, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Payload untuk disimpan: hanya kolom editable (bukan kolom tersembunyi,
     * bukan PK saat edit).
     *
     * @return array<string, mixed>
     */
    private function editablePayload(StudioAccess $access, TableDataManager $manager): array
    {
        $schema = app(TableIntrospector::class)->schema($this->table);
        $payload = [];
        foreach ($schema['columns'] as $name => $meta) {
            if (in_array($name, self::HIDDEN_COLUMNS, true)) {
                continue;
            }
            if ($this->isEdit && $meta['isPk']) {
                continue;
            }
            if (! $this->isEdit && count($schema['primaryKey']) === 1 && $meta['isPk'] && $meta['isBinary']) {
                continue; // PK RAW auto-generate
            }
            if (array_key_exists($name, $this->form)) {
                if (strtoupper($name) === 'PASSWORD_HASH') {
                    if (empty($this->form[$name])) {
                        continue;
                    }
                    $payload[$name] = \Illuminate\Support\Facades\Hash::make($this->form[$name]);
                } elseif (isset($meta['widget']) && in_array($meta['widget'], ['photo', 'document'])) {
                    $val = $this->form[$name];
                    if ($val instanceof \Illuminate\Http\UploadedFile) {
                        $ext = strtolower($val->getClientOriginalExtension() ?: 'tmp');
                        $path = "{$ext}/studio/" . strtolower($this->table);
                        $payload[$name] = $val->store($path, 'public');
                    } else {
                        $payload[$name] = $val;
                    }
                } else {
                    $payload[$name] = $this->form[$name];
                }
            }
        }

        return $payload;
    }
}
