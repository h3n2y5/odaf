-- =============================================================================
-- 99_app_manual.sql
-- Tabel APP_MANUAL: konten manual pengguna berbasis web (bagian Markdown).
-- Dapat dibaca semua pengguna; diedit superuser lewat halaman /manual.
--
-- Catatan: konten default (seed) diisi oleh aplikasi (App\Support\ManualRepository
-- ::ensureInstalled) saat tabel masih kosong, sehingga file ini cukup membuat
-- struktur tabelnya saja. Idempotent: aman dijalankan berulang.
-- =============================================================================

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM USER_TABLES WHERE TABLE_NAME = 'APP_MANUAL';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE '
            CREATE TABLE APP_MANUAL (
                OBJECT_ID     RAW(16)       DEFAULT SYS_GUID() NOT NULL,
                SLUG          VARCHAR2(100) NOT NULL,
                TITLE         VARCHAR2(200) NOT NULL,
                BODY_MD       CLOB,
                DISPLAY_ORDER NUMBER(10)    DEFAULT 100 NOT NULL,
                STATUS        VARCHAR2(30)  DEFAULT ''PUBLISHED'' NOT NULL,
                VERSION_NO    NUMBER(10)    DEFAULT 1 NOT NULL,
                CREATED_AT    TIMESTAMP     DEFAULT SYSTIMESTAMP,
                CREATED_BY    VARCHAR2(100),
                UPDATED_AT    TIMESTAMP,
                UPDATED_BY    VARCHAR2(100),
                CONSTRAINT PK_APP_MANUAL PRIMARY KEY (OBJECT_ID),
                CONSTRAINT UQ_APP_MANUAL_SLUG UNIQUE (SLUG)
            )';
    END IF;
END;
/
