-- =============================================================================
-- ODAF Demo Seed Data - QR Code
-- =============================================================================
ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = ODAF;

DECLARE
    v_po_page_id RAW(16);
    v_gr_page_id RAW(16);
BEGIN
    -- Cari PAGE_ID untuk form Purchase Order
    SELECT OBJECT_ID INTO v_po_page_id FROM UI_PAGE 
    WHERE OBJECT_CODE = 'FRM_PURCHASE_ORDER' AND ROWNUM = 1;
    
    -- Cari PAGE_ID untuk form Good Receipt
    SELECT OBJECT_ID INTO v_gr_page_id FROM UI_PAGE 
    WHERE OBJECT_CODE = 'FRM_GOOD_RECEIPT' AND ROWNUM = 1;

    -- 1. QR Code pada form Purchase Order (Cross-Document ke Good Receipt)
    INSERT INTO QR_CONFIG (
        PAGE_ID, OBJECT_CODE, OBJECT_NAME, QR_TYPE, 
        TARGET_APP_CODE, TARGET_PAGE_CODE, TARGET_ACTION, FK_COLUMN, 
        POSITION, SIZE_PX, SHOW_ON_FORM, SHOW_ON_PRINT, STATUS
    ) VALUES (
        v_po_page_id, 'QR_PO_TO_GR', 'QR Code Penerimaan Barang', 'CROSS_DOCUMENT',
        'ODAF_DEMO', 'FRM_GOOD_RECEIPT', 'RECEIVE', 'PO_ID',
        'TOP_RIGHT', 150, 1, 1, 'PUBLISHED'
    );
    
    -- 2. QR Code pada form Good Receipt (Workflow Action Approve)
    INSERT INTO QR_CONFIG (
        PAGE_ID, OBJECT_CODE, OBJECT_NAME, QR_TYPE, 
        TARGET_APP_CODE, TARGET_PAGE_CODE, TARGET_ACTION, FK_COLUMN, 
        POSITION, SIZE_PX, SHOW_ON_FORM, SHOW_ON_PRINT, STATUS
    ) VALUES (
        v_gr_page_id, 'QR_GR_APPROVE', 'QR Code Approval GR', 'WORKFLOW_ACTION',
        'ODAF_DEMO', 'FRM_GOOD_RECEIPT', 'APPROVE', NULL,
        'TOP_RIGHT', 150, 1, 1, 'PUBLISHED'
    );

    COMMIT;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        DBMS_OUTPUT.PUT_LINE('Page FRM_PURCHASE_ORDER atau FRM_GOOD_RECEIPT tidak ditemukan. Skip insert demo QR.');
END;
/
