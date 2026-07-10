<?php

declare(strict_types=1);

namespace Odaf\Support\Identity;

use Symfony\Component\Uid\Uuid;

/**
 * Implementasi OBJECT_ID berbasis UUIDv7 (time-ordered).
 *
 * Sama seperti ULID, bentuk kanonik platform adalah HEX 32 karakter (encoding
 * 16 byte) agar kompatibel langsung dengan kolom Oracle RAW(16).
 */
final class UuidV7IdentityGenerator implements IdentityGeneratorInterface
{
    public function generate(): string
    {
        return strtoupper(bin2hex(Uuid::v7()->toBinary()));
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
