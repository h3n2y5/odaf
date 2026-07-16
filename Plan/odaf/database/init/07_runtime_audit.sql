-- =============================================================================
-- ODAF Runtime Repository (RT_*) + Audit (AUD_*)
-- gvenzl menjalankan skrip ini sebagai SYS@CDB$ROOT; alihkan ke PDB & schema ODAF.
ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = ODAF;
--
-- Volume 2 Bab 32 (Runtime Repository), Bab 10 (Audit Strategy)
-- RT_* diisi compiler dan bersifat immutable/read-only setelah aktivasi.
-- Isolasi design-time vs runtime (CORE-002, CORE-004).
-- =============================================================================

-- Runtime Package: artefak terkompilasi & deterministik.
CREATE TABLE IF NOT EXISTS RT_PACKAGE (
    OBJECT_ID        RAW(16)                  DEFAULT SYS_GUID() NOT NULL,
    APPLICATION_ID   RAW(16)                  NOT NULL,   -- identitas dipertahankan dari APP_APPLICATION
    PACKAGE_VERSION  VARCHAR2(50 CHAR)        NOT NULL,
    CHECKSUM         VARCHAR2(128 CHAR)       NOT NULL,   -- verifikasi reproducibility
    COMPILER_VERSION VARCHAR2(50 CHAR),
    PAYLOAD          CLOB,                                -- objek runtime terkompilasi (JSON)
    ACTIVE_FLAG      NUMBER(1)                DEFAULT 0 NOT NULL,
    COMPILED_AT      TIMESTAMP WITH TIME ZONE DEFAULT SYSTIMESTAMP NOT NULL,
    ACTIVATED_AT     TIMESTAMP WITH TIME ZONE,
    CREATED_BY       RAW(16),
    CONSTRAINT PK_RT_PACKAGE PRIMARY KEY (OBJECT_ID),
    CONSTRAINT UK_RT_PACKAGE_VER UNIQUE (APPLICATION_ID, PACKAGE_VERSION),
    CONSTRAINT CK_RT_PACKAGE_ACTIVE CHECK (ACTIVE_FLAG IN (0, 1))
);

-- Audit event: append-only, immutable.
CREATE TABLE IF NOT EXISTS AUD_EVENT (
    EVENT_ID       RAW(16)                    DEFAULT SYS_GUID() NOT NULL,
    EVENT_TYPE     VARCHAR2(50 CHAR)          NOT NULL,   -- DATA_CREATE|DATA_UPDATE|DATA_DELETE|LOGIN|DEPLOY|...
    OBJECT_ID      RAW(16),                               -- objek yang terpengaruh
    OBJECT_NAME    VARCHAR2(255 CHAR),                    -- Snapshot nama objek saat event terjadi
    DATASET_CODE   VARCHAR2(100 CHAR),
    USER_ID        RAW(16),
    APPLICATION_ID RAW(16),
    BEFORE_DATA    CLOB,                                  -- JSON state sebelum
    AFTER_DATA     CLOB,                                  -- JSON state sesudah
    EVENT_AT       TIMESTAMP WITH TIME ZONE   DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT PK_AUD_EVENT PRIMARY KEY (EVENT_ID)
);

CREATE INDEX IF NOT EXISTS IDX_RT_PACKAGE_APP ON RT_PACKAGE (APPLICATION_ID);
CREATE INDEX IF NOT EXISTS IDX_AUD_EVENT_OBJECT ON AUD_EVENT (OBJECT_ID);
CREATE INDEX IF NOT EXISTS IDX_AUD_EVENT_AT ON AUD_EVENT (EVENT_AT);
