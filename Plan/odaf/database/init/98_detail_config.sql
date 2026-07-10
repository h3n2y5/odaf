-- =============================================================================
-- 98_detail_config.sql
-- Menambahkan kolom UI_PAGE.DETAIL_CONFIG (CLOB berisi JSON) untuk mendukung
-- fitur header-detail (master-detail): sebuah halaman FORM (header) dapat memiliki
-- satu atau lebih grid detail. Format JSON:
--   [{ "pageCode": "PAGE_T_PO_LINE", "fkColumn": "PO_ID", "title": "Baris" }]
--
-- Idempotent: aman dijalankan berulang.
-- =============================================================================

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
      FROM USER_TAB_COLUMNS
     WHERE TABLE_NAME = 'UI_PAGE'
       AND COLUMN_NAME = 'DETAIL_CONFIG';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE UI_PAGE ADD (DETAIL_CONFIG CLOB)';
    END IF;
END;
/
