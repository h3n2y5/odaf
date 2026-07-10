-- ============================================================================
-- Sample LOV Definitions (STATIC, SQL, VIEW)
-- Menggunakan tabel lokal untuk demo berbagai tipe LOV
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Tabel Master untuk Demo LOV SQL/VIEW
-- ----------------------------------------------------------------------------

-- Tabel Category untuk demo LOV VIEW
CREATE TABLE M_CATEGORY (
    CATEGORY_ID     RAW(16)         DEFAULT SYS_GUID() PRIMARY KEY,
    CATEGORY_CODE   VARCHAR2(50)    NOT NULL UNIQUE,
    CATEGORY_NAME   VARCHAR2(200)   NOT NULL,
    PARENT_ID       RAW(16),
    DESCRIPTION     VARCHAR2(500),
    ACTIVE_FLAG     NUMBER(1)       DEFAULT 1 NOT NULL,
    CREATED_AT      TIMESTAMP       DEFAULT SYSTIMESTAMP NOT NULL,
    CREATED_BY      RAW(16)         NOT NULL,
    UPDATED_AT      TIMESTAMP,
    UPDATED_BY      RAW(16),
    VERSION_NO      NUMBER(10)      DEFAULT 1 NOT NULL,
    CONSTRAINT fk_category_parent FOREIGN KEY (PARENT_ID) REFERENCES M_CATEGORY(CATEGORY_ID),
    CONSTRAINT chk_category_flag CHECK (ACTIVE_FLAG IN (0, 1))
);

-- Seed data Categories
DECLARE
    v_user_id RAW(16);
BEGIN
    -- Get admin user ID  
    SELECT OBJECT_ID INTO v_user_id FROM SEC_USER WHERE OBJECT_CODE = 'admin';
    
    -- Electronics & sub-categories
    INSERT INTO M_CATEGORY (CATEGORY_ID, CATEGORY_CODE, CATEGORY_NAME, PARENT_ID, DESCRIPTION, ACTIVE_FLAG, CREATED_BY)
    VALUES (HEXTORAW('00000000000000000000000000000101'), 'ELECTRONICS', 'Elektronik', NULL, 'Peralatan elektronik', 1, v_user_id);
    
    INSERT INTO M_CATEGORY (CATEGORY_ID, CATEGORY_CODE, CATEGORY_NAME, PARENT_ID, DESCRIPTION, ACTIVE_FLAG, CREATED_BY)
    VALUES (HEXTORAW('00000000000000000000000000000102'), 'PHONE', 'Handphone', HEXTORAW('00000000000000000000000000000101'), 'Smartphone dan telepon', 1, v_user_id);
    
    INSERT INTO M_CATEGORY (CATEGORY_ID, CATEGORY_CODE, CATEGORY_NAME, PARENT_ID, DESCRIPTION, ACTIVE_FLAG, CREATED_BY)
    VALUES (HEXTORAW('00000000000000000000000000000103'), 'LAPTOP', 'Laptop', HEXTORAW('00000000000000000000000000000101'), 'Komputer laptop', 1, v_user_id);
    
    -- Furniture
    INSERT INTO M_CATEGORY (CATEGORY_ID, CATEGORY_CODE, CATEGORY_NAME, PARENT_ID, DESCRIPTION, ACTIVE_FLAG, CREATED_BY)
    VALUES (HEXTORAW('00000000000000000000000000000104'), 'FURNITURE', 'Mebel', NULL, 'Furniture dan perabotan', 1, v_user_id);
    
    INSERT INTO M_CATEGORY (CATEGORY_ID, CATEGORY_CODE, CATEGORY_NAME, PARENT_ID, DESCRIPTION, ACTIVE_FLAG, CREATED_BY)
    VALUES (HEXTORAW('00000000000000000000000000000105'), 'OFFICE', 'Kantor', HEXTORAW('00000000000000000000000000000104'), 'Mebel kantor', 1, v_user_id);
    
    -- Apparel
    INSERT INTO M_CATEGORY (CATEGORY_ID, CATEGORY_CODE, CATEGORY_NAME, PARENT_ID, DESCRIPTION, ACTIVE_FLAG, CREATED_BY)
    VALUES (HEXTORAW('00000000000000000000000000000106'), 'APPAREL', 'Pakaian', NULL, 'Pakaian dan aksesori', 1, v_user_id);
    
    COMMIT;
END;
/

-- View untuk LOV (simplified query)
CREATE OR REPLACE VIEW V_CATEGORY_LOV AS
SELECT 
    RAWTOHEX(CATEGORY_ID) AS CATEGORY_ID_HEX,
    CATEGORY_CODE,
    CATEGORY_NAME,
    ACTIVE_FLAG
FROM M_CATEGORY
WHERE ACTIVE_FLAG = 1
ORDER BY CATEGORY_NAME;

-- Tabel Province untuk demo LOV cascade (Province → City)
CREATE TABLE M_PROVINCE (
    PROVINCE_ID     RAW(16)         DEFAULT SYS_GUID() PRIMARY KEY,
    PROVINCE_CODE   VARCHAR2(10)    NOT NULL UNIQUE,
    PROVINCE_NAME   VARCHAR2(100)   NOT NULL,
    ACTIVE_FLAG     NUMBER(1)       DEFAULT 1 NOT NULL,
    CREATED_AT      TIMESTAMP       DEFAULT SYSTIMESTAMP NOT NULL,
    CREATED_BY      RAW(16)         NOT NULL,
    CONSTRAINT chk_province_flag CHECK (ACTIVE_FLAG IN (0, 1))
);

CREATE TABLE M_CITY (
    CITY_ID         RAW(16)         DEFAULT SYS_GUID() PRIMARY KEY,
    PROVINCE_ID     RAW(16)         NOT NULL,
    CITY_CODE       VARCHAR2(10)    NOT NULL UNIQUE,
    CITY_NAME       VARCHAR2(100)   NOT NULL,
    ACTIVE_FLAG     NUMBER(1)       DEFAULT 1 NOT NULL,
    CREATED_AT      TIMESTAMP       DEFAULT SYSTIMESTAMP NOT NULL,
    CREATED_BY      RAW(16)         NOT NULL,
    CONSTRAINT fk_city_province FOREIGN KEY (PROVINCE_ID) REFERENCES M_PROVINCE(PROVINCE_ID),
    CONSTRAINT chk_city_flag CHECK (ACTIVE_FLAG IN (0, 1))
);

-- Seed Provinces & Cities
DECLARE
    v_user_id RAW(16);
    v_prov_jkt RAW(16) := HEXTORAW('00000000000000000000000000000201');
    v_prov_jbr RAW(16) := HEXTORAW('00000000000000000000000000000202');
    v_prov_jtn RAW(16) := HEXTORAW('00000000000000000000000000000203');
BEGIN
    SELECT OBJECT_ID INTO v_user_id FROM SEC_USER WHERE OBJECT_CODE = 'admin';
    
    -- Provinces
    INSERT INTO M_PROVINCE (PROVINCE_ID, PROVINCE_CODE, PROVINCE_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (v_prov_jkt, 'JKT', 'DKI Jakarta', 1, v_user_id);
    
    INSERT INTO M_PROVINCE (PROVINCE_ID, PROVINCE_CODE, PROVINCE_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (v_prov_jbr, 'JBR', 'Jawa Barat', 1, v_user_id);
    
    INSERT INTO M_PROVINCE (PROVINCE_ID, PROVINCE_CODE, PROVINCE_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (v_prov_jtn, 'JTN', 'Jawa Tengah', 1, v_user_id);
    
    -- Cities - Jakarta
    INSERT INTO M_CITY (CITY_ID, PROVINCE_ID, CITY_CODE, CITY_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (SYS_GUID(), v_prov_jkt, 'JKT-PST', 'Jakarta Pusat', 1, v_user_id);
    
    INSERT INTO M_CITY (CITY_ID, PROVINCE_ID, CITY_CODE, CITY_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (SYS_GUID(), v_prov_jkt, 'JKT-SLT', 'Jakarta Selatan', 1, v_user_id);
    
    INSERT INTO M_CITY (CITY_ID, PROVINCE_ID, CITY_CODE, CITY_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (SYS_GUID(), v_prov_jkt, 'JKT-TIM', 'Jakarta Timur', 1, v_user_id);
    
    -- Cities - Jawa Barat
    INSERT INTO M_CITY (CITY_ID, PROVINCE_ID, CITY_CODE, CITY_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (SYS_GUID(), v_prov_jbr, 'BDG', 'Bandung', 1, v_user_id);
    
    INSERT INTO M_CITY (CITY_ID, PROVINCE_ID, CITY_CODE, CITY_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (SYS_GUID(), v_prov_jbr, 'BKS', 'Bekasi', 1, v_user_id);
    
    INSERT INTO M_CITY (CITY_ID, PROVINCE_ID, CITY_CODE, CITY_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (SYS_GUID(), v_prov_jbr, 'DPK', 'Depok', 1, v_user_id);
    
    -- Cities - Jawa Tengah
    INSERT INTO M_CITY (CITY_ID, PROVINCE_ID, CITY_CODE, CITY_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (SYS_GUID(), v_prov_jtn, 'SMG', 'Semarang', 1, v_user_id);
    
    INSERT INTO M_CITY (CITY_ID, PROVINCE_ID, CITY_CODE, CITY_NAME, ACTIVE_FLAG, CREATED_BY)
    VALUES (SYS_GUID(), v_prov_jtn, 'SKA', 'Surakarta', 1, v_user_id);
    
    COMMIT;
END;
/

-- ----------------------------------------------------------------------------
-- 2. LOV Definitions - Berbagai Tipe
-- ----------------------------------------------------------------------------

DECLARE
    v_app_id RAW(16);
    v_user_id RAW(16);
    v_lov_id RAW(16);
BEGIN
    -- Get IDs
    SELECT OBJECT_ID INTO v_app_id FROM APP_APPLICATION WHERE OBJECT_CODE = 'ODAF_DEMO';
    SELECT OBJECT_ID INTO v_user_id FROM SEC_USER WHERE OBJECT_CODE = 'admin';
    
    -- ========================================================================
    -- LOV #1: STATIC - Customer Type (JSON Array of Objects)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000301');
    INSERT INTO DS_LOV (
        OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, 
        LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_lov_id, v_app_id, 'LOV_CUSTOMER_TYPE', 'Customer Type',
        'STATIC',
        '[{"value":"RETAIL","label":"Retail"},{"value":"WHOLESALE","label":"Grosir"},{"value":"DISTRIBUTOR","label":"Distributor"},{"value":"AGENT","label":"Agen"}]',
        NULL, NULL,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- ========================================================================
    -- LOV #2: STATIC - Product Status (JSON Object/Map)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000302');
    INSERT INTO DS_LOV (
        OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, 
        LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_lov_id, v_app_id, 'LOV_PRODUCT_STATUS', 'Product Status',
        'STATIC',
        '{"AVAILABLE":"Tersedia","OUT_OF_STOCK":"Stok Habis","DISCONTINUED":"Dihentikan","PRE_ORDER":"Pre-Order"}',
        NULL, NULL,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- ========================================================================
    -- LOV #3: STATIC - Priority (Simple Array)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000303');
    INSERT INTO DS_LOV (
        OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, 
        LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_lov_id, v_app_id, 'LOV_PRIORITY', 'Priority Level',
        'STATIC',
        '["Low","Medium","High","Critical"]',
        NULL, NULL,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- ========================================================================
    -- LOV #4: VIEW - Category (dari view)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000304');
    INSERT INTO DS_LOV (
        OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, 
        LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_lov_id, v_app_id, 'LOV_CATEGORY', 'Product Category',
        'VIEW',
        'V_CATEGORY_LOV',
        'CATEGORY_CODE',
        'CATEGORY_NAME',
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- ========================================================================
    -- LOV #5: SQL - Category Active Only (query langsung)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000305');
    INSERT INTO DS_LOV (
        OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, 
        LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_lov_id, v_app_id, 'LOV_CATEGORY_SQL', 'Category (SQL)',
        'SQL',
        'SELECT CATEGORY_CODE, CATEGORY_NAME FROM M_CATEGORY WHERE ACTIVE_FLAG = 1 ORDER BY CATEGORY_NAME',
        'CATEGORY_CODE',
        'CATEGORY_NAME',
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- ========================================================================
    -- LOV #6: SQL - Province (untuk cascade)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000306');
    INSERT INTO DS_LOV (
        OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, 
        LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_lov_id, v_app_id, 'LOV_PROVINCE', 'Province',
        'SQL',
        'SELECT RAWTOHEX(PROVINCE_ID) AS PROVINCE_ID, PROVINCE_NAME FROM M_PROVINCE WHERE ACTIVE_FLAG = 1 ORDER BY PROVINCE_NAME',
        'PROVINCE_ID',
        'PROVINCE_NAME',
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- ========================================================================
    -- LOV #7: SQL - City (parametric, depends on Province)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000307');
    INSERT INTO DS_LOV (
        OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, 
        LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_lov_id, v_app_id, 'LOV_CITY', 'City (by Province)',
        'SQL',
        'SELECT RAWTOHEX(CITY_ID) AS CITY_ID, CITY_NAME FROM M_CITY WHERE PROVINCE_ID = HEXTORAW({{PROVINCE_ID}}) AND ACTIVE_FLAG = 1 ORDER BY CITY_NAME',
        'CITY_ID',
        'CITY_NAME',
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- ========================================================================
    -- LOV #8: SQL - Top 5 Customers (limited rows)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000308');
    INSERT INTO DS_LOV (
        OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, 
        LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_lov_id, v_app_id, 'LOV_TOP_CUSTOMERS', 'Top Customers',
        'SQL',
        'SELECT RAWTOHEX(CUSTOMER_ID) AS CUSTOMER_ID, CUSTOMER_CODE || '' - '' || CUSTOMER_NAME AS DISPLAY_NAME FROM CUSTOMER WHERE ACTIVE_FLAG = 1 AND ROWNUM <= 5 ORDER BY CREDIT_LIMIT DESC',
        'CUSTOMER_ID',
        'DISPLAY_NAME',
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    COMMIT;
END;
/

-- ----------------------------------------------------------------------------
-- 3. Tambah kolom demo ke tabel CUSTOMER & PRODUCT
-- ----------------------------------------------------------------------------

-- Alter CUSTOMER (idempotent)
DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM USER_TAB_COLUMNS 
    WHERE TABLE_NAME = 'CUSTOMER' AND COLUMN_NAME = 'CUSTOMER_TYPE';
    
    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE CUSTOMER ADD CUSTOMER_TYPE VARCHAR2(50)';
    END IF;
    
    SELECT COUNT(*) INTO v_count FROM USER_TAB_COLUMNS 
    WHERE TABLE_NAME = 'CUSTOMER' AND COLUMN_NAME = 'PROVINCE_ID';
    
    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE CUSTOMER ADD PROVINCE_ID RAW(16)';
        EXECUTE IMMEDIATE 'ALTER TABLE CUSTOMER ADD PROVINCE_NAME VARCHAR2(100)';
        EXECUTE IMMEDIATE 'ALTER TABLE CUSTOMER ADD CITY_ID RAW(16)';
        EXECUTE IMMEDIATE 'ALTER TABLE CUSTOMER ADD CITY_NAME VARCHAR2(100)';
    END IF;
END;
/

-- Alter PRODUCT
DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM USER_TAB_COLUMNS 
    WHERE TABLE_NAME = 'PRODUCT' AND COLUMN_NAME = 'CATEGORY_CODE';
    
    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE PRODUCT ADD CATEGORY_CODE VARCHAR2(50)';
        EXECUTE IMMEDIATE 'ALTER TABLE PRODUCT ADD CATEGORY_NAME VARCHAR2(200)';
        EXECUTE IMMEDIATE 'ALTER TABLE PRODUCT ADD PRODUCT_STATUS VARCHAR2(50)';
        EXECUTE IMMEDIATE 'ALTER TABLE PRODUCT ADD PRIORITY VARCHAR2(50)';
    END IF;
END;
/

-- ----------------------------------------------------------------------------
-- 4. UI_FIELD untuk demo LOV
-- ----------------------------------------------------------------------------

DECLARE
    v_page_cust RAW(16);
    v_page_prod RAW(16);
    v_user_id RAW(16);
    v_field_id RAW(16);
BEGIN
    -- Get IDs
    SELECT OBJECT_ID INTO v_page_cust FROM UI_PAGE WHERE OBJECT_CODE = 'PAGE_CUSTOMER';
    SELECT OBJECT_ID INTO v_page_prod FROM UI_PAGE WHERE OBJECT_CODE = 'PAGE_PRODUCT';
    SELECT OBJECT_ID INTO v_user_id FROM SEC_USER WHERE OBJECT_CODE = 'admin';
    
    -- ========================================================================
    -- Customer Fields
    -- ========================================================================
    
    -- Customer Type (STATIC LOV)
    v_field_id := HEXTORAW('00000000000000000000000000000401');
    INSERT INTO UI_FIELD (
        OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
        COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
        LOV_ID, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_field_id, v_page_cust, 'FIELD_CUST_TYPE', 'Customer Type Field',
        'CUSTOMER_TYPE', 'Tipe Pelanggan', 'TEXT', 'STRING',
        HEXTORAW('00000000000000000000000000000301'), -- LOV_CUSTOMER_TYPE
        0, 0, 60,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- Province (untuk cascade demo)
    v_field_id := HEXTORAW('00000000000000000000000000000402');
    INSERT INTO UI_FIELD (
        OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
        COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
        LOV_ID, LOV_LABEL_COLUMN, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_field_id, v_page_cust, 'FIELD_PROVINCE', 'Province Field',
        'PROVINCE_ID', 'Provinsi', 'TEXT', 'STRING',
        HEXTORAW('00000000000000000000000000000306'), -- LOV_PROVINCE
        'PROVINCE_NAME', -- companion field
        0, 0, 70,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- Province Name (readonly, companion)
    v_field_id := HEXTORAW('00000000000000000000000000000403');
    INSERT INTO UI_FIELD (
        OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
        COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
        REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_field_id, v_page_cust, 'FIELD_PROVINCE_NAME', 'Province Name Field',
        'PROVINCE_NAME', 'Nama Provinsi', 'TEXT', 'STRING',
        0, 1, 71,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- City (parametric LOV, depends on Province)
    v_field_id := HEXTORAW('00000000000000000000000000000404');
    INSERT INTO UI_FIELD (
        OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
        COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
        LOV_ID, LOV_LABEL_COLUMN, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_field_id, v_page_cust, 'FIELD_CITY', 'City Field',
        'CITY_ID', 'Kota', 'TEXT', 'STRING',
        HEXTORAW('00000000000000000000000000000307'), -- LOV_CITY (parametric)
        'CITY_NAME',
        0, 0, 80,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- City Name (readonly, companion)
    v_field_id := HEXTORAW('00000000000000000000000000000405');
    INSERT INTO UI_FIELD (
        OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
        COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
        REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_field_id, v_page_cust, 'FIELD_CITY_NAME', 'City Name Field',
        'CITY_NAME', 'Nama Kota', 'TEXT', 'STRING',
        0, 1, 81,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- ========================================================================
    -- Product Fields
    -- ========================================================================
    
    -- Category (VIEW LOV)
    v_field_id := HEXTORAW('00000000000000000000000000000406');
    INSERT INTO UI_FIELD (
        OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
        COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
        LOV_ID, LOV_LABEL_COLUMN, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_field_id, v_page_prod, 'FIELD_CATEGORY', 'Category Field',
        'CATEGORY_CODE', 'Kategori', 'TEXT', 'STRING',
        HEXTORAW('00000000000000000000000000000304'), -- LOV_CATEGORY (VIEW)
        'CATEGORY_NAME',
        0, 0, 60,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- Category Name (readonly)
    v_field_id := HEXTORAW('00000000000000000000000000000407');
    INSERT INTO UI_FIELD (
        OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
        COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
        REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_field_id, v_page_prod, 'FIELD_CATEGORY_NAME', 'Category Name Field',
        'CATEGORY_NAME', 'Nama Kategori', 'TEXT', 'STRING',
        0, 1, 61,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- Product Status (STATIC Map)
    v_field_id := HEXTORAW('00000000000000000000000000000408');
    INSERT INTO UI_FIELD (
        OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
        COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
        LOV_ID, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_field_id, v_page_prod, 'FIELD_PROD_STATUS', 'Product Status Field',
        'PRODUCT_STATUS', 'Status Produk', 'TEXT', 'STRING',
        HEXTORAW('00000000000000000000000000000302'), -- LOV_PRODUCT_STATUS
        0, 0, 70,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    -- Priority (STATIC Array)
    v_field_id := HEXTORAW('00000000000000000000000000000409');
    INSERT INTO UI_FIELD (
        OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
        COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
        LOV_ID, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
        STATUS, ACTIVE_FLAG, CREATED_AT, CREATED_BY, VERSION_NO
    ) VALUES (
        v_field_id, v_page_prod, 'FIELD_PRIORITY', 'Priority Field',
        'PRIORITY', 'Prioritas', 'TEXT', 'STRING',
        HEXTORAW('00000000000000000000000000000303'), -- LOV_PRIORITY
        0, 0, 80,
        'PUBLISHED', 1, SYSTIMESTAMP, v_user_id, 1
    );
    
    COMMIT;
END;
/

-- Selesai!
-- Jalankan compile: docker compose exec app php artisan odaf:compile ODAF_DEMO --activate
