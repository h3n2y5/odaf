-- =============================================================================
-- ODAF Sample Business Table - PRODUCT (TANPA metadata)
-- gvenzl menjalankan skrip ini sebagai SYS@CDB$ROOT; alihkan ke PDB & schema ODAF.
ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = ODAF;
--
-- Tabel bisnis contoh untuk mendemonstrasikan Metadata Scaffolder (ODAF Studio).
-- Tabel ini sengaja TIDAK memiliki metadata; jalankan:
--   php artisan odaf:scaffold PRODUCT --app=ODAF_DEMO --label="Produk" --compile
-- untuk menghasilkan dataset/form/grid/menu/validasi otomatis.
-- =============================================================================

CREATE TABLE IF NOT EXISTS PRODUCT (
    PRODUCT_ID    RAW(16)                    DEFAULT SYS_GUID() NOT NULL,
    PRODUCT_CODE  VARCHAR2(50 CHAR)          NOT NULL,
    PRODUCT_NAME  VARCHAR2(200 CHAR)         NOT NULL,
    DESCRIPTION   VARCHAR2(1000 CHAR),
    UNIT_PRICE    NUMBER(15, 2)              DEFAULT 0 NOT NULL,
    STOCK_QTY     NUMBER(10)                 DEFAULT 0 NOT NULL,
    ACTIVE_FLAG   NUMBER(1)                  DEFAULT 1 NOT NULL,
    CREATED_AT    TIMESTAMP WITH TIME ZONE   DEFAULT SYSTIMESTAMP NOT NULL,
    UPDATED_AT    TIMESTAMP WITH TIME ZONE   DEFAULT SYSTIMESTAMP NOT NULL,
    CREATED_BY    RAW(16),
    UPDATED_BY    RAW(16),
    DELETED_AT    TIMESTAMP WITH TIME ZONE,
    DELETED_BY    RAW(16),
    VERSION_NO    NUMBER(10)                 DEFAULT 1 NOT NULL,
    CONSTRAINT PK_PRODUCT PRIMARY KEY (PRODUCT_ID),
    CONSTRAINT UK_PRODUCT_CODE UNIQUE (PRODUCT_CODE),
    CONSTRAINT CK_PRODUCT_ACTIVE CHECK (ACTIVE_FLAG IN (0, 1))
);
