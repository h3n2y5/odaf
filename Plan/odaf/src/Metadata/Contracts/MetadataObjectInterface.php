<?php

declare(strict_types=1);

namespace Odaf\Metadata\Contracts;

/**
 * Universal Metadata Object (UMO) contract.
 *
 * Kontrak dasar yang diimplementasikan setiap aggregate root pada repository
 * metadata. Memetakan kolom standar aggregate root dari Volume 2 Bab 25.
 *
 * Referensi: Volume 2 Bab 07 (Universal Metadata Object), Bab 08 (Key Strategy).
 */
interface MetadataObjectInterface
{
    /** OBJECT_ID — identitas platform, immutable, RAW(16) direpresentasikan hex/ULID. */
    public function objectId(): string;

    /** OBJECT_CODE — identitas bisnis, immutable, unik per domain. */
    public function objectCode(): string;

    /** OBJECT_NAME — nama tampilan, boleh berubah. */
    public function objectName(): string;

    /** Canonical Object Name, mis. "APP.APPLICATION". */
    public function canonicalName(): string;

    /** Domain metadata: APP, UI, DS, WF, VAL, SEC, dst. */
    public function domain(): string;

    /** VERSION_NO — versi metadata untuk optimistic locking & compiler. */
    public function versionNo(): int;

    /** STATUS lifecycle: DRAFT, PUBLISHED, DEPRECATED, dst. */
    public function status(): string;

    /**
     * Atribut mentah metadata sebagai array asosiatif.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array;
}
