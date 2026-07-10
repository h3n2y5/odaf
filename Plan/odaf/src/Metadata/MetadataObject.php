<?php

declare(strict_types=1);

namespace Odaf\Metadata;

use Odaf\Metadata\Contracts\MetadataObjectInterface;

/**
 * Implementasi generik Universal Metadata Object (UMO).
 *
 * Membungkus satu baris aggregate root metadata design-time (APP_*, UI_*, DS_*,
 * VAL_*, SEC_*) sebagai objek immutable. Atribut mentah tetap dapat diakses via
 * {@see attributes()} untuk dikonsumsi compiler.
 *
 * Referensi: Volume 2 Bab 07 (Universal Metadata Object), Bab 25 (Core DDL).
 */
class MetadataObject implements MetadataObjectInterface
{
    /**
     * @param  array<string, mixed>  $attributes  atribut mentah (kunci UPPER_CASE sesuai kolom)
     */
    public function __construct(
        private readonly string $objectId,
        private readonly string $objectCode,
        private readonly string $objectName,
        private readonly string $domain,
        private readonly int $versionNo,
        private readonly string $status,
        private readonly array $attributes = [],
    ) {}

    /**
     * Bangun UMO dari baris hasil query (kunci kolom UPPER_CASE).
     *
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(string $domain, array $row): self
    {
        return new self(
            objectId: (string) ($row['OBJECT_ID'] ?? ''),
            objectCode: (string) ($row['OBJECT_CODE'] ?? ''),
            objectName: (string) ($row['OBJECT_NAME'] ?? ''),
            domain: $domain,
            versionNo: (int) ($row['VERSION_NO'] ?? 1),
            status: (string) ($row['STATUS'] ?? 'DRAFT'),
            attributes: $row,
        );
    }

    public function objectId(): string
    {
        return $this->objectId;
    }

    public function objectCode(): string
    {
        return $this->objectCode;
    }

    public function objectName(): string
    {
        return $this->objectName;
    }

    public function canonicalName(): string
    {
        return $this->domain.'.'.$this->objectCode;
    }

    public function domain(): string
    {
        return $this->domain;
    }

    public function versionNo(): int
    {
        return $this->versionNo;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Ambil satu atribut mentah dengan default.
     */
    public function attr(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
