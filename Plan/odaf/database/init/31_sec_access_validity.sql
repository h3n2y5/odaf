-- =============================================================================
-- 31_sec_access_validity.sql
-- Migration idempotent: menambah kolom masa berlaku (VALID_FROM / VALID_TO)
-- pada SEC_USER_ROLE dan SEC_ACCESS, serta hierarki role (PARENT_ROLE_ID)
-- pada SEC_ROLE.
--
-- Aman dijalankan berulang: setiap ALTER hanya dieksekusi bila kolom belum ada.
-- =============================================================================

ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = ODAF;

-- ---------------------------------------------------------------------------
-- 1. SEC_ROLE: Hierarki role (PARENT_ROLE_ID → SEC_ROLE self-reference)
--    Jika role A memiliki PARENT_ROLE_ID = B, maka A mewarisi semua akses B.
--    NULL berarti role tanpa parent (root).
-- ---------------------------------------------------------------------------
DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM USER_TAB_COLUMNS
    WHERE TABLE_NAME = 'SEC_ROLE' AND COLUMN_NAME = 'PARENT_ROLE_ID';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE SEC_ROLE ADD PARENT_ROLE_ID RAW(16)';
        EXECUTE IMMEDIATE 'ALTER TABLE SEC_ROLE ADD CONSTRAINT FK_SEC_ROLE_PARENT
            FOREIGN KEY (PARENT_ROLE_ID) REFERENCES SEC_ROLE (OBJECT_ID)';
        EXECUTE IMMEDIATE 'CREATE INDEX IDX_SEC_ROLE_PARENT ON SEC_ROLE (PARENT_ROLE_ID)';
    END IF;
END;
/

-- ---------------------------------------------------------------------------
-- 2. SEC_USER_ROLE: Masa berlaku keanggotaan role
--    VALID_FROM = kapan user mulai punya role (NULL = sejak dulu / tidak dibatasi)
--    VALID_TO   = kapan keanggotaan berakhir (NULL = tidak pernah kedaluwarsa)
-- ---------------------------------------------------------------------------
DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM USER_TAB_COLUMNS
    WHERE TABLE_NAME = 'SEC_USER_ROLE' AND COLUMN_NAME = 'VALID_FROM';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE SEC_USER_ROLE ADD VALID_FROM DATE';
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM USER_TAB_COLUMNS
    WHERE TABLE_NAME = 'SEC_USER_ROLE' AND COLUMN_NAME = 'VALID_TO';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE SEC_USER_ROLE ADD VALID_TO DATE';
    END IF;
END;
/

-- ---------------------------------------------------------------------------
-- 3. SEC_ACCESS: Masa berlaku aturan akses
--    VALID_FROM = kapan aturan mulai berlaku (NULL = langsung berlaku)
--    VALID_TO   = kapan aturan kedaluwarsa (NULL = berlaku selamanya)
-- ---------------------------------------------------------------------------
DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM USER_TAB_COLUMNS
    WHERE TABLE_NAME = 'SEC_ACCESS' AND COLUMN_NAME = 'VALID_FROM';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE SEC_ACCESS ADD VALID_FROM DATE';
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM USER_TAB_COLUMNS
    WHERE TABLE_NAME = 'SEC_ACCESS' AND COLUMN_NAME = 'VALID_TO';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE SEC_ACCESS ADD VALID_TO DATE';
    END IF;
END;
/
