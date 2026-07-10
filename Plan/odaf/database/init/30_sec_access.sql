-- =============================================================================
-- 30_sec_access.sql
-- SEC_ACCESS: kontrol akses berbutir-halus per ROLE terhadap objek UI
-- (MENU | PAGE | FIELD) dengan tingkat akses:
--   FULL     = baca + tulis penuh
--   READONLY = hanya baca (tidak bisa ubah/simpan)
--   MASKED   = data disamarkan (••••) untuk data rahasia
--   NONE     = tidak boleh diakses / disembunyikan
--
-- Kebijakan penegakan (lihat RbacSecurityEngine): sebuah objek bersifat
-- "terbuka" (FULL) selama belum ada satupun baris SEC_ACCESS untuknya. Begitu
-- ada minimal satu aturan untuk objek tsb (role manapun), objek menjadi
-- "terkelola": hanya role yang diberi aturan yang memperoleh akses, selain itu
-- NONE. Superuser (role ADMIN) selalu FULL.
--
-- Idempotent: aman dijalankan berulang.
-- =============================================================================

ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = ODAF;

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
/
