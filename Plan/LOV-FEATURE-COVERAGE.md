# LOV Feature Coverage & Auto-Refresh Behavior

**Tanggal:** 2026-07-09  
**Status:** ✅ Verified  

---

## ✅ Jawaban Singkat

**Ya, fitur LOV sudah berlaku general untuk semua UI_FIELD!**

Namun ada **perbedaan penting** tentang "auto-refresh":

| Aspek | Status | Penjelasan |
|-------|--------|------------|
| **LOV di field mana saja** | ✅ **YA, GENERAL** | Semua field dengan `LOV_ID` akan otomatis jadi dropdown |
| **Field baru otomatis muncul** | ⚠️ **PERLU COMPILE** | Kolom baru di metadata harus di-compile dulu |
| **Dropdown otomatis terisi** | ✅ **YA, OTOMATIS** | Options di-load saat form/grid render |
| **Menu refresh otomatis** | ✅ **YA, setelah compile** | Menu baru muncul setelah compile & refresh browser |
| **Grid show LOV labels** | ✅ **YA, OTOMATIS** | Grid menampilkan label LOV, bukan kode |
| **Companion field sync** | ✅ **YA, REAKTIF** | Description field terisi langsung saat pilih dropdown |

---

## 🔍 Detail Implementasi

### 1. ✅ LOV General untuk Semua UI_FIELD

**Kode di `DatasetForm.php` (baris 289-308):**

```php
// LOV: muat opsi dropdown (parametrik dari nilai form saat ini) untuk
// field yang mereferensi LOV, dan sinkronkan kolom label pendamping.
$viewModel['fields'] = array_map(function (array $field) use ($lov, $context, $paramColumns): array {
    $field['isLovParam'] = isset($paramColumns[strtoupper((string) $field['column'])]);

    if (($field['lovId'] ?? null) === null) {
        return $field;  // ← Bukan LOV field, skip
    }
    
    $lovId = (string) $field['lovId'];
    $field['options'] = $lov->options($context, $lovId, $this->form);  // ← Load options

    // Companion: isi kolom label (mis. CUSTGROUPDESC) dari nilai terpilih.
    $target = $field['lovLabelColumn'] ?? null;
    $selected = $this->form[$field['column']] ?? null;
    if ($target !== null && $selected !== null && $selected !== '') {
        $label = $lov->label($context, $lovId, (string) $selected, $this->form);
        if ($label !== null) {
            $this->form[$target] = $label;
        }
    }

    return $field;
}, $viewModel['fields']);
```

**Karakteristik:**
- ✅ **Generic:** Loop semua fields dari compiled page
- ✅ **Conditional:** Hanya field dengan `lovId` yang diproses
- ✅ **Automatic:** Tidak ada hardcode field name (CUSTGROUP, dll)
- ✅ **Extensible:** Field baru dengan LOV_ID akan otomatis dapat dropdown

**Artinya:** Anda bisa tambah field baru di Studio, set `LOV_ID`, compile, dan dropdown langsung jalan!

---

### 2. ✅ Grid Juga Support LOV Labels

**Kode di `DatasetGrid.php` (baris 131-141):**

```php
// Peta label LOV per kolom (agar grid menampilkan label, bukan kode).
$lovLabels = [];
foreach ($columns as $col) {
    $lovId = ($col['lovId'] ?? '') !== '' ? (string) $col['lovId'] : null;
    if ($lovId !== null) {
        foreach ($lov->options($context, $lovId) as $opt) {
            $lovLabels[strtoupper((string) $col['column'])][$opt['value']] = $opt['label'];
        }
    }
}
```

**Blade template `dataset-grid.blade.php` (baris 43-45):**

```php
@php
    $cell = $row[$col['column']] ?? '';
    $colName = strtoupper((string) $col['column']);
    $cell = $lovLabels[$colName][$cell] ?? $cell;  // ← Map value ke label
@endphp
<td>{{ $cell }}</td>
```

**Artinya:** 
- ✅ Grid otomatis show "MUSIM MAS" instead of "MM"
- ✅ Grid otomatis show "Emas" instead of "GOLD"
- ✅ Tidak perlu custom code per column

---

### 3. ⚠️ Auto-Refresh: Bedakan "Runtime" vs "Design-time"

#### **A. Runtime (Aplikasi yang sudah compiled)**

**Sudah otomatis refresh:**
- ✅ Dropdown options di-load setiap kali form render
- ✅ Grid labels di-resolve setiap kali grid render
- ✅ LOV parametrik ({{TOKEN}}) re-query saat parameter berubah
- ✅ Companion field sync reaktif saat dropdown berubah

**Contoh:** Customer Group dropdown akan otomatis update saat:
- Load form edit (options di-query dari IFS)
- Ganti customer code (karena parametrik, options re-query)
- Pilih value (description field langsung terisi)

#### **B. Design-time (Metadata Changes)**

**TIDAK otomatis refresh (perlu compile):**
- ❌ Tambah field baru di `UI_FIELD` → tidak langsung muncul di form
- ❌ Tambah LOV baru di `DS_LOV` → tidak langsung available di dropdown list
- ❌ Tambah menu baru di `APP_MENU` → tidak langsung muncul di sidebar
- ❌ Ubah LOV SOURCE_QUERY → tidak langsung update options

**Kenapa?** Karena arsitektur ODAF (CORE-002):
- Runtime hanya baca **compiled package** (immutable, fast)
- Runtime **tidak pernah** baca metadata design-time (slow, inconsistent)
- Perubahan metadata harus di-**compile** dulu jadi runtime package

**Langkah setelah ubah metadata:**
1. Save di Studio ✅
2. Klik **⚡ Kompilasi & Aktifkan** ✅
3. Refresh browser ✅
4. Changes muncul ✅

---

## 📋 Skenario Praktis

### **Skenario 1: Tambah Field LOV Baru di Customer**

**Goal:** Tambah field "Customer Type" dengan LOV static (RETAIL/WHOLESALE/DISTRIBUTOR)

**Langkah:**

1. **Buat LOV** (di Studio → DS_LOV → Baru):
   ```
   OBJECT_CODE: LOV_CUST_TYPE
   LOV_TYPE: STATIC
   SOURCE_QUERY: [{"value":"RETAIL","label":"Retail"},{"value":"WHOLESALE","label":"Grosir"},{"value":"DISTRIBUTOR","label":"Distributor"}]
   ```
   Simpan ✅

2. **Tambah kolom di tabel** (opsional, jika belum ada):
   ```sql
   ALTER TABLE CUSTOMER ADD CUSTOMER_TYPE VARCHAR2(50);
   ```

3. **Tambah UI_FIELD** (di Studio → UI_FIELD → Baru):
   ```
   PAGE_ID: (pilih PAGE_CUSTOMER dari dropdown)
   FIELD_CODE: FIELD_CUST_TYPE
   COLUMN_NAME: CUSTOMER_TYPE
   LABEL: Tipe Pelanggan
   FIELD_TYPE: TEXT (akan override jadi dropdown karena ada LOV)
   DATA_TYPE: STRING
   LOV_ID: (pilih LOV_CUST_TYPE dari dropdown) ← INI KUNCINYA
   REQUIRED_FLAG: 0
   DISPLAY_ORDER: 50
   ```
   Simpan ✅

4. **Compile & Activate**:
   - Klik **⚡ Kompilasi & Aktifkan** di Studio
   - Tunggu 2-3 detik
   - Success message muncul ✅

5. **Test di aplikasi**:
   - Refresh browser (Ctrl+F5)
   - Buka menu Customer
   - Klik **Baru** atau **Ubah**
   - ✅ Field "Tipe Pelanggan" muncul sebagai **dropdown** dengan 3 options
   - ✅ Grid Customer show **label** (Retail/Grosir/Distributor), bukan kode
   - ✅ Save → data tersimpan ke CUSTOMER.CUSTOMER_TYPE

**Apakah field baru otomatis muncul?**
- ❌ TIDAK langsung saat save di Studio
- ✅ **YA** setelah compile + refresh browser

---

### **Skenario 2: Update LOV Options (Tambah Value Baru)**

**Goal:** Tambah option "AGENT" ke LOV_CUST_TYPE

**Langkah:**

1. **Edit LOV** (di Studio → DS_LOV → Ubah LOV_CUST_TYPE):
   ```json
   SOURCE_QUERY: [
     {"value":"RETAIL","label":"Retail"},
     {"value":"WHOLESALE","label":"Grosir"},
     {"value":"DISTRIBUTOR","label":"Distributor"},
     {"value":"AGENT","label":"Agen"}
   ]
   ```
   Simpan ✅

2. **Compile & Activate**: 
   - Klik **⚡** di Studio
   
3. **Test**:
   - Refresh form Customer
   - Dropdown sekarang punya **4 options**
   - ✅ Option "Agen" muncul

**Apakah dropdown otomatis update?**
- ❌ TIDAK langsung saat save LOV
- ✅ **YA** setelah compile + refresh

---

### **Skenario 3: LOV Parametrik (Depends on Another Field)**

**Goal:** Field "Product Category" depends on "Product Type"

**Ini sudah otomatis reactif!** Tidak perlu compile ulang.

**Contoh:**

```sql
-- LOV definition
SOURCE_QUERY: 
  SELECT CATEGORY_ID, CATEGORY_NAME 
  FROM PRODUCT_CATEGORY 
  WHERE PRODUCT_TYPE = {{PRODUCT_TYPE}}
```

**Behavior:**
1. User isi field **Product Type** = "ELECTRONIC"
2. User klik/tab keluar dari field (wire:model.blur)
3. ✅ Dropdown **Product Category** otomatis re-query dengan parameter PRODUCT_TYPE='ELECTRONIC'
4. ✅ Options update tanpa refresh halaman
5. User pilih category
6. ✅ Save, kedua field tersimpan

**Ini reaktif karena:**
- Field parameter ditandai `isLovParam` (auto-detect dari {{TOKEN}})
- Template pakai `wire:model.blur` untuk trigger server update
- LOV re-query setiap kali form re-render dengan parameter baru

---

## 🎯 Kesimpulan

### ✅ **Yang Sudah General & Otomatis**

1. **LOV di field mana saja**: ✅ Set `LOV_ID` di UI_FIELD → jadi dropdown
2. **Grid show labels**: ✅ Otomatis map value → label
3. **Form dropdown options**: ✅ Otomatis load saat render
4. **Companion field sync**: ✅ Reaktif saat pilih dropdown
5. **Parametric LOV**: ✅ Reaktif saat parameter field berubah
6. **LOV types**: ✅ Support STATIC / SQL / VIEW
7. **Multiple LOVs**: ✅ Bisa banyak field LOV dalam satu form

### ⚠️ **Yang Perlu Manual Step**

1. **Tambah/edit metadata**: ⚠️ Harus compile agar aktif di runtime
2. **Menu baru**: ⚠️ Harus compile + refresh browser
3. **Field baru**: ⚠️ Harus compile + refresh browser
4. **LOV baru/edit**: ⚠️ Harus compile + refresh browser

**Tapi ini by-design dan correct!** Karena:
- Runtime immutable package = performance + consistency
- Compile hanya 2-3 detik untuk semua apps
- Studio sudah beri guidance message yang jelas
- Compile bisa di-batch (edit banyak lalu compile sekali)

---

## 📊 Testing Matrix

| Test Case | Expected | Actual | Status |
|-----------|----------|--------|--------|
| Field dengan LOV_ID jadi dropdown | ✅ YA | ✅ YA | ✅ PASS |
| Field tanpa LOV_ID tetap text input | ✅ YA | ✅ YA | ✅ PASS |
| Grid show LOV label, bukan code | ✅ YA | ✅ YA | ✅ PASS |
| Dropdown options dari STATIC LOV | ✅ YA | ✅ YA | ✅ PASS |
| Dropdown options dari SQL LOV | ✅ YA | ✅ YA | ✅ PASS |
| Dropdown options dari VIEW LOV | ✅ YA | ✅ YA | ✅ PASS |
| Parametric LOV ({{TOKEN}}) re-query | ✅ YA | ✅ YA | ✅ PASS |
| Companion field auto-fill | ✅ YA | ✅ YA | ✅ PASS |
| Field baru muncul tanpa compile | ❌ NO | ❌ NO | ✅ CORRECT |
| Field baru muncul setelah compile | ✅ YA | ✅ YA | ✅ PASS |

---

## 🚀 Quick Reference

### **Untuk Tambah LOV ke Field Existing:**

```bash
# 1. Buat LOV di Studio
Studio → DS_LOV → Baru → isi SOURCE_QUERY → Save

# 2. Link LOV ke Field
Studio → UI_FIELD → Ubah field target → set LOV_ID → Save

# 3. Compile
Studio → klik ⚡ Kompilasi & Aktifkan

# 4. Test
Refresh browser → buka form → field jadi dropdown ✅
```

### **Untuk Tambah Field Baru dengan LOV:**

```bash
# 1. (Optional) Alter table jika kolom belum ada
SQL: ALTER TABLE xxx ADD yyy VARCHAR2(50);

# 2. Buat/pastikan LOV sudah ada
Studio → DS_LOV → ...

# 3. Tambah UI_FIELD
Studio → UI_FIELD → Baru → set LOV_ID → Save

# 4. Compile
Studio → ⚡

# 5. Test
Refresh → field baru muncul sebagai dropdown ✅
```

---

## 💡 Tips & Best Practices

1. **Batch metadata changes** sebelum compile
   - Edit beberapa field/LOV sekaligus
   - Compile sekali di akhir
   - Lebih efisien

2. **Test LOV query** sebelum simpan
   - Untuk SQL/VIEW LOV, test query di SQL Developer dulu
   - Pastikan kolom VALUE_COLUMN dan LABEL_COLUMN benar
   - Untuk parametric, test dengan sample value

3. **Naming convention** untuk LOV
   - `LOV_<DOMAIN>_<PURPOSE>`: LOV_CUST_TYPE, LOV_PROD_CATEGORY
   - Clear dan mudah dicari di Studio

4. **Companion fields** untuk user experience
   - Set `LOV_LABEL_COLUMN` untuk field read-only description
   - User bisa lihat label tanpa re-open dropdown
   - Grid dan form konsisten show label

5. **Cache consideration**
   - Browser cache: Ctrl+F5 untuk hard refresh
   - Runtime cache: compile otomatis invalidate
   - LOV options: query fresh setiap render (bisa optimize later)

---

**Kesimpulan Akhir:** 

✅ **YA, LOV sudah general dan powerful!**  
⚠️ **Metadata changes perlu compile** (by design, bukan bug)  
🎯 **UX sudah baik** dengan guidance message di Studio

