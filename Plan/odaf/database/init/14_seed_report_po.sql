-- =============================================================================
-- 14. SEED REPORT PO DEMO
-- Menambahkan template dokumen cetak untuk Purchase Order (ODAF Demo)
-- =============================================================================
ALTER SESSION SET CONTAINER = FREEPDB1;
ALTER SESSION SET CURRENT_SCHEMA = ODAF;

DECLARE
    v_po_page_id RAW(16);
    v_app_id RAW(16);
    v_html_content CLOB;
    v_css_content CLOB;
BEGIN
    -- Cari APP_ID dan PAGE_ID untuk form Purchase Order
    SELECT OBJECT_ID INTO v_app_id FROM APP_APPLICATION WHERE OBJECT_CODE = 'DEMO';
    SELECT OBJECT_ID INTO v_po_page_id FROM UI_PAGE WHERE OBJECT_CODE = 'FRM_PO' AND APPLICATION_ID = v_app_id;

    -- HTML Content (Mustache style)
    v_html_content := TO_CLOB('
<div class="header-section">
    <table width="100%">
        <tr>
            <td width="50%">
                <h1 style="margin-top: 0; color: #1e293b;">PURCHASE ORDER</h1>
                <p><b>PO Number:</b> {{ PO_NO }}<br>
                <b>Date:</b> {{ PO_DATE }}<br>
                <b>Supplier:</b> {{ SUPPLIER_NAME }}</p>
            </td>
            <td width="50%" align="right">
                <div style="float: right; text-align: center;">
                    {{ QR_CODE }}
                    <div style="font-size: 10px; color: #64748b; margin-top: 5px;">Scan to Verify/Receive</div>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="supplier-section">
    <h3>Bill To / Ship To:</h3>
    <p>
        <strong>Nexus Corporation Headquarter</strong><br>
        Jl. Sudirman Kav 1, Jakarta Pusat, 10220<br>
        Attn: Purchasing Dept
    </p>
</div>

<div class="items-section">
    <table class="items-table">
        <thead>
            <tr>
                <th align="left">Item Code</th>
                <th align="left">Description</th>
                <th align="right">Qty</th>
                <th align="right">Unit Price</th>
                <th align="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            {{#FRM_PO_DETAIL}}
            <tr>
                <td>{{ ITEM_CODE }}</td>
                <td>{{ ITEM_NAME }}</td>
                <td align="right">{{ QTY }}</td>
                <td align="right">{{ UNIT_PRICE }}</td>
                <td align="right">{{ AMOUNT }}</td>
            </tr>
            {{/FRM_PO_DETAIL}}
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" align="right">Total Amount:</th>
                <th align="right">{{ TOTAL_AMOUNT }}</th>
            </tr>
        </tfoot>
    </table>
</div>

<div class="footer-section">
    <table width="100%" style="margin-top: 50px;">
        <tr>
            <td width="33%" align="center">
                <p>Prepared By</p>
                <br><br><br>
                <p>___________________<br>Purchasing Staff</p>
            </td>
            <td width="33%" align="center">
            </td>
            <td width="33%" align="center">
                <p>Approved By</p>
                <br><br><br>
                <p>___________________<br>Purchasing Manager</p>
            </td>
        </tr>
    </table>
</div>
');

    -- CSS Content
    v_css_content := TO_CLOB('
.header-section { margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
.supplier-section { margin-bottom: 30px; background: #f8fafc; padding: 15px; border-radius: 8px; }
.supplier-section h3 { margin-top: 0; margin-bottom: 10px; font-size: 14px; color: #475569; }
.supplier-section p { margin: 0; line-height: 1.5; }
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
.items-table th, .items-table td { border: 1px solid #cbd5e1; padding: 10px; font-size: 13px; }
.items-table th { background-color: #f1f5f9; color: #334155; }
.items-table tfoot th { background-color: #e2e8f0; font-size: 14px; }
.footer-section { font-size: 13px; color: #475569; }
');

    INSERT INTO RPT_TEMPLATE (
        OBJECT_ID, APPLICATION_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME, 
        PAGE_SIZE, ORIENTATION, HTML_CONTENT, CSS_CONTENT, IS_DEFAULT, STATUS
    ) VALUES (
        SYS_GUID(), v_app_id, v_po_page_id, 'RPT_PO_STANDARD', 'Standard Purchase Order Print',
        'A4', 'PORTRAIT', v_html_content, v_css_content, 1, 'PUBLISHED'
    );

    COMMIT;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        DBMS_OUTPUT.PUT_LINE('Demo app or page not found, skipping seed.');
END;
/
