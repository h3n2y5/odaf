-- =============================================================================
-- ODAF Metadata Repository - QR Code Domain (QR_*) + Runtime Log (RT_QR_*)
-- gvenzl menjalankan skrip ini sebagai SYS@CDB$ROOT; alihkan ke PDB & schema ODAF.
ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = ODAF;
--
-- QR Code Configuration & Scan Audit Log
-- QR_CONFIG  = konfigurasi QR Code per halaman (design-time metadata)
-- RT_QR_SCAN_LOG = log audit scan QR (runtime data)
-- =============================================================================

-- Konfigurasi QR Code per Page (form/dokumen yang punya QR).
-- Setiap page boleh punya 0..N konfigurasi QR (mis. satu untuk link dokumen,
-- satu untuk workflow action).
CREATE TABLE IF NOT EXISTS QR_CONFIG (
    OBJECT_ID         RAW(16)                   DEFAULT SYS_GUID() NOT NULL,
    PAGE_ID           RAW(16)                   NOT NULL,               -- UI_PAGE pemilik
    OBJECT_CODE       VARCHAR2(100 CHAR)        NOT NULL,
    OBJECT_NAME       VARCHAR2(200 CHAR)        NOT NULL,
    QR_TYPE           VARCHAR2(30 CHAR)         DEFAULT 'DOCUMENT_LINK' NOT NULL,
    -- DOCUMENT_LINK   = URL navigasi ke dokumen ini (default)
    -- WORKFLOW_ACTION = URL + auto-prompt workflow action saat scan
    -- CROSS_DOCUMENT  = URL navigasi ke dokumen/form lain (mis. PO → Good Receipt)
    -- CUSTOM_DATA     = data kustom dari field-field tertentu
    TARGET_APP_CODE   VARCHAR2(100 CHAR),                               -- app tujuan (null = sama)
    TARGET_PAGE_CODE  VARCHAR2(100 CHAR),                               -- page tujuan (null = sama)
    TARGET_ACTION     VARCHAR2(50 CHAR),                                -- workflow action (opsional)
    DATA_FIELDS       CLOB,                                             -- JSON: field columns yang masuk ke QR payload
    FK_COLUMN         VARCHAR2(128 CHAR),                               -- kolom FK pada target (untuk cross-doc prefill)
    POSITION          VARCHAR2(30 CHAR)         DEFAULT 'TOP_RIGHT' NOT NULL,
    SIZE_PX           NUMBER(5)                 DEFAULT 150 NOT NULL,
    SHOW_ON_FORM      NUMBER(1)                 DEFAULT 1 NOT NULL,     -- tampilkan di form view
    SHOW_ON_PRINT     NUMBER(1)                 DEFAULT 1 NOT NULL,     -- tampilkan saat print
    DESCRIPTION       CLOB,
    VERSION_NO        NUMBER(10)                DEFAULT 1 NOT NULL,
    STATUS            VARCHAR2(30 CHAR)         DEFAULT 'DRAFT' NOT NULL,
    CREATED_AT        TIMESTAMP WITH TIME ZONE  DEFAULT SYSTIMESTAMP NOT NULL,
    UPDATED_AT        TIMESTAMP WITH TIME ZONE  DEFAULT SYSTIMESTAMP NOT NULL,
    CREATED_BY        RAW(16),
    UPDATED_BY        RAW(16),
    CONSTRAINT PK_QR_CONFIG PRIMARY KEY (OBJECT_ID),
    CONSTRAINT UK_QR_CONFIG_CODE UNIQUE (PAGE_ID, OBJECT_CODE),
    CONSTRAINT FK_QR_CONFIG_PAGE FOREIGN KEY (PAGE_ID) REFERENCES UI_PAGE (OBJECT_ID),
    CONSTRAINT CK_QR_CONFIG_TYPE CHECK (QR_TYPE IN ('DOCUMENT_LINK','WORKFLOW_ACTION','CROSS_DOCUMENT','CUSTOM_DATA')),
    CONSTRAINT CK_QR_CONFIG_POS CHECK (POSITION IN ('TOP_RIGHT','TOP_LEFT','BOTTOM_RIGHT','BOTTOM_LEFT','HEADER_CENTER')),
    CONSTRAINT CK_QR_CONFIG_FORM CHECK (SHOW_ON_FORM IN (0, 1)),
    CONSTRAINT CK_QR_CONFIG_PRINT CHECK (SHOW_ON_PRINT IN (0, 1))
);

-- Log audit scan QR Code (append-only, runtime data).
CREATE TABLE IF NOT EXISTS RT_QR_SCAN_LOG (
    OBJECT_ID       RAW(16)                     DEFAULT SYS_GUID() NOT NULL,
    QR_CONFIG_ID    RAW(16),                                            -- referensi ke QR_CONFIG (opsional)
    APP_CODE        VARCHAR2(100 CHAR),
    PAGE_CODE       VARCHAR2(100 CHAR),
    ENTITY_KEY      VARCHAR2(64 CHAR),                                  -- PK record yang di-scan
    SCANNED_BY      RAW(16),                                            -- SEC_USER yang scan
    SCANNED_AT      TIMESTAMP WITH TIME ZONE    DEFAULT SYSTIMESTAMP NOT NULL,
    SCAN_SOURCE     VARCHAR2(30 CHAR)           DEFAULT 'WEB' NOT NULL, -- WEB|ANDROID|API
    ACTION_TAKEN    VARCHAR2(100 CHAR),                                  -- aksi yang dilakukan setelah scan
    QR_URL          VARCHAR2(2000 CHAR),                                 -- full URL yang di-scan
    USER_AGENT      VARCHAR2(500 CHAR),                                  -- browser/device info
    CONSTRAINT PK_RT_QR_SCAN PRIMARY KEY (OBJECT_ID),
    CONSTRAINT CK_RT_QR_SCAN_SRC CHECK (SCAN_SOURCE IN ('WEB','ANDROID','API'))
);

CREATE INDEX IF NOT EXISTS IDX_QR_CONFIG_PAGE ON QR_CONFIG (PAGE_ID);
CREATE INDEX IF NOT EXISTS IDX_RT_QR_SCAN_AT ON RT_QR_SCAN_LOG (SCANNED_AT);
CREATE INDEX IF NOT EXISTS IDX_RT_QR_SCAN_ENTITY ON RT_QR_SCAN_LOG (APP_CODE, PAGE_CODE, ENTITY_KEY);
