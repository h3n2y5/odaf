# ✅ LOV Samples - READY TO USE!

**Status:** ✅ Installed & Compiled  
**File:** `database/init/96_quick_lov_samples.sql`  
**Last Compiled:** Package version 1.6e20c18b46b6

---

## 🎉 Yang Sudah Dibuat

### **5 LOV Definitions:**

| LOV Code | Type | Description | Use Case |
|----------|------|-------------|----------|
| `LOV_CUSTOMER_TYPE` | STATIC (array) | Retail, Grosir, Distributor, Agen | Customer type selector |
| `LOV_PRODUCT_STATUS` | STATIC (object) | Tersedia, Stok Habis, Dihentikan, Pre-Order | Product status |
| `LOV_PRIORITY` | STATIC (simple) | Low, Medium, High, Critical | Priority level |
| `LOV_CUSTOMERS` | SQL | Top 10 customers dari tabel CUSTOMER | Reference to other customer |
| `LOV_PRODUCTS` | SQL | Top 10 products dari tabel PRODUCT | Reference to other product |

### **7 New Fields:**

**Customer Form:**
- ✅ **Tipe Pelanggan** (CUSTOMER_TYPE) → Dropdown LOV_CUSTOMER_TYPE
- ✅ **Referensi Pelanggan** (CUSTOMER_REF_ID) → Dropdown LOV_CUSTOMERS
- ✅ **Nama Referensi** (CUSTOMER_REF_NAME) → Readonly companion

**Product Form:**
- ✅ **Status Produk** (PRODUCT_STATUS) → Dropdown LOV_PRODUCT_STATUS
- ✅ **Prioritas** (PRIORITY) → Dropdown LOV_PRIORITY
- ✅ **Referensi Produk** (PRODUCT_REF_ID) → Dropdown LOV_PRODUCTS
- ✅ **Nama Referensi** (PRODUCT_REF_NAME) → Readonly companion

---

## 🚀 Test Sekarang!

### **1. Buka Customer Form**

```
URL: http://localhost:8080/app/ODAF_DEMO
Login: admin / password
Menu: Customer → + Baru
```

**Field baru yang muncul:**

```
┌────────────────────────────────────────────┐
│ Kode Pelanggan     [____________]          │
│ Nama Pelanggan     [____________]          │
│ Email              [____________]          │
│ Batas Kredit       [____________]          │
│ Customer Group     [ -- pilih --   ▼ ]    │ (existing)
│                                            │
│ Tipe Pelanggan     [ -- pilih --   ▼ ]    │ ← NEW!
│                     ├ Retail              │
│                     ├ Grosir              │
│                     ├ Distributor         │
│                     └ Agen                │
│                                            │
│ Referensi Pel...   [ -- pilih --   ▼ ]    │ ← NEW!
│                     ├ CUST-001 - PT...    │
│                     ├ CUST-002 - CV...    │
│                     └ ...                 │
│ Nama Referensi     [____________]          │ ← Auto-fill!
│                                            │
│ [Simpan] [Batal]                           │
└────────────────────────────────────────────┘
```

---

### **2. Test STATIC LOV (3 Format)**

#### **Format 1: Array of Objects** (CUSTOMER_TYPE)
```json
[
  {"value":"RETAIL","label":"Retail"},
  {"value":"WHOLESALE","label":"Grosir"}
]
```
- ✅ Pisah code & label
- ✅ Value: RETAIL, Label: Retail
- ✅ Grid show: Retail (bukan RETAIL)

#### **Format 2: Object/Map** (PRODUCT_STATUS)
```json
{
  "AVAILABLE":"Tersedia",
  "OUT_OF_STOCK":"Stok Habis"
}
```
- ✅ Lebih simple untuk write
- ✅ Key as value, value as label
- ✅ Grid show: Tersedia (bukan AVAILABLE)

#### **Format 3: Simple Array** (PRIORITY)
```json
["Low","Medium","High","Critical"]
```
- ✅ Paling simple
- ✅ Value = label
- ✅ Grid show: Low, Medium, High

---

### **3. Test SQL LOV (from existing tables)**

#### **LOV_CUSTOMERS** (Reference dropdown)

**SOURCE_QUERY:**
```sql
SELECT RAWTOHEX(CUSTOMER_ID) AS CUSTOMER_ID, 
       CUSTOMER_CODE || ' - ' || CUSTOMER_NAME AS DISPLAY_NAME 
FROM CUSTOMER 
WHERE ROWNUM <= 10 
ORDER BY CUSTOMER_NAME
```

**Features:**
- ✅ Query existing CUSTOMER table
- ✅ Concatenate columns: "CUST-001 - PT Maju Jaya"
- ✅ Limit rows (ROWNUM <= 10) for performance
- ✅ RAWTOHEX for RAW(16) primary key

**Field:**
- `CUSTOMER_REF_ID` = dropdown (ID tersimpan)
- `CUSTOMER_REF_NAME` = readonly companion (name auto-fill)

**Use Case:**
- Parent-child relationship (referral customer)
- Related records selection
- Foreign key dropdown

---

### **4. Test Companion Field (Auto-fill)**

**Scenario:**
1. Buka Customer form → Baru
2. Pilih **Referensi Pelanggan** = "CUST-001 - PT Maju Jaya"
3. ✅ Field **Nama Referensi** otomatis terisi "CUST-001 - PT Maju Jaya"
4. Simpan
5. ✅ Kedua kolom tersimpan:
   - `CUSTOMER_REF_ID` = RAW(16) hex
   - `CUSTOMER_REF_NAME` = "CUST-001 - PT Maju Jaya"

**Benefit:**
- Grid bisa show name tanpa LOV lookup
- User bisa lihat full name tanpa re-open dropdown
- Report bisa pakai name langsung (no join)

---

## 📊 Comparison: 3 STATIC Formats

| Format | Pros | Cons | Use When |
|--------|------|------|----------|
| **Array of Objects** | Separate code/label, extensible | More verbose | Need code≠label, future metadata |
| **Object/Map** | Simple, readable | Limited to key-value | Quick setup, code≠label |
| **Simple Array** | Most simple | Value=label only | Self-explanatory values |

**Example:**
```json
// Array of Objects - Best for extensibility
[{"value":"A","label":"Active","color":"green","icon":"check"}]

// Object/Map - Best for simple mapping
{"A":"Active","I":"Inactive","P":"Pending"}

// Simple Array - Best for self-explanatory
["Pending","Approved","Rejected"]
```

---

## 💡 Next Steps: Tambah LOV Sendiri

### **Via Studio (UI):**

```
1. Studio → DS_LOV → + Baru
2. Isi form:
   Object Code:  LOV_YOUR_NAME
   Object Name:  Your LOV Display Name
   LOV Type:     STATIC (atau SQL/VIEW)
   Source Query: [JSON atau SQL query]
   Status:       PUBLISHED
3. Save
4. Studio → UI_FIELD → Baru/Ubah field target
5. Set LOV Id: (pilih LOV_YOUR_NAME)
6. Save
7. Klik ⚡ Kompilasi & Aktifkan
8. Refresh browser → field jadi dropdown!
```

### **Via SQL (Direct Insert):**

```sql
-- 1. Create LOV
INSERT INTO DS_LOV (
    OBJECT_ID, OBJECT_CODE, OBJECT_NAME,
    LOV_TYPE, SOURCE_QUERY,
    STATUS, CREATED_AT, CREATED_BY, VERSION_NO
) VALUES (
    SYS_GUID(), 'LOV_MY_STATUS', 'My Status',
    'STATIC', '["New","In Progress","Done"]',
    'PUBLISHED', SYSTIMESTAMP, 
    (SELECT OBJECT_ID FROM SEC_USER WHERE OBJECT_CODE='admin'), 
    1
);

-- 2. Link to field (update existing or insert new)
UPDATE UI_FIELD 
SET LOV_ID = (SELECT OBJECT_ID FROM DS_LOV WHERE OBJECT_CODE='LOV_MY_STATUS')
WHERE OBJECT_CODE = 'FIELD_MY_STATUS';

COMMIT;

-- 3. Compile
-- docker compose exec app php artisan odaf:compile ODAF_DEMO --activate
```

---

## 🎓 Advanced Examples

### **1. LOV dengan Calculation**

```sql
LOV_TYPE: SQL
SOURCE_QUERY:
  SELECT 
    RAWTOHEX(CUSTOMER_ID) AS ID,
    CUSTOMER_CODE || ' (Rp ' || 
    TO_CHAR(CREDIT_LIMIT, 'FM999,999,999') || ')' AS DISPLAY
  FROM CUSTOMER
  ORDER BY CREDIT_LIMIT DESC
VALUE_COLUMN: ID
LABEL_COLUMN: DISPLAY
```

**Result:** `CUST-001 (Rp 50,000,000)`

---

### **2. LOV dengan Date Filter**

```sql
-- Recent customers (last 30 days)
SELECT RAWTOHEX(CUSTOMER_ID), CUSTOMER_NAME
FROM CUSTOMER
WHERE CREATED_AT >= SYSTIMESTAMP - INTERVAL '30' DAY
ORDER BY CREATED_AT DESC
```

---

### **3. LOV Conditional (Role-based)**

```sql
-- Customers in my sales territory (future enhancement)
SELECT RAWTOHEX(CUSTOMER_ID), CUSTOMER_NAME
FROM CUSTOMER C
JOIN SALES_TERRITORY ST ON C.TERRITORY_ID = ST.TERRITORY_ID
WHERE ST.SALES_REP_ID = HEXTORAW({{CURRENT_USER_ID}})
ORDER BY CUSTOMER_NAME
```

({{CURRENT_USER_ID}} butuh enhancement di runtime context)

---

## 📚 Complete Documentation

Untuk detail lengkap, lihat:
- **`LOV-FEATURE-COVERAGE.md`** - Technical details & testing matrix
- **`QUICK-START-LOV.md`** - Step-by-step guide dengan screenshots
- **`LOV-SAMPLES-COMPLETE.md`** - Full examples dengan cascade/parametric

---

## ✅ Summary

**What's Working:**
- ✅ 5 LOV definitions (3 STATIC, 2 SQL)
- ✅ 3 STATIC formats demonstrated (array/object/simple)
- ✅ SQL LOV dari existing tables
- ✅ Companion fields (auto-fill description)
- ✅ Grid show labels (bukan codes)
- ✅ 7 new fields di Customer & Product forms
- ✅ Already compiled & activated

**Test Now:**
1. Login ke aplikasi
2. Buka Customer form → lihat dropdown baru
3. Pilih dari dropdown → companion field auto-fill
4. Save → data tersimpan
5. Grid → show labels

**Create Your Own:**
1. Via Studio (UI) - easiest
2. Via SQL (direct) - fastest
3. Follow examples di atas

🎉 **Selamat! LOV Samples siap dipakai!**

