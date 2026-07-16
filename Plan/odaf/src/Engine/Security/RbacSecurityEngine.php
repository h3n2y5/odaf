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
 *  - Fine-grained access (SEC_ACCESS): FULL / READONLY / MASKED / NONE per
 *    PAGE dan FIELD, dengan masa berlaku (VALID_FROM/VALID_TO) dan hierarki
 *    role (PARENT_ROLE_ID). Level efektif = paling permisif di antara semua
 *    role user (termasuk role warisan). Superuser selalu FULL.
 *
 * Data RBAC (assignment user/role/permission) bersifat operasional, bukan
 * metadata terkompilasi, sehingga dibaca langsung dari repository.
 */
final class RbacSecurityEngine implements SecurityEngineInterface
{
    /** @var array<string, bool> cache superuser per userId */
    private array $superuserCache = [];

    /** @var array<string, array<int, string>> cache roleIds per userId (direct + inherited) */
    private array $roleCache = [];

    /** @var bool|null cache keberadaan kolom PARENT_ROLE_ID di SEC_ROLE */
    private ?bool $hierarchyAvailable = null;

    /** @var bool|null cache keberadaan kolom VALID_FROM di SEC_USER_ROLE */
    private ?bool $roleValidityAvailable = null;

    /** @var bool|null cache keberadaan kolom VALID_FROM di SEC_ACCESS */
    private ?bool $accessValidityAvailable = null;

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
     * permisif di antara role milik user (termasuk role warisan via hierarki);
     * bila user tidak punya aturan untuk objek terkelola tsb, hasilnya NONE.
     * Superuser selalu FULL.
     *
     * Masa berlaku: aturan SEC_ACCESS dengan VALID_FROM > hari ini atau
     * VALID_TO < hari ini diabaikan (dianggap tidak ada).
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
        $validityFilter = $this->accessValidityFilter();

        // Objek yang terkelola (punya minimal satu aturan aktif, role manapun).
        $managedRows = $this->connection->select(
            "SELECT DISTINCT RAWTOHEX(TARGET_OBJECT_ID) AS TID
               FROM SEC_ACCESS
              WHERE OBJECT_TYPE = ? AND TARGET_OBJECT_ID IN ({$idPh})
                {$validityFilter}",
            [$objectType, ...$idBind],
        );
        $managed = array_map(fn ($r): string => strtoupper($this->firstValue($r)), $managedRows);

        // Aturan milik role user (direct + warisan) untuk menghitung level
        // paling permisif. Termasuk role dari hierarki (PARENT_ROLE_ID).
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
                    AND TARGET_OBJECT_ID IN ({$mPh})
                    {$validityFilter}",
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

    /** Bobot permisif: NONE < MASKED < READONLY < APPEND < FULL. */
    private function rank(string $level): int
    {
        return match (strtoupper($level)) {
            self::LEVEL_FULL => 4,
            self::LEVEL_APPEND => 3,
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
     * Role efektif pengguna: role langsung (SEC_USER_ROLE, difilter masa
     * berlaku) ditambah role warisan via hierarki (PARENT_ROLE_ID).
     *
     * @return array<int, string>
     */
    private function roleIds(ExecutionContextInterface $context): array
    {
        // Jika context sudah membawa roleIds (dari OdafUserProvider saat login),
        // gunakan langsung — sudah terfilter validity & diperluas hierarki.
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

        // 1. Role langsung (dengan filter masa berlaku bila kolom tersedia).
        $validityFilter = $this->roleValidityFilter();
        $rows = $this->connection->select(
            "SELECT RAWTOHEX(ROLE_ID) AS ROLE_ID
               FROM SEC_USER_ROLE
              WHERE USER_ID = HEXTORAW(?)
                {$validityFilter}",
            [strtoupper($userId)],
        );

        $directRoles = array_map(
            fn ($r): string => strtoupper($this->firstValue($r)),
            $rows,
        );

        // 2. Perluas dengan hierarki (role warisan via PARENT_ROLE_ID).
        $allRoles = $this->expandRoleHierarchy($directRoles);

        return $this->roleCache[$userId] = $allRoles;
    }

    /**
     * Perluas daftar role dengan semua ancestor (parent, grandparent, dst.)
     * dari hierarki role. Menggunakan CONNECT BY Oracle bila PARENT_ROLE_ID
     * tersedia; jika tidak, kembalikan daftar asli.
     *
     * @param  array<int, string>  $roleIds  role langsung (hex uppercase)
     * @return array<int, string>  role langsung + semua ancestor (unik)
     */
    private function expandRoleHierarchy(array $roleIds): array
    {
        if ($roleIds === [] || ! $this->hasHierarchy()) {
            return $roleIds;
        }

        [$placeholders, $bindings] = $this->rawInList($roleIds);

        // CONNECT BY traversal: dimulai dari role langsung, naik ke parent.
        // NOCYCLE mencegah infinite loop jika ada siklus (seharusnya tidak ada).
        $rows = $this->connection->select(
            "SELECT DISTINCT RAWTOHEX(OBJECT_ID) AS ROLE_ID
               FROM SEC_ROLE
              START WITH OBJECT_ID IN ({$placeholders})
            CONNECT BY NOCYCLE OBJECT_ID = PRIOR PARENT_ROLE_ID",
            $bindings,
        );

        return array_values(array_unique(array_map(
            fn ($r): string => strtoupper($this->firstValue($r)),
            $rows,
        )));
    }

    /**
     * Cek apakah SEC_ROLE memiliki kolom PARENT_ROLE_ID (hierarki tersedia).
     */
    private function hasHierarchy(): bool
    {
        if ($this->hierarchyAvailable !== null) {
            return $this->hierarchyAvailable;
        }

        $count = (int) $this->connection->scalar(
            "SELECT COUNT(*) FROM USER_TAB_COLUMNS
              WHERE TABLE_NAME = 'SEC_ROLE' AND COLUMN_NAME = 'PARENT_ROLE_ID'"
        );

        return $this->hierarchyAvailable = ($count > 0);
    }

    /**
     * Fragment SQL filter masa berlaku untuk SEC_USER_ROLE.
     * Mengembalikan string kosong jika kolom VALID_FROM belum ada.
     */
    private function roleValidityFilter(): string
    {
        if ($this->roleValidityAvailable === null) {
            $count = (int) $this->connection->scalar(
                "SELECT COUNT(*) FROM USER_TAB_COLUMNS
                  WHERE TABLE_NAME = 'SEC_USER_ROLE' AND COLUMN_NAME = 'VALID_FROM'"
            );
            $this->roleValidityAvailable = ($count > 0);
        }

        if (! $this->roleValidityAvailable) {
            return '';
        }

        return "AND (VALID_FROM IS NULL OR VALID_FROM <= TRUNC(SYSDATE))
                AND (VALID_TO IS NULL OR VALID_TO >= TRUNC(SYSDATE))";
    }

    /**
     * Fragment SQL filter masa berlaku untuk SEC_ACCESS.
     * Mengembalikan string kosong jika kolom VALID_FROM belum ada.
     */
    private function accessValidityFilter(): string
    {
        if ($this->accessValidityAvailable === null) {
            $count = (int) $this->connection->scalar(
                "SELECT COUNT(*) FROM USER_TAB_COLUMNS
                  WHERE TABLE_NAME = 'SEC_ACCESS' AND COLUMN_NAME = 'VALID_FROM'"
            );
            $this->accessValidityAvailable = ($count > 0);
        }

        if (! $this->accessValidityAvailable) {
            return '';
        }

        return "AND (VALID_FROM IS NULL OR VALID_FROM <= TRUNC(SYSDATE))
                AND (VALID_TO IS NULL OR VALID_TO >= TRUNC(SYSDATE))";
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
