# Quick Start: Menambahkan LOV ke Field

**Target:** Menambahkan dropdown ke field dalam 5 langkah mudah

---

## 🎯 Skenario: Tambah "Status Pelanggan" Dropdown

Kita akan tambah field baru `CUSTOMER_STATUS` dengan dropdown (ACTIVE/INACTIVE/SUSPENDED).

---

## 📝 Langkah-langkah

### **Step 1: Login ke Studio** 

```
URL: http://localhost:8080/studio
Login: admin / password
```

---

### **Step 2: Buat LOV Definition**

1. Di sidebar Studio, klik **DS_LOV** (di group "Metadata")
2. Klik tombol **+ Baru** (kanan atas)
3. Isi form:

```
┌─────────────────────────────────────────────────┐
│ Object Code:     LOV_CUSTOMER_STATUS            │
│ Object Name:     Customer Status List           │
│ LOV Type:        STATIC                         │  ← Pilih dari dropdown
│ Source Query:    [lihat di bawah]               │
│ Value Column:    (kosongkan untuk STATIC)       │
│ Label Column:    (kosongkan untuk STATIC)       │
│ Status:          PUBLISHED                      │  ← Pilih dari dropdown
│ Active Flag:     Ya                             │  ← Pilih Ya
└─────────────────────────────────────────────────┘
```

**Source Query (copy-paste):**
```json
[
  {"value":"ACTIVE","label":"Aktif"},
  {"value":"INACTIVE","label":"Tidak Aktif"},
  {"value":"SUSPENDED","label":"Ditangguhkan"}
]
```

4. Klik **Simpan**
5. ✅ LOV berhasil dibuat!

---

### **Step 3: Tambah Kolom di Database** (jika belum ada)

Jika kolom `CUSTOMER_STATUS` belum ada di tabel, tambahkan dulu:

**Via SQL Developer atau Studio:**
```sql
ALTER TABLE CUSTOMER ADD CUSTOMER_STATUS VARCHAR2(20);
```

**Atau skip step ini** jika kolom sudah ada.

---

### **Step 4: Tambah UI Field**

1. Di sidebar Studio, klik **UI_FIELD** (di group "Metadata")
2. Klik tombol **+ Baru**
3. Isi form:

```
┌─────────────────────────────────────────────────┐
│ Page Id:          (pilih "Customer Form")       │  ← Dropdown
│ Field Code:       FIELD_CUST_STATUS             │
│ Column Name:      CUSTOMER_STATUS               │
│ Label:            Status Pelanggan              │
│ Field Type:       TEXT                          │  ← Akan jadi dropdown karena ada LOV
│ Data Type:        STRING                        │  ← Dropdown
│ LOV Id:           (pilih "Customer Status...")  │  ← 🎯 INI KUNCI!
│ Required Flag:    Tidak                         │
│ Readonly Flag:    Tidak                         │
│ Display Order:    60                            │
│ Status:           PUBLISHED                     │
│ Active Flag:      Ya                            │
└─────────────────────────────────────────────────┘
```

4. Klik **Simpan**
5. ✅ Lihat flash message: "... klik ⚡ Kompilasi & Aktifkan ..."

---

### **Step 5: Compile & Test**

1. **Klik tombol ⚡ "Kompilasi & Aktifkan"** di kanan atas header Studio
2. Tunggu 2-3 detik
3. ✅ Success message: "Kompilasi berhasil..."
4. **Buka aplikasi** (tab baru atau existing):
   ```
   http://localhost:8080/app/ODAF_DEMO
   ```
5. Klik menu **Customer**
6. Klik **+ Baru** atau **Ubah** pada customer existing
7. ✅ **Field "Status Pelanggan" muncul sebagai DROPDOWN** dengan 3 options!

---

## 🎉 Hasil

### **Form Customer (Baru/Edit):**

```
┌──────────────────────────────────────────┐
│ Kode Pelanggan     [____________]        │
│ Nama Pelanggan     [____________]        │
│ Email              [____________]        │
│ Batas Kredit       [____________]        │
│                                          │
│ Status Pelanggan   [ Aktif ▼ ]          │ ← NEW! Dropdown
│                     ├ Aktif             │
│                     ├ Tidak Aktif       │
│                     └ Ditangguhkan      │
│                                          │
│ [Simpan] [Batal]                         │
└──────────────────────────────────────────┘
```

### **Grid Customer (List):**

```
┌─────────────┬──────────────────┬─────────────────┬───────────────┐
│ Kode        │ Nama             │ Email           │ Status        │
├─────────────┼──────────────────┼─────────────────┼───────────────┤
│ CUST-001    │ PT Maju Jaya     │ info@maju.com   │ Aktif         │ ← Label!
│ CUST-002    │ CV Sejahtera     │ cs@sejahtera.id │ Ditangguhkan  │ ← Label!
│ CUST-003    │ UD Sentosa       │ admin@sentosa.  │ Tidak Aktif   │ ← Label!
└─────────────┴──────────────────┴─────────────────┴───────────────┘
```

**Grid otomatis show LABEL ("Aktif"), bukan VALUE ("ACTIVE")!** ✅

---

## 💡 Variasi LOV Lain

### **LOV dari Database Query (SQL Type)**

Misalnya dropdown "Province" dari tabel master:

```sql
-- Step 2: Buat LOV dengan query
LOV Type:     SQL
Source Query: SELECT PROVINCE_ID, PROVINCE_NAME FROM M_PROVINCE WHERE ACTIVE_FLAG = 1
Value Column: PROVINCE_ID
Label Column: PROVINCE_NAME
```

### **LOV Parametrik (Depends on Another Field)**

Misalnya "City" tergantung "Province":

```sql
-- Step 2: Buat LOV dengan parameter
LOV Type:     SQL
Source Query: SELECT CITY_ID, CITY_NAME 
              FROM M_CITY 
              WHERE PROVINCE_ID = {{PROVINCE_ID}}
              AND ACTIVE_FLAG = 1
Value Column: CITY_ID
Label Column: CITY_NAME
```

**Behavior:**
- User pilih Province → dropdown City otomatis update dengan cities di province tersebut
- Reaktif tanpa refresh halaman!

### **LOV dengan Companion Field (Show Label)**

Misalnya simpan code tapi tampilkan juga description:

```sql
-- Step 4: Saat tambah UI_FIELD
LOV Id:             LOV_PRODUCT_CATEGORY
LOV Label Column:   PRODUCT_CATEGORY_DESC  ← Kolom companion (readonly)
```

**Result:**
- User pilih "Electronics" dari dropdown PRODUCT_CATEGORY
- Field PRODUCT_CATEGORY_DESC otomatis terisi "Peralatan Elektronik"
- Kedua kolom tersimpan ke database

---

## ❓ FAQ

### **Q: Field baru tidak muncul setelah save?**

A: **Harus compile dulu!** 
1. Klik ⚡ "Kompilasi & Aktifkan" di Studio
2. Refresh browser (Ctrl+F5)
3. Field akan muncul

Runtime tidak baca metadata langsung, hanya baca compiled package.

---

### **Q: Dropdown kosong / tidak ada options?**

A: Cek:
1. **LOV Status = PUBLISHED?** (bukan DRAFT)
2. **LOV Active Flag = Ya?**
3. **Source Query benar?** 
   - STATIC: JSON array valid
   - SQL: Query return rows
   - VIEW: Tabel/view exist
4. **Sudah compile?**

---

### **Q: Dropdown ada tapi tidak tersimpan?**

A: Cek:
1. **Kolom di database exist?** (`ALTER TABLE ... ADD ...`)
2. **Field readonly?** (readonly field tidak disimpan)
3. **Column Name match?** (case-sensitive: `CUSTOMER_STATUS`)

---

### **Q: Bisa LOV dari tabel lain via database link?**

A: **Ya!** Seperti Customer Group dari IFS:

```sql
LOV Type:     SQL
Source Query: SELECT CF$_CUSTGROUP, CF$_CUSTGROUPDESC
              FROM IFSAPP.CTM_CUST_GROUP_CLV@NEXUS
              WHERE CF$_CUSTID = {{CUSTOMER_CODE}}
```

Dropdown akan query remote database via @NEXUS.

---

### **Q: Performance LOV query lambat?**

A: Options untuk optimize:
1. **Add WHERE clause** untuk limit rows (max 500 recommended)
2. **Add index** di tabel sumber
3. **Cache LOV static** (untuk options yang jarang berubah)
4. **Pakai VIEW** instead of complex SQL

---

## 🎓 Next Steps

Setelah mahir LOV dasar, coba:

1. **LOV Cascade:** Province → City → District (3-level)
2. **LOV Conditional:** Options berbeda per user role
3. **LOV dengan Search:** Untuk LOV banyak options (1000+)
4. **LOV Multi-select:** Checkbox multiple selection
5. **LOV Tree:** Hierarchical dropdown (category/subcategory)

(Feature cascade/search/multi/tree belum built-in, perlu enhancement F2/F3)

---

## 📚 Reference

- **Full Documentation:** `LOV-FEATURE-COVERAGE.md`
- **Bug Fix History:** `BUGFIX-LOV-COMPANION.md`
- **Architecture:** `DEVELOPMENT-DONE-Phase1.md`
- **Volume 2 Database:** `docs/volume-02-database/14-Dataset-Metadata.md`

