<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Repositori kontrol akses berbutir-halus (tabel SEC_ACCESS).
 *
 * Menyediakan pembuatan tabel idempotent, daftar role/aplikasi/halaman/field,
 * pembacaan aturan akses per role, penyimpanan aturan, dan pengelolaan
 * hierarki role (PARENT_ROLE_ID) serta masa berlaku (VALID_FROM/VALID_TO).
 *
 * Level akses: FULL | READONLY | MASKED | NONE (kosong/DEFAULT = tanpa aturan).
 */
final class AccessControlRepository
{
    public const LEVELS = ['FULL', 'READONLY', 'MASKED', 'NONE'];

    public function isInstalled(): bool
    {
        return DB::selectOne("SELECT 1 AS X FROM USER_TABLES WHERE TABLE_NAME = 'SEC_ACCESS'") !== null;
    }

    /** Buat tabel SEC_ACCESS bila belum ada. Aman dipanggil berulang. */
    public function ensureInstalled(): void
    {
        if ($this->isInstalled()) {
            return;
        }

        DB::unprepared(<<<'SQL'
        DECLARE
            v_count NUMBER;
        BEGIN
            SELECT COUNT(*) INTO v_count FROM USER_TABLES WHERE TABLE_NAME = 'SEC_ACCESS';
            IF v_count = 0 THEN
                EXECUTE IMMEDIATE '
                    CREATE TABLE SEC_ACCESS (
                        OBJECT_ID        RAW(16)                  DEFAULT SYS_GUID() NOT NULL,
                        ROLE_ID          RAW(16)                  NOT NULL,
                        OBJECT_TYPE      VARCHAR2(20 CHAR)        NOT NULL,
                        TARGET_OBJECT_ID RAW(16)                  NOT NULL,
                        ACCESS_LEVEL     VARCHAR2(20 CHAR)        NOT NULL,
                        VALID_FROM       DATE,
                        VALID_TO         DATE,
                        VERSION_NO       NUMBER(10)               DEFAULT 1 NOT NULL,
                        CREATED_AT       TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
                        UPDATED_AT       TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
                        CREATED_BY       RAW(16),
                        UPDATED_BY       RAW(16),
                        CONSTRAINT PK_SEC_ACCESS PRIMARY KEY (OBJECT_ID),
                        CONSTRAINT UK_SEC_ACCESS UNIQUE (ROLE_ID, OBJECT_TYPE, TARGET_OBJECT_ID),
                        CONSTRAINT FK_SEC_ACCESS_ROLE FOREIGN KEY (ROLE_ID) REFERENCES SEC_ROLE (OBJECT_ID),
                        CONSTRAINT CK_SEC_ACCESS_TYPE CHECK (OBJECT_TYPE IN (''MENU'',''PAGE'',''FIELD'')),
                        CONSTRAINT CK_SEC_ACCESS_LEVEL CHECK (ACCESS_LEVEL IN (''FULL'',''READONLY'',''MASKED'',''NONE''))
                    )';
                EXECUTE IMMEDIATE 'CREATE INDEX IDX_SEC_ACCESS_TARGET ON SEC_ACCESS (OBJECT_TYPE, TARGET_OBJECT_ID)';
                EXECUTE IMMEDIATE 'CREATE INDEX IDX_SEC_ACCESS_ROLE ON SEC_ACCESS (ROLE_ID)';
            END IF;
        END;
        SQL);
    }

    // ---- Lookup data -------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    public function roles(): array
    {
        $parentCol = $this->hasColumn('SEC_ROLE', 'PARENT_ROLE_ID')
            ? ', RAWTOHEX(PARENT_ROLE_ID) AS PARENT_ROLE_ID'
            : ", NULL AS PARENT_ROLE_ID";

        return $this->rows(DB::select(
            "SELECT RAWTOHEX(OBJECT_ID) AS ID, OBJECT_CODE, OBJECT_NAME
                    {$parentCol}
               FROM SEC_ROLE ORDER BY OBJECT_NAME"
        ));
    }

    /** Roles filtered by application (+ ADMIN global role). */
    public function rolesForApp(string $appId): array
    {
        $parentCol = $this->hasColumn('SEC_ROLE', 'PARENT_ROLE_ID')
            ? ', RAWTOHEX(PARENT_ROLE_ID) AS PARENT_ROLE_ID'
            : ", NULL AS PARENT_ROLE_ID";

        $hasAppId = $this->hasColumn('SEC_ROLE', 'APPLICATION_ID');

        if ($hasAppId && $appId !== '') {
            return $this->rows(DB::select(
                "SELECT RAWTOHEX(OBJECT_ID) AS ID, OBJECT_CODE, OBJECT_NAME
                        {$parentCol}
                   FROM SEC_ROLE
                  WHERE APPLICATION_ID = HEXTORAW(?) OR OBJECT_CODE = 'ADMIN'
                  ORDER BY OBJECT_NAME",
                [$appId]
            ));
        }

        return $this->roles();
    }

    /** @return array<int, array<string, mixed>> */
    public function applications(): array
    {
        return $this->rows(DB::select(
            "SELECT RAWTOHEX(OBJECT_ID) AS ID, OBJECT_CODE, OBJECT_NAME
               FROM APP_APPLICATION ORDER BY OBJECT_NAME"
        ));
    }

    /**
     * Pohon objek sebuah aplikasi: daftar halaman (PAGE) beserta field-nya.
     * Menu dikendalikan lewat level PAGE (mapNav menyembunyikan menu bila
     * halaman NONE), sehingga editor cukup mengelola PAGE + FIELD.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pageTree(string $applicationId): array
    {
        $pages = $this->rows(DB::select(
            "SELECT RAWTOHEX(OBJECT_ID) AS ID, OBJECT_CODE, OBJECT_NAME, PAGE_TYPE
               FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?)
              ORDER BY OBJECT_NAME",
            [$applicationId]
        ));

        foreach ($pages as &$page) {
            $page['FIELDS'] = $this->rows(DB::select(
                "SELECT RAWTOHEX(OBJECT_ID) AS ID, LABEL, COLUMN_NAME, FIELD_TYPE
                   FROM UI_FIELD WHERE PAGE_ID = HEXTORAW(?)
                  ORDER BY DISPLAY_ORDER, COLUMN_NAME",
                [$page['ID']]
            ));
        }
        unset($page);

        return $pages;
    }

    // ---- Aturan akses per role ----------------------------------------------

    /**
     * Aturan akses milik sebuah role, dipetakan "TYPE:TARGET_ID(upper)" => LEVEL.
     * Termasuk VALID_FROM dan VALID_TO sebagai metadata tambahan.
     *
     * @return array<string, array<string, mixed>>
     */
    public function rulesForRole(string $roleId): array
    {
        if (! $this->isInstalled()) {
            return [];
        }

        $validityCols = $this->hasColumn('SEC_ACCESS', 'VALID_FROM')
            ? ", TO_CHAR(VALID_FROM, 'YYYY-MM-DD') AS VALID_FROM,
                TO_CHAR(VALID_TO, 'YYYY-MM-DD') AS VALID_TO"
            : ", NULL AS VALID_FROM, NULL AS VALID_TO";

        $rows = $this->rows(DB::select(
            "SELECT OBJECT_TYPE, RAWTOHEX(TARGET_OBJECT_ID) AS TID, ACCESS_LEVEL
                    {$validityCols}
               FROM SEC_ACCESS WHERE ROLE_ID = HEXTORAW(?)",
            [$roleId]
        ));

        $map = [];
        foreach ($rows as $r) {
            $key = strtoupper((string) $r['OBJECT_TYPE']).':'.strtoupper((string) $r['TID']);
            $map[$key] = [
                'level' => (string) $r['ACCESS_LEVEL'],
                'validFrom' => $r['VALID_FROM'] ?? null,
                'validTo' => $r['VALID_TO'] ?? null,
            ];
        }

        return $map;
    }

    /**
     * Simpan satu aturan akses. Level kosong / 'DEFAULT' menghapus aturan
     * (objek kembali memakai kebijakan default).
     */
    public function setRule(
        string $roleId,
        string $objectType,
        string $targetId,
        string $level,
        ?string $userId,
        ?string $validFrom = null,
        ?string $validTo = null,
    ): void {
        $this->ensureInstalled();

        $objectType = strtoupper($objectType);
        $level = strtoupper($level);

        // Hapus aturan lama untuk kunci ini (idempotent upsert sederhana).
        DB::delete(
            "DELETE FROM SEC_ACCESS WHERE ROLE_ID = HEXTORAW(?) AND OBJECT_TYPE = ? AND TARGET_OBJECT_ID = HEXTORAW(?)",
            [$roleId, $objectType, $targetId]
        );

        if ($level === '' || $level === 'DEFAULT' || ! in_array($level, self::LEVELS, true)) {
            return; // DEFAULT: tanpa aturan
        }

        $hasValidity = $this->hasColumn('SEC_ACCESS', 'VALID_FROM');

        if ($hasValidity) {
            DB::insert(
                "INSERT INTO SEC_ACCESS (OBJECT_ID, ROLE_ID, OBJECT_TYPE, TARGET_OBJECT_ID, ACCESS_LEVEL,
                        VALID_FROM, VALID_TO, CREATED_BY)
                 VALUES (SYS_GUID(), HEXTORAW(?), ?, HEXTORAW(?), ?,
                        CASE WHEN ? IS NULL THEN NULL ELSE TO_DATE(?, 'YYYY-MM-DD') END,
                        CASE WHEN ? IS NULL THEN NULL ELSE TO_DATE(?, 'YYYY-MM-DD') END,
                        CASE WHEN ? IS NULL THEN NULL ELSE HEXTORAW(?) END)",
                [$roleId, $objectType, $targetId, $level,
                 $validFrom, $validFrom, $validTo, $validTo,
                 $userId, $userId]
            );
        } else {
            DB::insert(
                "INSERT INTO SEC_ACCESS (OBJECT_ID, ROLE_ID, OBJECT_TYPE, TARGET_OBJECT_ID, ACCESS_LEVEL, CREATED_BY)
                 VALUES (SYS_GUID(), HEXTORAW(?), ?, HEXTORAW(?), ?, CASE WHEN ? IS NULL THEN NULL ELSE HEXTORAW(?) END)",
                [$roleId, $objectType, $targetId, $level, $userId, $userId]
            );
        }
    }

    // ---- Hierarki role -----------------------------------------------------

    /**
     * Set parent role (hierarki). Null = hapus parent (jadi root).
     */
    public function setRoleParent(string $roleId, ?string $parentRoleId): void
    {
        if (! $this->hasColumn('SEC_ROLE', 'PARENT_ROLE_ID')) {
            return;
        }

        // Cegah siklus: parent tidak boleh merupakan descendant dari role ini.
        if ($parentRoleId !== null && $this->wouldCauseCycle($roleId, $parentRoleId)) {
            throw new \RuntimeException('Hierarki sirkular terdeteksi: role target adalah turunan dari role ini.');
        }

        if ($parentRoleId === null || $parentRoleId === '') {
            DB::update(
                'UPDATE SEC_ROLE SET PARENT_ROLE_ID = NULL, UPDATED_AT = SYSTIMESTAMP WHERE OBJECT_ID = HEXTORAW(?)',
                [$roleId]
            );
        } else {
            DB::update(
                'UPDATE SEC_ROLE SET PARENT_ROLE_ID = HEXTORAW(?), UPDATED_AT = SYSTIMESTAMP WHERE OBJECT_ID = HEXTORAW(?)',
                [$parentRoleId, $roleId]
            );
        }
    }

    /**
     * Cek apakah menghubungkan $roleId -> $parentId akan menyebabkan siklus.
     */
    private function wouldCauseCycle(string $roleId, string $parentId): bool
    {
        // Telusuri ancestor dari $parentId; jika $roleId ditemukan = siklus.
        $rows = $this->rows(DB::select(
            "SELECT RAWTOHEX(OBJECT_ID) AS RID
               FROM SEC_ROLE
              START WITH OBJECT_ID = HEXTORAW(?)
            CONNECT BY NOCYCLE OBJECT_ID = PRIOR PARENT_ROLE_ID",
            [$parentId]
        ));

        $ancestors = array_map(fn ($r) => strtoupper((string) $r['RID']), $rows);

        return in_array(strtoupper($roleId), $ancestors, true);
    }

    // ---- User-Role masa berlaku -------------------------------------------

    /**
     * Daftar role sebuah user beserta masa berlaku.
     *
     * @return array<int, array<string, mixed>>
     */
    public function userRolesWithValidity(string $userId): array
    {
        $validityCols = $this->hasColumn('SEC_USER_ROLE', 'VALID_FROM')
            ? ", TO_CHAR(ur.VALID_FROM, 'YYYY-MM-DD') AS VALID_FROM,
                TO_CHAR(ur.VALID_TO, 'YYYY-MM-DD') AS VALID_TO"
            : ", NULL AS VALID_FROM, NULL AS VALID_TO";

        return $this->rows(DB::select(
            "SELECT RAWTOHEX(r.OBJECT_ID) AS ROLE_ID, r.OBJECT_CODE, r.OBJECT_NAME
                    {$validityCols}
               FROM SEC_USER_ROLE ur
               JOIN SEC_ROLE r ON r.OBJECT_ID = ur.ROLE_ID
              WHERE ur.USER_ID = HEXTORAW(?)
              ORDER BY r.OBJECT_NAME",
            [$userId]
        ));
    }

    /**
     * Set masa berlaku keanggotaan role pada user.
     */
    public function setUserRoleValidity(string $userId, string $roleId, ?string $validFrom, ?string $validTo): void
    {
        if (! $this->hasColumn('SEC_USER_ROLE', 'VALID_FROM')) {
            return;
        }

        DB::update(
            "UPDATE SEC_USER_ROLE
                SET VALID_FROM = CASE WHEN ? IS NULL THEN NULL ELSE TO_DATE(?, 'YYYY-MM-DD') END,
                    VALID_TO   = CASE WHEN ? IS NULL THEN NULL ELSE TO_DATE(?, 'YYYY-MM-DD') END
              WHERE USER_ID = HEXTORAW(?) AND ROLE_ID = HEXTORAW(?)",
            [$validFrom, $validFrom, $validTo, $validTo, $userId, $roleId]
        );
    }

    // ---- Internal helpers --------------------------------------------------

    /** @var array<string, bool> */
    private array $columnCache = [];

    private function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";
        if (isset($this->columnCache[$key])) {
            return $this->columnCache[$key];
        }

        $count = (int) DB::scalar(
            'SELECT COUNT(*) FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?',
            [strtoupper($table), strtoupper($column)]
        );

        return $this->columnCache[$key] = ($count > 0);
    }

    /**
     * Normalisasi baris oci8 (uppercase kunci; CLOB -> string).
     *
     * @param  iterable<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function rows(iterable $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ((array) $row as $k => $v) {
                $item[strtoupper((string) $k)] = is_resource($v) ? (string) stream_get_contents($v) : $v;
            }
            $out[] = $item;
        }

        return $out;
    }
}
