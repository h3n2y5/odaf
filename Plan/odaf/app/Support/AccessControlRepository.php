<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Repositori kontrol akses berbutir-halus (tabel SEC_ACCESS).
 *
 * Menyediakan pembuatan tabel idempotent, daftar role/aplikasi/halaman/field,
 * pembacaan aturan akses per role, dan penyimpanan aturan. Level akses:
 * FULL | READONLY | MASKED | NONE (kosong/DEFAULT = tanpa aturan).
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

    /** @return array<int, array<string, mixed>> */
    public function roles(): array
    {
        return $this->rows(DB::select(
            "SELECT RAWTOHEX(OBJECT_ID) AS ID, OBJECT_CODE, OBJECT_NAME
               FROM SEC_ROLE ORDER BY OBJECT_NAME"
        ));
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

    /**
     * Aturan akses milik sebuah role, dipetakan "TYPE:TARGET_ID(upper)" => LEVEL.
     *
     * @return array<string, string>
     */
    public function rulesForRole(string $roleId): array
    {
        if (! $this->isInstalled()) {
            return [];
        }

        $rows = $this->rows(DB::select(
            "SELECT OBJECT_TYPE, RAWTOHEX(TARGET_OBJECT_ID) AS TID, ACCESS_LEVEL
               FROM SEC_ACCESS WHERE ROLE_ID = HEXTORAW(?)",
            [$roleId]
        ));

        $map = [];
        foreach ($rows as $r) {
            $map[strtoupper((string) $r['OBJECT_TYPE']).':'.strtoupper((string) $r['TID'])] = (string) $r['ACCESS_LEVEL'];
        }

        return $map;
    }

    /**
     * Simpan satu aturan akses. Level kosong / 'DEFAULT' menghapus aturan
     * (objek kembali memakai kebijakan default).
     */
    public function setRule(string $roleId, string $objectType, string $targetId, string $level, ?string $userId): void
    {
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

        DB::insert(
            "INSERT INTO SEC_ACCESS (OBJECT_ID, ROLE_ID, OBJECT_TYPE, TARGET_OBJECT_ID, ACCESS_LEVEL, CREATED_BY)
             VALUES (SYS_GUID(), HEXTORAW(?), ?, HEXTORAW(?), ?, CASE WHEN ? IS NULL THEN NULL ELSE HEXTORAW(?) END)",
            [$roleId, $objectType, $targetId, $level, $userId, $userId]
        );
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
