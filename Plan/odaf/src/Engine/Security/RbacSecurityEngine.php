<?php

declare(strict_types=1);

namespace Odaf\Engine\Security;

use Illuminate\Database\ConnectionInterface;
use Odaf\Engine\Security\Contracts\AuthorizationException;
use Odaf\Engine\Security\Contracts\SecurityEngineInterface;
use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * BB-07 Security Engine — RBAC berbasis SEC_* (Vol.3 Bab 18).
 *
 * Model penegakan:
 *  - Role dengan OBJECT_CODE 'ADMIN' adalah superuser (akses penuh).
 *  - Selain itu: deny-by-default. Akses diberikan bila ada SEC_ROLE_PERMISSION
 *    -> SEC_PERMISSION dengan TARGET_OBJECT_ID + ACTION_CODE yang cocok.
 *  - Field-level: field tanpa permission FIELD terdaftar dianggap terlihat
 *    (belum dibatasi); jika ada permission FIELD, hanya yang diberikan READ.
 *
 * Data RBAC (assignment user/role/permission) bersifat operasional, bukan
 * metadata terkompilasi, sehingga dibaca langsung dari repository.
 */
final class RbacSecurityEngine implements SecurityEngineInterface
{
    /** @var array<string, bool> cache superuser per userId */
    private array $superuserCache = [];

    /** @var array<string, array<int, string>> cache roleIds per userId */
    private array $roleCache = [];

    public function __construct(private readonly ConnectionInterface $connection) {}

    public function can(ExecutionContextInterface $context, string $objectId, string $action): bool
    {
        if ($this->isSuperuser($context)) {
            return true;
        }

        $roleIds = $this->roleIds($context);
        if ($roleIds === []) {
            return false;
        }

        [$placeholders, $bindings] = $this->rawInList($roleIds);
        $bindings[] = strtoupper($objectId);
        $bindings[] = strtoupper($action);

        $count = (int) $this->connection->scalar(
            "SELECT COUNT(*)
             FROM SEC_ROLE_PERMISSION rp
             JOIN SEC_PERMISSION p ON p.OBJECT_ID = rp.PERMISSION_ID
             WHERE rp.ROLE_ID IN ({$placeholders})
               AND p.TARGET_OBJECT_ID = HEXTORAW(?)
               AND p.ACTION_CODE = ?",
            $bindings,
        );

        return $count > 0;
    }

    public function authorize(ExecutionContextInterface $context, string $objectId, string $action): void
    {
        if (! $this->can($context, $objectId, $action)) {
            throw new AuthorizationException(
                sprintf('Akses ditolak: aksi %s pada objek %s.', $action, $objectId),
            );
        }
    }

    public function visibleFields(ExecutionContextInterface $context, array $fieldIds): array
    {
        if ($fieldIds === [] || $this->isSuperuser($context)) {
            return $fieldIds;
        }

        [$placeholders, $bindings] = $this->rawInList($fieldIds);

        // Field yang memiliki permission FIELD terdaftar (dibatasi).
        $restrictedRows = $this->connection->select(
            "SELECT DISTINCT RAWTOHEX(TARGET_OBJECT_ID) AS FID
             FROM SEC_PERMISSION
             WHERE PERMISSION_TYPE = 'FIELD' AND TARGET_OBJECT_ID IN ({$placeholders})",
            $bindings,
        );
        $restricted = array_map(fn ($r): string => strtoupper($this->firstValue($r)), $restrictedRows);

        if ($restricted === []) {
            return $fieldIds; // tidak ada pembatasan field
        }

        $roleIds = $this->roleIds($context);
        $granted = [];
        if ($roleIds !== []) {
            [$rolePh, $roleBind] = $this->rawInList($roleIds);
            [$fieldPh, $fieldBind] = $this->rawInList($fieldIds);
            $grantedRows = $this->connection->select(
                "SELECT DISTINCT RAWTOHEX(p.TARGET_OBJECT_ID) AS FID
                 FROM SEC_ROLE_PERMISSION rp
                 JOIN SEC_PERMISSION p ON p.OBJECT_ID = rp.PERMISSION_ID
                 WHERE rp.ROLE_ID IN ({$rolePh})
                   AND p.PERMISSION_TYPE = 'FIELD'
                   AND p.ACTION_CODE = 'READ'
                   AND p.TARGET_OBJECT_ID IN ({$fieldPh})",
                [...$roleBind, ...$fieldBind],
            );
            $granted = array_map(fn ($r): string => strtoupper($this->firstValue($r)), $grantedRows);
        }

        return array_values(array_filter(
            $fieldIds,
            static function (string $fid) use ($restricted, $granted): bool {
                $fid = strtoupper($fid);
                if (! in_array($fid, $restricted, true)) {
                    return true; // tidak dibatasi
                }

                return in_array($fid, $granted, true);
            },
        ));
    }

    public function rowFilter(ExecutionContextInterface $context, string $datasetId): array
    {
        // Row-level security dasar: tidak ada filter tambahan pada F1.
        // Diperluas pada F2 (predikat per role/tenant).
        return [];
    }

    public function accessLevel(ExecutionContextInterface $context, string $objectType, string $objectId): string
    {
        $map = $this->accessLevels($context, $objectType, [$objectId]);

        return $map[strtoupper($objectId)] ?? self::LEVEL_FULL;
    }

    /**
     * Kebijakan: sebuah objek "terbuka" (FULL) selama belum ada aturan
     * SEC_ACCESS untuknya (tipe + target manapun, role manapun). Begitu ada
     * minimal satu aturan, objek "terkelola": level efektif = aturan paling
     * permisif di antara role milik user; bila user tidak punya aturan untuk
     * objek terkelola tsb, hasilnya NONE. Superuser selalu FULL.
     */
    public function accessLevels(ExecutionContextInterface $context, string $objectType, array $objectIds): array
    {
        $ids = array_values(array_unique(array_map(static fn (string $i): string => strtoupper($i), $objectIds)));
        if ($ids === []) {
            return [];
        }

        // Default terbuka bila superuser atau tabel belum ada.
        if ($this->isSuperuser($context) || ! $this->accessTableExists()) {
            return array_fill_keys($ids, self::LEVEL_FULL);
        }

        $objectType = strtoupper($objectType);
        [$idPh, $idBind] = $this->rawInList($ids);

        // Objek yang terkelola (punya minimal satu aturan, role manapun).
        $managedRows = $this->connection->select(
            "SELECT DISTINCT RAWTOHEX(TARGET_OBJECT_ID) AS TID
               FROM SEC_ACCESS
              WHERE OBJECT_TYPE = ? AND TARGET_OBJECT_ID IN ({$idPh})",
            [$objectType, ...$idBind],
        );
        $managed = array_map(fn ($r): string => strtoupper($this->firstValue($r)), $managedRows);

        // Aturan milik role user (untuk menghitung level paling permisif).
        $userLevels = [];
        $roleIds = $this->roleIds($context);
        if ($roleIds !== [] && $managed !== []) {
            [$rolePh, $roleBind] = $this->rawInList($roleIds);
            [$mPh, $mBind] = $this->rawInList($managed);
            $rows = $this->connection->select(
                "SELECT RAWTOHEX(TARGET_OBJECT_ID) AS TID, ACCESS_LEVEL AS LVL
                   FROM SEC_ACCESS
                  WHERE OBJECT_TYPE = ?
                    AND ROLE_ID IN ({$rolePh})
                    AND TARGET_OBJECT_ID IN ({$mPh})",
                [$objectType, ...$roleBind, ...$mBind],
            );
            foreach ($rows as $r) {
                $arr = array_change_key_case((array) $r, CASE_UPPER);
                $tid = strtoupper((string) ($arr['TID'] ?? ''));
                $lvl = strtoupper((string) ($arr['LVL'] ?? self::LEVEL_NONE));
                if (! isset($userLevels[$tid]) || $this->rank($lvl) > $this->rank($userLevels[$tid])) {
                    $userLevels[$tid] = $lvl;
                }
            }
        }

        $result = [];
        foreach ($ids as $id) {
            if (! in_array($id, $managed, true)) {
                $result[$id] = self::LEVEL_FULL;      // tak terkelola -> terbuka
            } else {
                $result[$id] = $userLevels[$id] ?? self::LEVEL_NONE; // terkelola -> harus di-grant
            }
        }

        return $result;
    }

    /** Bobot permisif: NONE < MASKED < READONLY < FULL. */
    private function rank(string $level): int
    {
        return match (strtoupper($level)) {
            self::LEVEL_FULL => 3,
            self::LEVEL_READONLY => 2,
            self::LEVEL_MASKED => 1,
            default => 0, // NONE / tak dikenal
        };
    }

    /** @var bool|null cache keberadaan tabel SEC_ACCESS */
    private ?bool $accessTableExists = null;

    private function accessTableExists(): bool
    {
        if ($this->accessTableExists !== null) {
            return $this->accessTableExists;
        }

        $count = (int) $this->connection->scalar(
            "SELECT COUNT(*) FROM USER_TABLES WHERE TABLE_NAME = 'SEC_ACCESS'"
        );

        return $this->accessTableExists = ($count > 0);
    }

    // ---- Helpers ------------------------------------------------------------

    public function isSuperuser(ExecutionContextInterface $context): bool
    {
        $userId = $context->userId();
        if ($userId === null) {
            return false;
        }
        if (isset($this->superuserCache[$userId])) {
            return $this->superuserCache[$userId];
        }

        $roleIds = $this->roleIds($context);
        if ($roleIds === []) {
            return $this->superuserCache[$userId] = false;
        }

        [$placeholders, $bindings] = $this->rawInList($roleIds);
        $count = (int) $this->connection->scalar(
            "SELECT COUNT(*) FROM SEC_ROLE WHERE OBJECT_CODE = 'ADMIN' AND OBJECT_ID IN ({$placeholders})",
            $bindings,
        );

        return $this->superuserCache[$userId] = ($count > 0);
    }

    /**
     * @return array<int, string>
     */
    private function roleIds(ExecutionContextInterface $context): array
    {
        if ($context->roleIds() !== []) {
            return $context->roleIds();
        }
        $userId = $context->userId();
        if ($userId === null) {
            return [];
        }
        if (isset($this->roleCache[$userId])) {
            return $this->roleCache[$userId];
        }

        $rows = $this->connection->select(
            'SELECT RAWTOHEX(ROLE_ID) AS ROLE_ID FROM SEC_USER_ROLE WHERE USER_ID = HEXTORAW(?)',
            [strtoupper($userId)],
        );

        return $this->roleCache[$userId] = array_map(
            fn ($r): string => strtoupper($this->firstValue($r)),
            $rows,
        );
    }

    /**
     * Ambil nilai kolom pertama dari sebuah baris hasil, terlepas dari case
     * nama kolom (driver oci8 mengembalikan nama kolom lowercase).
     */
    private function firstValue(mixed $row): string
    {
        $arr = (array) $row;

        return $arr === [] ? '' : (string) reset($arr);
    }

    /**
     * Bangun daftar HEXTORAW(?) untuk klausa IN.
     *
     * @param  array<int, string>  $ids
     * @return array{0: string, 1: array<int, string>}
     */
    private function rawInList(array $ids): array
    {
        $placeholders = implode(', ', array_fill(0, count($ids), 'HEXTORAW(?)'));
        $bindings = array_map(static fn (string $id): string => strtoupper($id), array_values($ids));

        return [$placeholders, $bindings];
    }
}
