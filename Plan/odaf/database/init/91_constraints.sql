-- =============================================================================
-- ODAF Deferred Constraints (dijalankan setelah semua tabel & seed ada)
-- gvenzl menjalankan skrip ini sebagai SYS@CDB$ROOT; alihkan ke PDB & schema ODAF.
ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = ODAF;
--
-- APP_MENU.PAGE_ID mereferensi UI_PAGE, namun FK tidak dapat dibuat inline
-- (APP_MENU dibuat sebelum UI_PAGE). Ditambahkan di sini agar PAGE_ID otomatis
-- tampil sebagai dropdown di ODAF Studio.
-- =============================================================================

-- Null-kan PAGE_ID yang tidak menunjuk halaman valid agar FK dapat divalidasi.
UPDATE APP_MENU SET PAGE_ID = NULL
 WHERE PAGE_ID IS NOT NULL
   AND PAGE_ID NOT IN (SELECT OBJECT_ID FROM UI_PAGE);

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM USER_CONSTRAINTS WHERE CONSTRAINT_NAME = 'FK_APP_MENU_PAGE';
    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE APP_MENU ADD CONSTRAINT FK_APP_MENU_PAGE '
            || 'FOREIGN KEY (PAGE_ID) REFERENCES UI_PAGE (OBJECT_ID)';
    END IF;
END;
/

COMMIT;
