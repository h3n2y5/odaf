# Sample LOV Lengkap - STATIC, SQL, VIEW

**Tanggal:** 2026-07-09  
**File SQL:** `database/init/95_sample_lov_demo.sql`  

---

## 📦 Yang Disediakan

Script SQL ini membuat:

1. ✅ **3 Tabel Master** (Category, Province, City)
2. ✅ **8 LOV Definitions** (berbagai tipe & use case)
3. ✅ **9 UI Fields** untuk Customer & Product
4. ✅ **Seed Data** siap pakai

---

## 🎯 8 Sample LOV dengan Berbagai Tipe

### **LOV #1: STATIC - Customer Type (Array of Objects)**

**Use Case:** Tipe pelanggan dengan code & label terpisah

```json
[
  {"value":"RETAIL","label":"Retail"},
  {"value":"WHOLESALE","label":"Grosir"},
  {"value":"DISTRIBUTOR","label":"Distributor"},
  {"value":"AGENT","label":"Agen"}
]
```

**Metadata:**
```
LOV_CODE: LOV_CUSTOMER_TYPE
LOV_TYPE: STATIC
SOURCE_QUERY: [JSON array di atas]
```

**Field:**
- `CUSTOMER.CUSTOMER_TYPE` → Dropdown: Retail | Grosir | Distributor | Agen
- Value tersimpan: `RETAIL`, `WHOLESALE`, dst
- Grid show label: "Retail", "Grosir"

---

### **LOV #2: STATIC - Product Status (Object/Map)**

**Use Case:** Simple key-value pairs tanpa perlu array of objects

```json
{
  "AVAILABLE":"Tersedia",
  "OUT_OF_STOCK":"Stok Habis",
  "DISCONTINUED":"Dihentikan",
  "PRE_ORDER":"Pre-Order"
}
```

**Metadata:**
```
LOV_CODE: LOV_PRODUCT_STATUS
LOV_TYPE: STATIC
SOURCE_QUERY: [JSON object di atas]
```

**Field:**
- `PRODUCT.PRODUCT_STATUS` → Dropdown: Tersedia | Stok Habis | ...
- Value tersimpan: `AVAILABLE`, `OUT_OF_STOCK`
- Grid show label: "Tersedia", "Stok Habis"

**Kapan pakai format ini:**
- Lebih simple untuk write
- Tidak butuh extra metadata per option
- Code dan label sudah cukup

---

### **LOV #3: STATIC - Priority (Simple Array)**

**Use Case:** Value dan label sama (tidak perlu pisah)

```json
["Low","Medium","High","Critical"]
```

**Metadata:**
```
LOV_CODE: LOV_PRIORITY
LOV_TYPE: STATIC
SOURCE_QUERY: [array sederhana]
```

**Field:**
- `PRODUCT.PRIORITY` → Dropdown: Low | Medium | High | Critical
- Value tersimpan: `Low`, `Medium`, dst
- Grid show: sama (karena value = label)

**Kapan pakai format ini:**
- Paling simple
- Value tidak perlu translation/mapping
- Cocok untuk status, level, grade yang self-explanatory

---

### **LOV #4: VIEW - Category**

**Use Case:** Query dari database view (pre-defined query)

**View:**
```sql
CREATE VIEW V_CATEGORY_LOV AS
SELECT 
    RAWTOHEX(CATEGORY_ID) AS CATEGORY_ID_HEX,
    CATEGORY_CODE,
    CATEGORY_NAME,
    ACTIVE_FLAG
FROM M_CATEGORY
WHERE ACTIVE_FLAG = 1
ORDER BY CATEGORY_NAME;
```

**Metadata:**
```
LOV_CODE: LOV_CATEGORY
LOV_TYPE: VIEW
SOURCE_QUERY: V_CATEGORY_LOV
VALUE_COLUMN: CATEGORY_CODE
LABEL_COLUMN: CATEGORY_NAME
```

**Field:**
- `PRODUCT.CATEGORY_CODE` → Dropdown: Elektronik | Mebel | Pakaian | ...
- `PRODUCT.CATEGORY_NAME` → Readonly companion field
- Value tersimpan: `ELECTRONICS`, `FURNITURE`
- Description tersimpan: "Elektronik", "Mebel"

**Kapan pakai VIEW:**
- Query complex (join, subquery)
- Reusable (view bisa dipakai LOV lain atau report)
- DBA prefer encapsulate logic di view
- Performance (view bisa di-materialize)

---

### **LOV #5: SQL - Category (Query Langsung)**

**Use Case:** Query inline tanpa perlu buat view

**Metadata:**
```
LOV_CODE: LOV_CATEGORY_SQL
LOV_TYPE: SQL
SOURCE_QUERY: SELECT CATEGORY_CODE, CATEGORY_NAME 
              FROM M_CATEGORY 
              WHERE ACTIVE_FLAG = 1 
              ORDER BY CATEGORY_NAME
VALUE_COLUMN: CATEGORY_CODE
LABEL_COLUMN: CATEGORY_NAME
```

**Sama hasil dengan LOV #4**, tapi:
- ✅ Tidak perlu buat view
- ✅ Query visible di metadata (easier debug)
- ❌ Tidak reusable
- ❌ Untuk complex query, view lebih rapi

**Kapan pakai SQL:**
- Query simple (single table, basic WHERE)
- Tidak perlu reuse query
- Rapid development / prototype

---

### **LOV #6: SQL - Province (Master Data)**

**Use Case:** Master provinsi untuk cascade dengan City

**Metadata:**
```
LOV_CODE: LOV_PROVINCE
LOV_TYPE: SQL
SOURCE_QUERY: SELECT RAWTOHEX(PROVINCE_ID) AS PROVINCE_ID, PROVINCE_NAME 
              FROM M_PROVINCE 
              WHERE ACTIVE_FLAG = 1 
              ORDER BY PROVINCE_NAME
VALUE_COLUMN: PROVINCE_ID
LABEL_COLUMN: PROVINCE_NAME
```

**Field:**
- `CUSTOMER.PROVINCE_ID` → Dropdown: DKI Jakarta | Jawa Barat | ...
- `CUSTOMER.PROVINCE_NAME` → Readonly companion
- Value tersimpan: RAW(16) hex (PK)
- Description: "DKI Jakarta"

**Important:** 
- ✅ **RAWTOHEX** untuk convert RAW(16) → hex string
- ✅ VALUE_COLUMN return hex, bukan binary
- ✅ Companion field untuk user-friendly display

---

### **LOV #7: SQL - City (Parametric, Depends on Province)** ⭐

**Use Case:** Cascade dropdown - City depends on selected Province

**Metadata:**
```
LOV_CODE: LOV_CITY
LOV_TYPE: SQL
SOURCE_QUERY: SELECT RAWTOHEX(CITY_ID) AS CITY_ID, CITY_NAME 
              FROM M_CITY 
              WHERE PROVINCE_ID = HEXTORAW({{PROVINCE_ID}})
              AND ACTIVE_FLAG = 1 
              ORDER BY CITY_NAME
VALUE_COLUMN: CITY_ID
LABEL_COLUMN: CITY_NAME
```

**Key:** `{{PROVINCE_ID}}` = parameter dari form!

**Field:**
- `CUSTOMER.CITY_ID` → Dropdown (options depend on PROVINCE_ID)
- `CUSTOMER.CITY_NAME` → Readonly companion

**Behavior:**
1. User pilih **Province** = "DKI Jakarta"
2. Field **Province** di-blur → trigger server update
3. ✅ Dropdown **City** otomatis update dengan cities di Jakarta saja:
   - Jakarta Pusat
   - Jakarta Selatan
   - Jakarta Timur
4. User pilih city → tersimpan CITY_ID + CITY_NAME

**Ini LOV paling powerful!** Cascade tanpa custom code.

**Important:**
- ✅ Parameter field otomatis pakai `wire:model.blur` (reaktif)
- ✅ `HEXTORAW({{PROVINCE_ID}})` untuk convert hex back to RAW
- ✅ Query re-execute setiap parameter berubah
- ✅ Dropdown kosong kalau parameter belum diisi (safe)

---

### **LOV #8: SQL - Top Customers (Limited Rows)**

**Use Case:** Dropdown dengan limit rows untuk performance

**Metadata:**
```
LOV_CODE: LOV_TOP_CUSTOMERS
LOV_TYPE: SQL
SOURCE_QUERY: SELECT RAWTOHEX(CUSTOMER_ID) AS CUSTOMER_ID, 
                     CUSTOMER_CODE || ' - ' || CUSTOMER_NAME AS DISPLAY_NAME 
              FROM CUSTOMER 
              WHERE ACTIVE_FLAG = 1 
              AND ROWNUM <= 5 
              ORDER BY CREDIT_LIMIT DESC
VALUE_COLUMN: CUSTOMER_ID
LABEL_COLUMN: DISPLAY_NAME
```

**Features:**
- ✅ `ROWNUM <= 5` untuk limit rows
- ✅ Concatenate columns untuk display (`CUST-001 - PT Maju Jaya`)
- ✅ Order by logic (top customers by credit limit)

**Use Case:**
- Reference field untuk select parent/related record
- Dropdown tidak boleh terlalu banyak options (UX)
- Show most relevant items only

---

## 🎨 Field Definitions

### **Customer Form Fields**

```
1. CUSTOMER_TYPE     → LOV_CUSTOMER_TYPE (STATIC array of objects)
2. PROVINCE_ID       → LOV_PROVINCE (SQL master)
   PROVINCE_NAME     → Companion (readonly)
3. CITY_ID           → LOV_CITY (SQL parametric ← PROVINCE_ID)
   CITY_NAME         → Companion (readonly)
```

### **Product Form Fields**

```
1. CATEGORY_CODE     → LOV_CATEGORY (VIEW)
   CATEGORY_NAME     → Companion (readonly)
2. PRODUCT_STATUS    → LOV_PRODUCT_STATUS (STATIC object/map)
3. PRIORITY          → LOV_PRIORITY (STATIC array)
```

---

## 🚀 Instalasi & Testing

### **Step 1: Apply SQL Script**

Script `95_sample_lov_demo.sql` sudah ada di `database/init/`, akan otomatis dijalankan saat:
- Container pertama kali dibuat, atau
- Volume database di-reset

Untuk apply manual ke database yang sudah running:

```bash
# Copy script ke container
docker cp database/init/95_sample_lov_demo.sql odaf-oracle:/tmp/

# Execute via sqlplus
docker exec -it odaf-oracle sqlplus ODAF/OdafApp2026@FREEPDB1 @/tmp/95_sample_lov_demo.sql
```

**Atau via SQL Developer:**
1. Connect ke ODAF schema
2. Open `95_sample_lov_demo.sql`
3. Run as Script (F5)

---

### **Step 2: Compile Application**

```bash
docker compose exec app php artisan odaf:compile ODAF_DEMO --activate
```

Output:
```
✓ Kompilasi berhasil: ODAF_DEMO
✓ Package diaktifkan.
```

---

### **Step 3: Test di Browser**

#### **Test Customer Form (Cascade LOV)**

1. Buka: `http://localhost:8080/app/ODAF_DEMO`
2. Login: `admin` / `password`
3. Klik menu **Customer**
4. Klik **+ Baru**

**Form akan tampil field baru:**

```
┌────────────────────────────────────────────┐
│ Kode Pelanggan     [____________]          │
│ Nama Pelanggan     [____________]          │
│ Email              [____________]          │
│ Batas Kredit       [____________]          │
│                                            │
│ Tipe Pelanggan     [ Retail        ▼ ]    │ ← STATIC array
│                     ├ Retail              │
│                     ├ Grosir              │
│                     ├ Distributor         │
│                     └ Agen                │
│                                            │
│ Provinsi           [ -- pilih --   ▼ ]    │ ← SQL master
│                     ├ DKI Jakarta         │
│                     ├ Jawa Barat          │
│                     └ Jawa Tengah         │
│ Nama Provinsi      [____________]          │ ← Readonly companion
│                                            │
│ Kota               [ -- pilih --   ▼ ]    │ ← SQL parametric (empty)
│ Nama Kota          [____________]          │ ← Readonly
│                                            │
│ [Simpan] [Batal]                           │
└────────────────────────────────────────────┘
```

**5. Test Cascade:**
- Pilih **Provinsi** = "DKI Jakarta"
- Tab keluar dari field
- ✅ Dropdown **Kota** otomatis terisi:
  - Jakarta Pusat
  - Jakarta Selatan
  - Jakarta Timur
- Field **Nama Provinsi** otomatis terisi "DKI Jakarta"

**6. Pilih City:**
- Pilih **Kota** = "Jakarta Selatan"
- ✅ Field **Nama Kota** otomatis terisi "Jakarta Selatan"

**7. Save:**
- Isi Kode, Nama, Email
- Klik **Simpan**
- ✅ Data tersimpan dengan:
  - `CUSTOMER_TYPE` = `RETAIL`
  - `PROVINCE_ID` = RAW(16)
  - `PROVINCE_NAME` = "DKI Jakarta"
  - `CITY_ID` = RAW(16)
  - `CITY_NAME` = "Jakarta Selatan"

---

#### **Test Product Form (Various LOV Types)**

1. Klik menu **Produk**
2. Klik **+ Baru**

**Form tampil:**

```
┌────────────────────────────────────────────┐
│ Kode Produk        [____________]          │
│ Nama Produk        [____________]          │
│ Deskripsi          [____________]          │
│ Harga              [____________]          │
│                                            │
│ Kategori           [ -- pilih --   ▼ ]    │ ← VIEW LOV
│                     ├ Elektronik          │
│                     ├ Handphone           │
│                     ├ Laptop              │
│                     ├ Mebel               │
│                     └ Pakaian             │
│ Nama Kategori      [____________]          │ ← Readonly
│                                            │
│ Status Produk      [ -- pilih --   ▼ ]    │ ← STATIC object
│                     ├ Tersedia            │
│                     ├ Stok Habis          │
│                     ├ Dihentikan          │
│                     └ Pre-Order           │
│                                            │
│ Prioritas          [ -- pilih --   ▼ ]    │ ← STATIC array
│                     ├ Low                 │
│                     ├ Medium              │
│                     ├ High                │
│                     └ Critical            │
│                                            │
│ [Simpan] [Batal]                           │
└────────────────────────────────────────────┘
```

**3. Test:**
- Pilih **Kategori** = "Elektronik"
- ✅ **Nama Kategori** auto-fill "Elektronik"
- Pilih **Status Produk** = "Tersedia"
- Pilih **Prioritas** = "High"
- Save
- ✅ Data tersimpan

---

#### **Test Grid (LOV Labels)**

1. Klik menu **Customer**
2. Grid tampil:

```
┌──────────┬─────────────┬──────────────┬────────────┬─────────────┐
│ Kode     │ Nama        │ Tipe         │ Provinsi   │ Kota        │
├──────────┼─────────────┼──────────────┼────────────┼─────────────┤
│ CUST-001 │ PT Maju     │ Retail       │ DKI...     │ Jakarta Sel │ ← Labels!
│ CUST-002 │ CV Jaya     │ Grosir       │ Jawa Barat │ Bandung     │
└──────────┴─────────────┴──────────────┴────────────┴─────────────┘
```

**Grid otomatis show:**
- "Retail" (bukan "RETAIL")
- "DKI Jakarta" (dari companion field PROVINCE_NAME)
- "Jakarta Selatan" (dari CITY_NAME)

---

## 📊 Comparison: STATIC vs SQL vs VIEW

| Aspect | STATIC | SQL | VIEW |
|--------|--------|-----|------|
| **Data Source** | Hardcoded JSON | Inline query | Pre-defined view |
| **Dynamic?** | ❌ No | ✅ Yes | ✅ Yes |
| **Parametric?** | ❌ No | ✅ Yes ({{TOKEN}}) | ✅ Yes (if view supports) |
| **Performance** | ⚡ Fastest (no DB) | 🔶 Medium | ⚡ Fast (if materialized) |
| **Reusable** | ❌ No | ❌ No | ✅ Yes |
| **Easy to Change** | ✅ Via Studio | ✅ Via Studio | ⚠️ Need ALTER VIEW |
| **Use Case** | Fixed options | Simple query | Complex query, reuse |
| **Examples** | Status, Type, Level | Master table | Join, calculation |

---

## 💡 Best Practices

### **1. Kapan Pakai STATIC:**

✅ **Good for:**
- Options fixed/rarely change (Status, Type, Priority)
- Small list (<20 items)
- No DB dependency (faster)
- Multi-language (bisa JSON per locale)

❌ **Bad for:**
- Master data (Province, Category)
- Large list (>50 items)
- Frequently changing
- Shared across apps

**Format Recommendations:**
- **Array of Objects:** When you need separate code & label
  ```json
  [{"value":"A","label":"Active"}, ...]
  ```
- **Object/Map:** Simple key-value, easier to write
  ```json
  {"A":"Active", "I":"Inactive"}
  ```
- **Array:** Value = label, most simple
  ```json
  ["Low","Medium","High"]
  ```

---

### **2. Kapan Pakai SQL:**

✅ **Good for:**
- Simple query (1-2 tables)
- Rapid development
- Query logic visible di metadata
- Parametric LOV

❌ **Bad for:**
- Complex query (banyak join)
- Reusable query
- Performance-critical (prefer view)

**SQL Best Practices:**
- Always add `WHERE ACTIVE_FLAG = 1`
- Add `ORDER BY` for sorted dropdown
- Limit rows: `ROWNUM <= 100` (Oracle) atau `LIMIT 100` (Postgres)
- Use `RAWTOHEX()` untuk RAW columns
- Concatenate columns untuk display: `CODE || ' - ' || NAME`

---

### **3. Kapan Pakai VIEW:**

✅ **Good for:**
- Complex query (join 3+ tables)
- Reusable (LOV, report, integration)
- Performance (materialized view)
- DBA-managed (encapsulation)

❌ **Bad for:**
- Simple query (overhead)
- Parametric (view tidak support {{TOKEN}})
- Rapid prototype

**VIEW Best Practices:**
- Prefix `V_` untuk naming
- Suffix `_LOV` untuk LOV-specific view
- Select only needed columns (not `SELECT *`)
- Add `WHERE ACTIVE_FLAG = 1` filter
- Already sorted (`ORDER BY`)

---

### **4. Parametric LOV (Cascade):**

**Pattern:**
```sql
-- Parent LOV (Province)
SELECT RAWTOHEX(PROVINCE_ID), PROVINCE_NAME 
FROM M_PROVINCE 
WHERE ACTIVE_FLAG = 1

-- Child LOV (City, depends on Province)
SELECT RAWTOHEX(CITY_ID), CITY_NAME 
FROM M_CITY 
WHERE PROVINCE_ID = HEXTORAW({{PROVINCE_ID}})  -- ← Parameter!
AND ACTIVE_FLAG = 1
```

**Requirements:**
1. Parent field must have `wire:model.blur` (auto-detected)
2. Child query use `{{PARENT_COLUMN}}`
3. Use `HEXTORAW()` jika parameter adalah RAW(16)
4. Child LOV returns empty if parameter NULL (safe)

**Multi-level Cascade (3+ levels):**
```
Province → City → District
LOV_PROVINCE → LOV_CITY ({{PROVINCE_ID}}) → LOV_DISTRICT ({{CITY_ID}})
```

---

### **5. Companion Fields:**

**Pattern:**
- Main field: Store code/ID (VALUE)
- Companion field: Store label/name (LABEL), readonly

**Setup:**
```
UI_FIELD:
  COLUMN_NAME: CATEGORY_CODE
  LOV_ID: LOV_CATEGORY
  LOV_LABEL_COLUMN: CATEGORY_NAME  ← Points to companion

UI_FIELD (companion):
  COLUMN_NAME: CATEGORY_NAME
  READONLY_FLAG: 1  ← Important!
```

**Why:**
- Grid can show label without LOV lookup
- User can see full name without re-open dropdown
- Report can use label directly (no join)

**Storage:**
- Both columns stored to DB
- Denormalization for performance/UX
- Acceptable for metadata-driven apps

---

### **6. Performance Tips:**

**For Large LOV (1000+ options):**
- Add `ROWNUM <= 500` limit
- Consider autocomplete/search field (future enhancement)
- Use indexed columns in WHERE
- Consider caching (future)

**For Remote LOV (DB Link):**
```sql
-- Add ROWNUM limit!
SELECT ... FROM TABLE@REMOTE WHERE ... AND ROWNUM <= 100
```

**For Frequent Changes:**
- Use SQL/VIEW (dynamic)
- Avoid STATIC (need recompile)

---

## 🎓 Advanced Examples

### **Example 1: LOV with Calculation**

```sql
-- Top products by revenue
LOV_TYPE: SQL
SOURCE_QUERY: 
  SELECT RAWTOHEX(PRODUCT_ID) AS PRODUCT_ID,
         PRODUCT_CODE || ' (Rp ' || TO_CHAR(TOTAL_REVENUE, 'FM999,999,999') || ')' AS DISPLAY
  FROM (
    SELECT P.PRODUCT_ID, P.PRODUCT_CODE, 
           SUM(OI.QTY * OI.PRICE) AS TOTAL_REVENUE
    FROM PRODUCT P
    JOIN ORDER_ITEM OI ON P.PRODUCT_ID = OI.PRODUCT_ID
    GROUP BY P.PRODUCT_ID, P.PRODUCT_CODE
    ORDER BY TOTAL_REVENUE DESC
  )
  WHERE ROWNUM <= 20
VALUE_COLUMN: PRODUCT_ID
LABEL_COLUMN: DISPLAY
```

**Result:** `PROD-001 (Rp 15,000,000)`

---

### **Example 2: LOV with Date Filter**

```sql
-- Recent customers (last 30 days)
SELECT RAWTOHEX(CUSTOMER_ID), CUSTOMER_NAME
FROM CUSTOMER
WHERE CREATED_AT >= SYSTIMESTAMP - INTERVAL '30' DAY
AND ACTIVE_FLAG = 1
ORDER BY CREATED_AT DESC
```

---

### **Example 3: LOV with User Context (Future)**

```sql
-- Customers assigned to current user (sales territory)
SELECT RAWTOHEX(CUSTOMER_ID), CUSTOMER_NAME
FROM CUSTOMER
WHERE SALES_REP_ID = HEXTORAW({{CURRENT_USER_ID}})  -- From context
AND ACTIVE_FLAG = 1
```

({{CURRENT_USER_ID}} butuh enhancement runtime context)

---

## 📝 Troubleshooting

### **Problem: Dropdown kosong**

**Check:**
1. LOV Status = PUBLISHED? (bukan DRAFT)
2. LOV Active Flag = 1?
3. SOURCE_QUERY valid JSON (untuk STATIC)?
4. SQL query return rows?
5. VALUE_COLUMN & LABEL_COLUMN exist di query result?
6. Sudah compile?

**Debug:**
```sql
-- Test query manual
SELECT * FROM V_CATEGORY_LOV;  -- VIEW
SELECT ... FROM M_PROVINCE;    -- SQL

-- Check LOV definition
SELECT OBJECT_CODE, LOV_TYPE, SOURCE_QUERY, STATUS 
FROM DS_LOV 
WHERE OBJECT_CODE = 'LOV_XXX';
```

---

### **Problem: Parametric LOV tidak update**

**Check:**
1. Parameter field ada di form?
2. Parameter field pakai `wire:model.blur`? (auto-detected)
3. Query pakai `{{PARAMETER_NAME}}` correct?
4. Use `HEXTORAW({{...}})` untuk RAW parameter?

**Test:**
- Isi parameter field manual
- Tab keluar → trigger blur
- Lihat dropdown child update

---

### **Problem: Companion field tidak terisi**

**Check:**
1. `LOV_LABEL_COLUMN` set di UI_FIELD?
2. Kolom companion exist di tabel?
3. Field companion ada di form (readonly)?
4. Sudah compile?
5. Browser console error?

---

### **Problem: Grid show code, bukan label**

**Check:**
1. LOV_ID set di UI_FIELD?
2. Sudah compile?
3. Grid re-render?

Grid otomatis resolve LOV labels via `DatasetGrid.php`.

---

## ✅ Summary

**8 LOV Samples Created:**
1. ✅ STATIC - Customer Type (array of objects)
2. ✅ STATIC - Product Status (object/map)
3. ✅ STATIC - Priority (simple array)
4. ✅ VIEW - Category
5. ✅ SQL - Category (inline)
6. ✅ SQL - Province (master)
7. ✅ SQL - City (parametric, cascade)
8. ✅ SQL - Top Customers (limited)

**Features Demonstrated:**
- ✅ All 3 LOV types (STATIC, SQL, VIEW)
- ✅ All 3 STATIC formats (array objects, object map, simple array)
- ✅ Parametric LOV (cascade)
- ✅ Companion fields (label sync)
- ✅ RAW(16) handling (RAWTOHEX/HEXTORAW)
- ✅ Concatenated display
- ✅ Row limiting
- ✅ Sorting

**Next Steps:**
1. Apply SQL script
2. Compile application
3. Test cascade Province → City
4. Test different STATIC formats
5. Try membuat LOV sendiri!

