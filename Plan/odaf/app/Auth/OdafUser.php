<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Identitas pengguna terautentikasi yang dipetakan dari SEC_USER.
 *
 * Dibuat ringan (bukan Eloquent) agar konsisten dengan konvensi RAW(16) ODAF:
 * OBJECT_ID direpresentasikan sebagai string hex uppercase 32 karakter. Objek
 * ini juga membawa OBJECT_ID role pengguna sehingga Security Engine dapat
 * mengevaluasi RBAC tanpa query ulang.
 */
final class OdafUser implements Authenticatable
{
    /**
     * @param  array<int, string>  $roleIds  OBJECT_ID role (hex uppercase)
     */
    public function __construct(
        public readonly string $objectId,
        public readonly string $username,
        public readonly string $name,
        public readonly ?string $email,
        private readonly ?string $passwordHash,
        public readonly array $roleIds = [],
    ) {}

    public function getAuthIdentifierName(): string
    {
        return 'OBJECT_ID';
    }

    public function getAuthIdentifier(): string
    {
        return $this->objectId;
    }

    public function getAuthPasswordName(): string
    {
        return 'PASSWORD_HASH';
    }

    public function getAuthPassword(): string
    {
        return $this->passwordHash ?? '';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void
    {
        // SEC_USER tidak memiliki kolom remember token; fitur remember-me
        // dinonaktifkan pada F1.
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * @return array<int, string>
     */
    public function roleIds(): array
    {
        return $this->roleIds;
    }
}
