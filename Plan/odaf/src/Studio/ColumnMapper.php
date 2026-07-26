<?php

declare(strict_types=1);

namespace Odaf\Studio;

/**
 * Pemetaan kolom Oracle -> metadata field ODAF (logika murni, tanpa DB).
 *
 * Dipakai oleh MetadataScaffolder untuk mereverse-engineer tabel bisnis menjadi
 * metadata UI_FIELD (FIELD_TYPE + DATA_TYPE) beserta klasifikasi kolom sistem.
 */
final class ColumnMapper
{
    /**
     * Kolom yang dikelola engine (audit/versi/soft-delete) dan tidak muncul di form.
     *
     * @var array<int, string>
     */
    public const SYSTEM_COLUMNS = [
        'CREATED_AT', 'CREATED_BY', 'UPDATED_AT', 'UPDATED_BY',
        'DELETED_AT', 'DELETED_BY', 'VERSION_NO', 'ACTIVE_FLAG',
    ];

    /**
     * Apakah kolom termasuk kolom sistem (dikelola engine, disembunyikan dari form).
     */
    public static function isSystemColumn(string $columnName): bool
    {
        return in_array(strtoupper($columnName), self::SYSTEM_COLUMNS, true);
    }

    /**
     * Petakan sebuah kolom Oracle ke FIELD_TYPE + DATA_TYPE ODAF.
     *
     * @param  array{name: string, dataType: string, length?: int|null, precision?: int|null, scale?: int|null}  $column
     * @return array{fieldType: string, dataType: string}
     */
    public static function map(array $column): array
    {
        $name = strtoupper($column['name']);
        $type = strtoupper($column['dataType']);
        $length = $column['length'] ?? null;
        $precision = $column['precision'] ?? null;
        $scale = $column['scale'] ?? null;

        // Teks.
        if (in_array($type, ['VARCHAR2', 'NVARCHAR2', 'CHAR', 'NCHAR'], true)) {
            if (str_contains($name, 'PHOTO') || str_contains($name, 'FOTO') || str_contains($name, 'IMAGE') || str_contains($name, 'PIC')) {
                return ['fieldType' => 'PHOTO', 'dataType' => 'STRING'];
            }
            if (str_contains($name, 'DOCUMENT') || str_contains($name, 'DOKUMEN') || str_contains($name, 'FILE') || str_contains($name, 'PDF')) {
                return ['fieldType' => 'DOCUMENT', 'dataType' => 'STRING'];
            }
            if (str_contains($name, 'EMAIL')) {
                return ['fieldType' => 'EMAIL', 'dataType' => 'STRING'];
            }
            if ($length !== null && $length > 255) {
                return ['fieldType' => 'TEXTAREA', 'dataType' => 'STRING'];
            }

            return ['fieldType' => 'TEXT', 'dataType' => 'STRING'];
        }

        if (in_array($type, ['CLOB', 'NCLOB', 'LONG'], true)) {
            return ['fieldType' => 'TEXTAREA', 'dataType' => 'STRING'];
        }

        // Numerik.
        if (in_array($type, ['NUMBER', 'FLOAT', 'BINARY_FLOAT', 'BINARY_DOUBLE', 'INTEGER'], true)) {
            // NUMBER(1) sebagai flag boolean -> checkbox.
            if ($type === 'NUMBER' && (int) $precision === 1 && (int) ($scale ?? 0) === 0) {
                return ['fieldType' => 'CHECKBOX', 'dataType' => 'BOOLEAN'];
            }
            if ((int) ($scale ?? 0) > 0 || in_array($type, ['FLOAT', 'BINARY_FLOAT', 'BINARY_DOUBLE'], true)) {
                return ['fieldType' => 'NUMBER', 'dataType' => 'DECIMAL'];
            }

            return ['fieldType' => 'NUMBER', 'dataType' => 'INTEGER'];
        }

        // Tanggal & waktu.
        if ($type === 'DATE') {
            return ['fieldType' => 'DATE', 'dataType' => 'DATE'];
        }
        if (str_starts_with($type, 'TIMESTAMP')) {
            return ['fieldType' => 'DATETIME', 'dataType' => 'DATETIME'];
        }

        // Default aman.
        return ['fieldType' => 'TEXT', 'dataType' => 'STRING'];
    }

    /**
     * Ubah nama kolom menjadi label ramah: CUSTOMER_CODE -> "Customer Code".
     */
    public static function humanize(string $columnName): string
    {
        $words = str_replace('_', ' ', strtolower($columnName));

        return ucwords($words);
    }

    /**
     * Apakah kolom RAW (biasanya id/FK biner) yang tidak dirender langsung.
     */
    public static function isBinary(string $dataType): bool
    {
        return str_starts_with(strtoupper($dataType), 'RAW');
    }
}
