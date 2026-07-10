-- =============================================================================
-- ODAF Metadata Repository - System Domain (SYS_*)
-- gvenzl menjalankan skrip ini sebagai SYS@CDB$ROOT; alihkan ke PDB & schema ODAF.
ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = ODAF;
--
-- Volume 2: Naming Standards (Bab 06), Core DDL (Bab 25)
-- Lookup/enumeration tables. Target: Oracle 23ai (mendukung IF NOT EXISTS).
-- =============================================================================

-- Status lifecycle metadata (SYS_<CATEGORY>).
CREATE TABLE IF NOT EXISTS SYS_STATUS (
    STATUS_CODE    VARCHAR2(30 CHAR)  NOT NULL,
    STATUS_NAME    VARCHAR2(100 CHAR) NOT NULL,
    DISPLAY_ORDER  NUMBER(10)         DEFAULT 0 NOT NULL,
    ACTIVE_FLAG    NUMBER(1)          DEFAULT 1 NOT NULL,
    CONSTRAINT PK_SYS_STATUS PRIMARY KEY (STATUS_CODE),
    CONSTRAINT CK_SYS_STATUS_ACTIVE CHECK (ACTIVE_FLAG IN (0, 1))
);

-- Bahasa untuk i18n caption (Vol.1 - Report/Theme multi-language).
CREATE TABLE IF NOT EXISTS SYS_LANGUAGE (
    LANGUAGE_CODE  VARCHAR2(10 CHAR)  NOT NULL,
    LANGUAGE_NAME  VARCHAR2(100 CHAR) NOT NULL,
    ACTIVE_FLAG    NUMBER(1)          DEFAULT 1 NOT NULL,
    CONSTRAINT PK_SYS_LANGUAGE PRIMARY KEY (LANGUAGE_CODE),
    CONSTRAINT CK_SYS_LANGUAGE_ACTIVE CHECK (ACTIVE_FLAG IN (0, 1))
);
