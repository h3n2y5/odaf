<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;

/**
 * UserProvider ODAF: mengautentikasi terhadap SEC_USER.
 *
 * Mengikuti konvensi RAW(16): OBJECT_ID dibaca via RAWTOHEX (hex uppercase) dan
 * dicocokkan via HEXTORAW. Hanya pengguna ACTIVE_FLAG=1 & STATUS='PUBLISHED'
 * yang dapat login. Role pengguna dimuat sekaligus untuk RBAC.
 */
final class OdafUserProvider implements UserProvider
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly Hasher $hasher,
    ) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        $row = $this->connection->selectOne(
            "SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, OBJECT_CODE, OBJECT_NAME, EMAIL, PASSWORD_HASH
             FROM SEC_USER
             WHERE OBJECT_ID = HEXTORAW(?) AND ACTIVE_FLAG = 1 AND STATUS = 'PUBLISHED'",
            [strtoupper((string) $identifier)],
        );

        return $row === null ? null : $this->hydrate((array) $row);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        // Remember-me tidak didukung (tidak ada kolom token pada SEC_USER).
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // No-op: remember-me tidak didukung pada F1.
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $username = $credentials['username'] ?? null;
        if ($username === null || $username === '') {
            return null;
        }

        $row = $this->connection->selectOne(
            "SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, OBJECT_CODE, OBJECT_NAME, EMAIL, PASSWORD_HASH
             FROM SEC_USER
             WHERE OBJECT_CODE = ? AND ACTIVE_FLAG = 1 AND STATUS = 'PUBLISHED'",
            [(string) $username],
        );

        return $row === null ? null : $this->hydrate((array) $row);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = (string) ($credentials['password'] ?? '');
        $hash = $user->getAuthPassword();

        if ($plain === '' || $hash === '') {
            return false;
        }

        return $this->hasher->check($plain, $hash);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // Rehash otomatis dilewati pada F1; password dikelola via
        // perintah artisan odaf:user:password.
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function hydrate(array $row): OdafUser
    {
        $row = array_change_key_case($row, CASE_UPPER);
        $objectId = strtoupper((string) $row['OBJECT_ID']);

        $passwordHash = $row['PASSWORD_HASH'] ?? null;
        if (is_resource($passwordHash)) {
            $passwordHash = stream_get_contents($passwordHash);
        }

        return new OdafUser(
            objectId: $objectId,
            username: (string) $row['OBJECT_CODE'],
            name: (string) $row['OBJECT_NAME'],
            email: $row['EMAIL'] !== null ? (string) $row['EMAIL'] : null,
            passwordHash: $passwordHash !== null ? (string) $passwordHash : null,
            roleIds: $this->loadRoleIds($objectId),
        );
    }

    /**
     * @return array<int, string>
     */
    private function loadRoleIds(string $objectId): array
    {
        $rows = $this->connection->select(
            'SELECT RAWTOHEX(ROLE_ID) AS ROLE_ID FROM SEC_USER_ROLE WHERE USER_ID = HEXTORAW(?)',
            [$objectId],
        );

        return array_map(
            static function ($r): string {
                $arr = (array) $r;

                return $arr === [] ? '' : strtoupper((string) reset($arr));
            },
            $rows,
        );
    }
}
