-- =============================================================================
-- 97_field_config.sql
-- Menambahkan kolom UI_FIELD.FIELD_CONFIG (CLOB berisi JSON) untuk menyimpan
-- opsi format per field (mis. default tanggal SYSDATE, dengan/tanpa jam,
-- jumlah desimal & pemisah ribuan untuk number).
--
-- Idempotent: aman dijalankan berulang (hanya ALTER bila kolom belum ada).
-- =============================================================================

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
      FROM USER_TAB_COLUMNS
     WHERE TABLE_NAME = 'UI_FIELD'
       AND COLUMN_NAME = 'FIELD_CONFIG';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE UI_FIELD ADD (FIELD_CONFIG CLOB)';
    END IF;
END;
/
