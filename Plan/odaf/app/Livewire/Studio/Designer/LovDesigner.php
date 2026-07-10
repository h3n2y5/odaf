<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * LOV Designer - Visual designer untuk DS_LOV (List of Values).
 *
 * Kolom DS_LOV (skema aktual):
 *   OBJECT_ID, OBJECT_CODE, OBJECT_NAME, LOV_TYPE, VALUE_COLUMN, LABEL_COLUMN,
 *   SOURCE_QUERY, DESCRIPTION, VERSION_NO, STATUS, CREATED_AT, UPDATED_AT, ...
 *
 * LOV_TYPE didukung engine: STATIC (JSON), SQL (SELECT), VIEW (nama tabel/view).
 */
#[Layout('layouts.odaf')]
final class LovDesigner extends Component
{
    use NormalizesRows;

    public ?string $lovId = null;
    public bool $isNewLov = true;

    /** @var array<string, mixed> */
    public array $lov = [
        'object_code' => '',
        'object_name' => '',
        'description' => '',
        'lov_type' => 'STATIC',
        'source_query' => '',
        'value_column' => '',
        'label_column' => '',
    ];

    /** @var array<int, array<string, string>> */
    public array $staticPairs = [
        ['value' => '', 'label' => ''],
    ];

    public string $sqlQuery = '';
    public string $sqlTestResult = '';
    public bool $sqlTestSuccess = false;

    /** @var array<int, array<string, mixed>> */
    public array $previewOptions = [];

    /** @var array<int, array<string, mixed>> */
    public array $availableTables = [];

    public function mount(StudioAccess $access, ?string $lovId = null): void
    {
        $access->ensureAdmin();

        $this->lovId = $lovId;
        $this->isNewLov = $lovId === null;

        $this->loadTables();

        if (!$this->isNewLov) {
            $this->loadLov();
        }

        $this->updatePreview();
    }

    public function render()
    {
        return view('livewire.studio.designer.lov-designer');
    }

    private function loadTables(): void
    {
        $tables = DB::select("
            SELECT TABLE_NAME AS TABLE_NAME
            FROM USER_TABLES
            WHERE TABLE_NAME NOT LIKE 'RT_%'
            ORDER BY TABLE_NAME
        ");

        $this->availableTables = $this->normalizeRows($tables);
    }

    private function loadLov(): void
    {
        $lov = DB::selectOne("
            SELECT
                RAWTOHEX(OBJECT_ID) AS ID,
                OBJECT_CODE,
                OBJECT_NAME,
                DESCRIPTION,
                LOV_TYPE,
                SOURCE_QUERY,
                VALUE_COLUMN,
                LABEL_COLUMN
            FROM DS_LOV
            WHERE OBJECT_ID = HEXTORAW(?)
        ", [$this->lovId]);

        if (!$lov) {
            abort(404, 'LOV not found');
        }

        $lovArr = $this->normalizeRow($lov);

        $this->lov = [
            'object_code' => $lovArr['OBJECT_CODE'] ?? '',
            'object_name' => $lovArr['OBJECT_NAME'] ?? '',
            'description' => $lovArr['DESCRIPTION'] ?? '',
            'lov_type' => $lovArr['LOV_TYPE'] ?? 'STATIC',
            'source_query' => $lovArr['SOURCE_QUERY'] ?? '',
            'value_column' => $lovArr['VALUE_COLUMN'] ?? '',
            'label_column' => $lovArr['LABEL_COLUMN'] ?? '',
        ];

        // Parse static JSON to pairs
        if ($this->lov['lov_type'] === 'STATIC' && !empty($this->lov['source_query'])) {
            $this->parseStaticJson((string) $this->lov['source_query']);
        }

        // Load SQL query for SQL/VIEW
        if (in_array($this->lov['lov_type'], ['SQL', 'VIEW'], true)) {
            $this->sqlQuery = (string) $this->lov['source_query'];
        }
    }

    private function parseStaticJson(string $json): void
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            $this->staticPairs = [['value' => '', 'label' => '']];
            return;
        }

        $pairs = [];

        // Support: [{value,label}], {v:l}, or [scalar]
        if (array_is_list($data)) {
            foreach ($data as $item) {
                if (is_array($item) && isset($item['value'])) {
                    $pairs[] = [
                        'value' => (string) $item['value'],
                        'label' => (string) ($item['label'] ?? $item['value']),
                    ];
                } elseif (is_scalar($item)) {
                    $pairs[] = ['value' => (string) $item, 'label' => (string) $item];
                }
            }
        } else {
            foreach ($data as $key => $value) {
                $pairs[] = ['value' => (string) $key, 'label' => (string) $value];
            }
        }

        $pairs[] = ['value' => '', 'label' => ''];
        $this->staticPairs = $pairs;
    }

    public function addStaticRow(): void
    {
        $this->staticPairs[] = ['value' => '', 'label' => ''];
    }

    public function removeStaticRow(int $index): void
    {
        if (count($this->staticPairs) > 1) {
            unset($this->staticPairs[$index]);
            $this->staticPairs = array_values($this->staticPairs);
        }

        $this->updatePreview();
    }

    public function testSqlQuery(): void
    {
        if (empty(trim($this->sqlQuery))) {
            $this->sqlTestResult = 'Query is empty';
            $this->sqlTestSuccess = false;
            return;
        }

        try {
            $results = DB::select($this->sqlQuery);

            $this->sqlTestSuccess = true;
            $this->sqlTestResult = sprintf(
                "OK - Query berhasil\nRows: %d\nColumns: %s\n\nSample (5 baris):\n%s",
                count($results),
                implode(', ', array_keys((array) ($results[0] ?? []))),
                json_encode(array_slice(array_map(fn ($r) => (array) $r, $results), 0, 5), JSON_PRETTY_PRINT)
            );

            $this->updatePreview();
        } catch (\Throwable $e) {
            $this->sqlTestSuccess = false;
            $this->sqlTestResult = "GAGAL:\n" . $e->getMessage();
        }
    }

    public function updatePreview(): void
    {
        try {
            $this->previewOptions = $this->fetchLovOptions();
        } catch (\Throwable $e) {
            $this->previewOptions = [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchLovOptions(): array
    {
        return match ($this->lov['lov_type']) {
            'STATIC' => $this->fetchStaticOptions(),
            'SQL' => $this->fetchSqlOptions(),
            'VIEW' => $this->fetchViewOptions(),
            default => [],
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchStaticOptions(): array
    {
        $options = [];

        foreach ($this->staticPairs as $pair) {
            if ($pair['value'] !== '' && $pair['label'] !== '') {
                $options[] = ['value' => $pair['value'], 'label' => $pair['label']];
            }
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSqlOptions(): array
    {
        if (empty(trim($this->sqlQuery))) {
            return [];
        }

        $rows = $this->normalizeRows(DB::select($this->sqlQuery));

        if (empty($rows)) {
            return [];
        }

        $keys = array_keys($rows[0]);
        $valueCol = $this->resolveColumn($this->lov['value_column'], $keys, 0);
        $labelCol = $this->resolveColumn($this->lov['label_column'], $keys, 1);

        return array_map(function ($arr) use ($valueCol, $labelCol) {
            return [
                'value' => (string) ($arr[$valueCol] ?? ''),
                'label' => (string) ($arr[$labelCol] ?? ($arr[$valueCol] ?? '')),
            ];
        }, $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchViewOptions(): array
    {
        $source = trim((string) $this->lov['source_query']);

        if ($source === '' || !preg_match('/^[A-Za-z0-9_$.]+$/', $source)) {
            return [];
        }

        $valueCol = $this->lov['value_column'] ?: 'OBJECT_ID';
        $labelCol = $this->lov['label_column'] ?: 'OBJECT_NAME';

        // Validasi nama kolom (hindari injeksi).
        foreach ([$valueCol, $labelCol] as $c) {
            if (!preg_match('/^[A-Za-z0-9_$]+$/', (string) $c)) {
                return [];
            }
        }

        $isRaw = str_contains(strtoupper((string) $valueCol), 'OBJECT_ID');
        $valueExpr = $isRaw ? "RAWTOHEX({$valueCol})" : $valueCol;

        $query = "SELECT {$valueExpr} AS VALUE, {$labelCol} AS LABEL FROM {$source} ORDER BY {$labelCol} FETCH FIRST 100 ROWS ONLY";
        $rows = $this->normalizeRows(DB::select($query));

        return array_map(fn ($arr) => [
            'value' => (string) ($arr['VALUE'] ?? ''),
            'label' => (string) ($arr['LABEL'] ?? ''),
        ], $rows);
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function resolveColumn(?string $configured, array $keys, int $default): string
    {
        if ($configured) {
            $upper = strtoupper($configured);
            foreach ($keys as $k) {
                if (strtoupper($k) === $upper) {
                    return $k;
                }
            }
        }

        return $keys[$default] ?? ($keys[0] ?? '');
    }

    public function save(): void
    {
        $this->validate([
            'lov.object_code' => 'required|max:100',
            'lov.object_name' => 'required|max:200',
            'lov.lov_type' => 'required|in:STATIC,SQL,VIEW',
        ]);

        try {
            $sourceQuery = $this->buildSourceQuery();

            if ($this->isNewLov) {
                $lovId = strtoupper(bin2hex(random_bytes(16)));

                DB::insert("
                    INSERT INTO DS_LOV (
                        OBJECT_ID, OBJECT_CODE, OBJECT_NAME, LOV_TYPE,
                        VALUE_COLUMN, LABEL_COLUMN, SOURCE_QUERY, DESCRIPTION, STATUS
                    ) VALUES (
                        HEXTORAW(?), ?, ?, ?,
                        ?, ?, ?, ?, 'PUBLISHED'
                    )
                ", [
                    $lovId,
                    $this->lov['object_code'],
                    $this->lov['object_name'],
                    $this->lov['lov_type'],
                    $this->lov['value_column'] ?: null,
                    $this->lov['label_column'] ?: null,
                    $sourceQuery,
                    $this->lov['description'] ?: null,
                ]);

                $this->lovId = $lovId;
                $this->isNewLov = false;

                session()->flash('success', 'LOV berhasil dibuat! Jangan lupa kompilasi & aktifkan aplikasi agar dropdown muncul di runtime.');
            } else {
                DB::update("
                    UPDATE DS_LOV
                    SET
                        OBJECT_CODE = ?,
                        OBJECT_NAME = ?,
                        LOV_TYPE = ?,
                        VALUE_COLUMN = ?,
                        LABEL_COLUMN = ?,
                        SOURCE_QUERY = ?,
                        DESCRIPTION = ?,
                        UPDATED_AT = SYSTIMESTAMP
                    WHERE OBJECT_ID = HEXTORAW(?)
                ", [
                    $this->lov['object_code'],
                    $this->lov['object_name'],
                    $this->lov['lov_type'],
                    $this->lov['value_column'] ?: null,
                    $this->lov['label_column'] ?: null,
                    $sourceQuery,
                    $this->lov['description'] ?: null,
                    $this->lovId,
                ]);

                session()->flash('success', 'LOV berhasil diperbarui! Jangan lupa kompilasi ulang aplikasi.');
            }

            $this->updatePreview();
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    private function buildSourceQuery(): string
    {
        return match ($this->lov['lov_type']) {
            'STATIC' => (string) json_encode(array_values(array_filter(
                array_map(fn ($p) => ['value' => $p['value'], 'label' => $p['label']], $this->staticPairs),
                fn ($p) => $p['value'] !== '' && $p['label'] !== ''
            ))),
            'SQL' => $this->sqlQuery,
            'VIEW' => (string) $this->lov['source_query'],
            default => '',
        };
    }

    public function setSourceType(string $type): void
    {
        $this->lov['lov_type'] = $type;
        $this->updatePreview();
    }

    public function importCsv(): void
    {
        session()->flash('info', 'Fitur import CSV akan hadir berikutnya.');
    }
}
