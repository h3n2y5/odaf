-- =============================================================================
-- Database Link NEXUS -> IFS (IFSAPP @ 10.23.31.40/PDBAALD1)
-- gvenzl menjalankan skrip ini sebagai SYS@CDB$ROOT; alihkan ke PDB FREEPDB1.
ALTER SESSION SET CONTAINER = FREEPDB1;
--
-- PERINGATAN: file ini memuat kredensial (IFSAPP). Untuk lingkungan dev/internal.
-- Jangan commit ke repo publik / sesuaikan kredensial untuk produksi.
-- PUBLIC link agar dapat dipakai schema ODAF (LOV SQL memakai @NEXUS).
-- =============================================================================

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count FROM DBA_DB_LINKS WHERE DB_LINK = 'NEXUS' AND OWNER = 'PUBLIC';
    IF v_count > 0 THEN
        EXECUTE IMMEDIATE 'DROP PUBLIC DATABASE LINK NEXUS';
    END IF;
    EXECUTE IMMEDIATE 'CREATE PUBLIC DATABASE LINK NEXUS '
        || 'CONNECT TO IFSAPP IDENTIFIED BY ifsapp '
        || 'USING ''10.23.31.40:1521/PDBAALD1''';
END;
/
