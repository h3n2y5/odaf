-- ============================================================================
-- Quick LOV Samples (STATIC only untuk demo cepat)
-- Tidak perlu tabel master, langsung STATIC LOV
-- ============================================================================

DECLARE
    v_user_id RAW(16);
    v_lov_id RAW(16);
BEGIN
    -- Get admin user ID
    SELECT OBJECT_ID INTO v_user_id FROM SEC_USER WHERE OBJECT_CODE = 'admin';
    
    -- ========================================================================
    -- LOV #1: STATIC - Customer Type (JSON Array of Objects)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000301');
    MERGE INTO DS_LOV USING DUAL ON (OBJECT_ID = v_lov_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, OBJECT_CODE, OBJECT_NAME, 
            LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_lov_id, 'LOV_CUSTOMER_TYPE', 'Customer Type',
            'STATIC',
            '[{"value":"RETAIL","label":"Retail"},{"value":"WHOLESALE","label":"Grosir"},{"value":"DISTRIBUTOR","label":"Distributor"},{"value":"AGENT","label":"Agen"}]',
            NULL, NULL,
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    -- ========================================================================
    -- LOV #2: STATIC - Product Status (JSON Object/Map)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000302');
    MERGE INTO DS_LOV USING DUAL ON (OBJECT_ID = v_lov_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, OBJECT_CODE, OBJECT_NAME, 
            LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_lov_id, 'LOV_PRODUCT_STATUS', 'Product Status',
            'STATIC',
            '{"AVAILABLE":"Tersedia","OUT_OF_STOCK":"Stok Habis","DISCONTINUED":"Dihentikan","PRE_ORDER":"Pre-Order"}',
            NULL, NULL,
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    -- ========================================================================
    -- LOV #3: STATIC - Priority (Simple Array)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000303');
    MERGE INTO DS_LOV USING DUAL ON (OBJECT_ID = v_lov_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, OBJECT_CODE, OBJECT_NAME, 
            LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_lov_id, 'LOV_PRIORITY', 'Priority Level',
            'STATIC',
            '["Low","Medium","High","Critical"]',
            NULL, NULL,
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    -- ========================================================================
    -- LOV #4: SQL - From CUSTOMER table (demo SQL LOV)
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000304');
    MERGE INTO DS_LOV USING DUAL ON (OBJECT_ID = v_lov_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, OBJECT_CODE, OBJECT_NAME, 
            LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_lov_id, 'LOV_CUSTOMERS', 'All Customers',
            'SQL',
            'SELECT RAWTOHEX(CUSTOMER_ID) AS CUSTOMER_ID, CUSTOMER_CODE || '' - '' || CUSTOMER_NAME AS DISPLAY_NAME FROM CUSTOMER WHERE ROWNUM <= 10 ORDER BY CUSTOMER_NAME',
            'CUSTOMER_ID',
            'DISPLAY_NAME',
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    -- ========================================================================
    -- LOV #5: SQL - From PRODUCT table
    -- ========================================================================
    v_lov_id := HEXTORAW('00000000000000000000000000000305');
    MERGE INTO DS_LOV USING DUAL ON (OBJECT_ID = v_lov_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, OBJECT_CODE, OBJECT_NAME, 
            LOV_TYPE, SOURCE_QUERY, VALUE_COLUMN, LABEL_COLUMN,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_lov_id, 'LOV_PRODUCTS', 'All Products',
            'SQL',
            'SELECT RAWTOHEX(PRODUCT_ID) AS PRODUCT_ID, PRODUCT_CODE || '' - '' || PRODUCT_NAME AS DISPLAY_NAME FROM PRODUCT WHERE ROWNUM <= 10 ORDER BY PRODUCT_NAME',
            'PRODUCT_ID',
            'DISPLAY_NAME',
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    COMMIT;
    DBMS_OUTPUT.PUT_LINE('✓ 5 LOV samples created successfully!');
END;
/

-- ============================================================================
-- Tambah kolom demo ke CUSTOMER
-- ============================================================================

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM USER_TAB_COLUMNS 
    WHERE TABLE_NAME = 'CUSTOMER' AND COLUMN_NAME = 'CUSTOMER_TYPE';
    
    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE CUSTOMER ADD CUSTOMER_TYPE VARCHAR2(50)';
        DBMS_OUTPUT.PUT_LINE('✓ Added CUSTOMER.CUSTOMER_TYPE');
    ELSE
        DBMS_OUTPUT.PUT_LINE('  CUSTOMER.CUSTOMER_TYPE already exists');
    END IF;
    
    SELECT COUNT(*) INTO v_count FROM USER_TAB_COLUMNS 
    WHERE TABLE_NAME = 'CUSTOMER' AND COLUMN_NAME = 'CUSTOMER_REF_ID';
    
    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE CUSTOMER ADD CUSTOMER_REF_ID RAW(16)';
        EXECUTE IMMEDIATE 'ALTER TABLE CUSTOMER ADD CUSTOMER_REF_NAME VARCHAR2(200)';
        DBMS_OUTPUT.PUT_LINE('✓ Added CUSTOMER.CUSTOMER_REF_ID (reference field)');
    ELSE
        DBMS_OUTPUT.PUT_LINE('  CUSTOMER.CUSTOMER_REF_ID already exists');
    END IF;
END;
/

-- ============================================================================
-- Tambah kolom demo ke PRODUCT
-- ============================================================================

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM USER_TAB_COLUMNS 
    WHERE TABLE_NAME = 'PRODUCT' AND COLUMN_NAME = 'PRODUCT_STATUS';
    
    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE PRODUCT ADD PRODUCT_STATUS VARCHAR2(50)';
        DBMS_OUTPUT.PUT_LINE('✓ Added PRODUCT.PRODUCT_STATUS');
    ELSE
        DBMS_OUTPUT.PUT_LINE('  PRODUCT.PRODUCT_STATUS already exists');
    END IF;
    
    SELECT COUNT(*) INTO v_count FROM USER_TAB_COLUMNS 
    WHERE TABLE_NAME = 'PRODUCT' AND COLUMN_NAME = 'PRIORITY';
    
    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE PRODUCT ADD PRIORITY VARCHAR2(50)';
        DBMS_OUTPUT.PUT_LINE('✓ Added PRODUCT.PRIORITY');
    ELSE
        DBMS_OUTPUT.PUT_LINE('  PRODUCT.PRIORITY already exists');
    END IF;
    
    SELECT COUNT(*) INTO v_count FROM USER_TAB_COLUMNS 
    WHERE TABLE_NAME = 'PRODUCT' AND COLUMN_NAME = 'PRODUCT_REF_ID';
    
    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE PRODUCT ADD PRODUCT_REF_ID RAW(16)';
        EXECUTE IMMEDIATE 'ALTER TABLE PRODUCT ADD PRODUCT_REF_NAME VARCHAR2(200)';
        DBMS_OUTPUT.PUT_LINE('✓ Added PRODUCT.PRODUCT_REF_ID (reference field)');
    ELSE
        DBMS_OUTPUT.PUT_LINE('  PRODUCT.PRODUCT_REF_ID already exists');
    END IF;
END;
/

-- ============================================================================
-- UI_FIELD untuk demo LOV (idempotent via MERGE)
-- ============================================================================

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
    
    -- Customer Type (STATIC LOV - array of objects)
    v_field_id := HEXTORAW('00000000000000000000000000000401');
    MERGE INTO UI_FIELD USING DUAL ON (OBJECT_ID = v_field_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
            COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
            LOV_ID, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_field_id, v_page_cust, 'FIELD_CUST_TYPE', 'Customer Type Field',
            'CUSTOMER_TYPE', 'Tipe Pelanggan', 'TEXT', 'STRING',
            HEXTORAW('00000000000000000000000000000301'), -- LOV_CUSTOMER_TYPE
            0, 0, 60,
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    -- Customer Reference (SQL LOV with companion)
    v_field_id := HEXTORAW('00000000000000000000000000000402');
    MERGE INTO UI_FIELD USING DUAL ON (OBJECT_ID = v_field_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
            COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
            LOV_ID, LOV_LABEL_COLUMN, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_field_id, v_page_cust, 'FIELD_CUST_REF', 'Customer Reference Field',
            'CUSTOMER_REF_ID', 'Referensi Pelanggan', 'TEXT', 'STRING',
            HEXTORAW('00000000000000000000000000000304'), -- LOV_CUSTOMERS
            'CUSTOMER_REF_NAME', -- companion
            0, 0, 70,
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    -- Customer Reference Name (readonly companion)
    v_field_id := HEXTORAW('00000000000000000000000000000403');
    MERGE INTO UI_FIELD USING DUAL ON (OBJECT_ID = v_field_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
            COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
            REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_field_id, v_page_cust, 'FIELD_CUST_REF_NAME', 'Customer Reference Name',
            'CUSTOMER_REF_NAME', 'Nama Referensi', 'TEXT', 'STRING',
            0, 1, 71,
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    -- ========================================================================
    -- Product Fields
    -- ========================================================================
    
    -- Product Status (STATIC LOV - object/map)
    v_field_id := HEXTORAW('00000000000000000000000000000404');
    MERGE INTO UI_FIELD USING DUAL ON (OBJECT_ID = v_field_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
            COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
            LOV_ID, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_field_id, v_page_prod, 'FIELD_PROD_STATUS', 'Product Status Field',
            'PRODUCT_STATUS', 'Status Produk', 'TEXT', 'STRING',
            HEXTORAW('00000000000000000000000000000302'), -- LOV_PRODUCT_STATUS
            0, 0, 60,
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    -- Priority (STATIC LOV - simple array)
    v_field_id := HEXTORAW('00000000000000000000000000000405');
    MERGE INTO UI_FIELD USING DUAL ON (OBJECT_ID = v_field_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
            COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
            LOV_ID, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_field_id, v_page_prod, 'FIELD_PRIORITY', 'Priority Field',
            'PRIORITY', 'Prioritas', 'TEXT', 'STRING',
            HEXTORAW('00000000000000000000000000000303'), -- LOV_PRIORITY
            0, 0, 70,
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    -- Product Reference (SQL LOV with companion)
    v_field_id := HEXTORAW('00000000000000000000000000000406');
    MERGE INTO UI_FIELD USING DUAL ON (OBJECT_ID = v_field_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
            COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
            LOV_ID, LOV_LABEL_COLUMN, REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_field_id, v_page_prod, 'FIELD_PROD_REF', 'Product Reference Field',
            'PRODUCT_REF_ID', 'Referensi Produk', 'TEXT', 'STRING',
            HEXTORAW('00000000000000000000000000000305'), -- LOV_PRODUCTS
            'PRODUCT_REF_NAME',
            0, 0, 80,
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    -- Product Reference Name (readonly companion)
    v_field_id := HEXTORAW('00000000000000000000000000000407');
    MERGE INTO UI_FIELD USING DUAL ON (OBJECT_ID = v_field_id)
    WHEN NOT MATCHED THEN
        INSERT (
            OBJECT_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
            COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE,
            REQUIRED_FLAG, READONLY_FLAG, DISPLAY_ORDER,
            STATUS, CREATED_AT, CREATED_BY, VERSION_NO
        ) VALUES (
            v_field_id, v_page_prod, 'FIELD_PROD_REF_NAME', 'Product Reference Name',
            'PRODUCT_REF_NAME', 'Nama Referensi', 'TEXT', 'STRING',
            0, 1, 81,
            'PUBLISHED', SYSTIMESTAMP, v_user_id, 1
        );
    
    COMMIT;
    DBMS_OUTPUT.PUT_LINE('✓ 7 UI fields created successfully!');
END;
/

-- Selesai!
-- Jalankan compile: docker compose exec app php artisan odaf:compile ODAF_DEMO --activate
PROMPT
PROMPT ========================================================================
PROMPT LOV Samples created successfully!
PROMPT - 3 STATIC LOVs (array/object/simple)
PROMPT - 2 SQL LOVs (from existing tables)
PROMPT - New fields added to Customer & Product forms
PROMPT
PROMPT Next step: docker compose exec app php artisan odaf:compile ODAF_DEMO --activate
PROMPT ========================================================================
