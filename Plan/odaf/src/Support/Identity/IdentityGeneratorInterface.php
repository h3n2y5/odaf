<?php

declare(strict_types=1);

namespace Odaf\Support\Identity;

/**
 * Pembangkit OBJECT_ID (Vol.2 Bab 08 §16).
 *
 * Syarat: unik global, distributed, collision-resistant, sortable (disarankan),
 * runtime-independent. Implementasi boleh ULID atau UUIDv7 — transparan bagi
 * logika bisnis. Disimpan sebagai RAW(16) di Oracle.
 */
interface IdentityGeneratorInterface
{
    /**
     * Bangkitkan identitas baru dalam bentuk kanonik HEX 32 karakter (uppercase),
     * yaitu encoding 16 byte untuk kolom RAW(16) via HEXTORAW.
     */
    public function generate(): string;

    /**
     * Konversi identitas kanonik ke 16 byte biner untuk kolom RAW(16).
     */
    public function toBinary(string $id): string;

    /**
     * Konversi 16 byte biner (RAW(16)) kembali ke string kanonik.
     */
    public function fromBinary(string $binary): string;
}
