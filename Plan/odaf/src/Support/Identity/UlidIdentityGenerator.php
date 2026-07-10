<?php

declare(strict_types=1);

namespace Odaf\Support\Identity;

use Symfony\Component\Uid\Ulid;

/**
 * Implementasi OBJECT_ID berbasis ULID.
 *
 * Bentuk kanonik platform adalah HEX 32 karakter (encoding 16 byte ULID), agar
 * langsung kompatibel dengan kolom Oracle RAW(16) via HEXTORAW/RAWTOHEX. Karena
 * byte ULID terurut waktu, hex-nya pun tetap sortable secara leksikografis
 * (Vol.2 Bab 08 §16).
 */
final class UlidIdentityGenerator implements IdentityGeneratorInterface
{
    public function generate(): string
    {
        return strtoupper(bin2hex((new Ulid)->toBinary()));
    }

    public function toBinary(string $id): string
    {
        return (string) hex2bin($id);
    }

    public function fromBinary(string $binary): string
    {
        return strtoupper(bin2hex($binary));
    }
}
