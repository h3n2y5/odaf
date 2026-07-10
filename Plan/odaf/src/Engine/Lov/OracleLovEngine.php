<?php

declare(strict_types=1);

namespace Odaf\Engine\Lov;

use Illuminate\Database\ConnectionInterface;
use Odaf\Engine\Lov\Contracts\LovEngineInterface;
use Odaf\Runtime\Contracts\ExecutionContextInterface;
use Odaf\Runtime\UnifiedRuntimeKernel;
use Throwable;

/**
 * BB-05/LOV — resolusi List of Values (Oracle).
 *
 * Mendukung tiga tipe LOV terkompilasi:
 *  - STATIC : SOURCE_QUERY berisi JSON (array objek {value,label}, map {v:l},
 *             atau array skalar).
 *  - SQL    : SOURCE_QUERY berisi SELECT; VALUE_COLUMN/LABEL_COLUMN menamai kolom
 *             (default kolom pertama & kedua).
 *  - VIEW   : SOURCE_QUERY berisi nama tabel/view; kolom dari VALUE/LABEL_COLUMN.
 *
 * PROCEDURE/REST belum didukung (mengembalikan daftar kosong).
 */
final class OracleLovEngine implements LovEngineInterface
{
    /** @var array<string, array<int, array{value: string, label: string}>> cache opsi per lovId */
    private array $cache = [];

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly UnifiedRuntimeKernel $kernel,
    ) {}

    public function options(ExecutionContextInterface $context, string $lovId, array $params = []): array
    {
        // Kunci cache menyertakan params karena LOV dependen bergantung padanya.
        $paramsUpper = $this->upperKeys($params);
        $cacheKey = $lovId.'|'.md5((string) json_encode($paramsUpper));
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $lov = $this->kernel->lov($context->applicationId(), $lovId);
        if ($lov === null) {
            return $this->cache[$cacheKey] = [];
        }

        $options = match (strtoupper((string) $lov['lovType'])) {
            'STATIC' => $this->staticOptions((string) ($lov['sourceQuery'] ?? '')),
            'SQL' => $this->queryOptions((string) ($lov['sourceQuery'] ?? ''), $lov, isView: false, params: $paramsUpper),
            'VIEW' => $this->queryOptions((string) ($lov['sourceQuery'] ?? ''), $lov, isView: true, params: $paramsUpper),
            default => [],
        };

        return $this->cache[$cacheKey] = $options;
    }

    public function label(ExecutionContextInterface $context, string $lovId, string $value, array $params = []): ?string
    {
        foreach ($this->options($context, $lovId, $params) as $opt) {
            if ((string) $opt['value'] === $value) {
                return $opt['label'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function upperKeys(array $params): array
    {
        $out = [];
        foreach ($params as $k => $v) {
            $out[strtoupper((string) $k)] = $v;
        }

        return $out;
    }

    // ---- Resolver per tipe --------------------------------------------------

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function staticOptions(string $json): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        if (array_is_list($decoded)) {
            foreach ($decoded as $item) {
                if (is_array($item)) {
                    // {value,label} atau {code,name}
                    $value = $item['value'] ?? $item['code'] ?? $item['id'] ?? null;
                    $label = $item['label'] ?? $item['name'] ?? $item['text'] ?? $value;
                    if ($value !== null) {
                        $out[] = ['value' => (string) $value, 'label' => (string) $label];
                    }
                } else {
                    // skalar: value = label
                    $out[] = ['value' => (string) $item, 'label' => (string) $item];
                }
            }
        } else {
            // map {value: label}
            foreach ($decoded as $value => $label) {
                $out[] = ['value' => (string) $value, 'label' => (string) (is_scalar($label) ? $label : $value)];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $lov
     * @param  array<string, mixed>  $params
     * @return array<int, array{value: string, label: string}>
     */
    private function queryOptions(string $source, array $lov, bool $isView, array $params = []): array
    {
        $source = trim($source);
        if ($source === '') {
            return [];
        }

        $valueCol = $lov['valueColumn'] !== null ? strtoupper((string) $lov['valueColumn']) : null;
        $labelCol = $lov['labelColumn'] !== null ? strtoupper((string) $lov['labelColumn']) : null;

        $bindings = [];
        if ($isView) {
            // Sumber adalah nama tabel/view; wajib punya kolom nilai & label.
            if (! preg_match('/^[A-Za-z0-9_$#.]+$/', $source) || $valueCol === null || $labelCol === null) {
                return [];
            }
            if (! preg_match('/^[A-Z0-9_$#]+$/', $valueCol) || ! preg_match('/^[A-Z0-9_$#]+$/', $labelCol)) {
                return [];
            }
            $sql = "SELECT {$valueCol} AS LOV_VALUE, {$labelCol} AS LOV_LABEL FROM {$source} "
                ."ORDER BY {$labelCol} FETCH FIRST 1000 ROWS ONLY";
        } else {
            // Sumber adalah SELECT penuh; substitusi placeholder {{TOKEN}} -> bind.
            $compiled = $this->applyTemplate($source, $params);
            if ($compiled === null) {
                // Parameter dependen belum tersedia -> tidak ada opsi.
                return [];
            }
            [$sql, $bindings] = $compiled;
        }

        try {
            $rows = $this->connection->select($sql, $bindings);
        } catch (Throwable $e) {
            logger()->warning('ODAF LOV query gagal', ['lov' => $lov['code'] ?? null, 'error' => $e->getMessage()]);

            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            $row = $this->normalizeRow($r);
            if ($isView) {
                $value = $row['LOV_VALUE'] ?? null;
                $label = $row['LOV_LABEL'] ?? $value;
            } else {
                $value = $valueCol !== null ? ($row[$valueCol] ?? null) : $this->nthValue($row, 0);
                $label = $labelCol !== null ? ($row[$labelCol] ?? null) : $this->nthValue($row, 1);
                $label ??= $value;
            }
            if ($value !== null) {
                $out[] = ['value' => (string) $value, 'label' => (string) $label];
            }
        }

        return $out;
    }

    /**
     * Substitusi placeholder {{TOKEN}} pada SQL menjadi bind parameter.
     *
     * @param  array<string, mixed>  $params  (kunci UPPER_CASE)
     * @return array{0: string, 1: array<int, mixed>}|null null bila ada token
     *                                                     yang parameternya belum tersedia (LOV dependen belum siap)
     */
    private function applyTemplate(string $sql, array $params): ?array
    {
        $bindings = [];
        $missing = false;

        $compiled = preg_replace_callback(
            '/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/',
            function (array $m) use (&$bindings, &$missing, $params): string {
                $key = strtoupper($m[1]);
                $value = $params[$key] ?? null;
                if ($value === null || $value === '') {
                    $missing = true;

                    return 'NULL';
                }
                $bindings[] = $value;

                return '?';
            },
            $sql,
        );

        if ($missing) {
            return null;
        }

        return [(string) $compiled, $bindings];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeRow(mixed $row): array
    {
        $arr = (array) $row;
        $out = [];
        foreach ($arr as $k => $v) {
            if (is_resource($v)) {
                $v = stream_get_contents($v);
            }
            $out[strtoupper((string) $k)] = $v;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function nthValue(array $row, int $index): mixed
    {
        $values = array_values($row);

        return $values[$index] ?? null;
    }
}
