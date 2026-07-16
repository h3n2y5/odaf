-- =============================================================================
-- Migrasi: Menambahkan Level APPEND pada SEC_ACCESS
-- =============================================================================

ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = ODAF;

DECLARE
    v_count NUMBER;
BEGIN
    -- 1. Hapus constraint lama
    SELECT COUNT(*) INTO v_count
      FROM USER_CONSTRAINTS
     WHERE TABLE_NAME = 'SEC_ACCESS' AND CONSTRAINT_NAME = 'CK_SEC_ACCESS_LEVEL';

    IF v_count > 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE SEC_ACCESS DROP CONSTRAINT CK_SEC_ACCESS_LEVEL';
    END IF;

    -- 2. Buat constraint baru dengan APPEND
    EXECUTE IMMEDIATE 'ALTER TABLE SEC_ACCESS ADD CONSTRAINT CK_SEC_ACCESS_LEVEL CHECK (ACCESS_LEVEL IN (''FULL'',''APPEND'',''READONLY'',''MASKED'',''NONE''))';
END;
/
COMMIT;
