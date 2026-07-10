<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

/**
 * Normalisasi baris hasil query oci8:
 *  - kunci kolom di-uppercase (oci8 mengembalikan lowercase);
 *  - kolom CLOB (resource stream) dibaca menjadi string.
 */
trait NormalizesRows
{
    /**
     * @param  iterable<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRows(iterable $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->normalizeRow($row);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeRow(mixed $row): array
    {
        $arr = (array) $row;
        $out = [];
        foreach ($arr as $key => $value) {
            if (is_resource($value)) {
                $value = stream_get_contents($value);
            }
            $out[strtoupper((string) $key)] = $value;
        }

        return $out;
    }
}
