# Bugfix: Skema Kolom Aktual untuk Phase 3 Designer

**Date:** 2026-07-09  
**Status:** ✅ Fixed & Verified

---

## 🐛 Masalah

Komponen Phase 3 (ApplicationDashboard, FormBuilder, LovDesigner) dibuat dengan asumsi nama kolom yang salah, menyebabkan serangkaian error:

1. `ORA-00904: "APPLICATION_ID": invalid identifier` (tebakan APP_ID salah)
2. `ORA-00904: "PKG"."VERSION": invalid identifier`
3. `Undefined array key "OBJECT_NAME"` (oci8 mengembalikan key lowercase)

**Akar masalah:** menebak skema, bukan introspeksi database.

---

## 🔍 Skema Aktual (hasil introspeksi `USER_TAB_COLUMNS`)

### APP_APPLICATION
`OBJECT_ID, OBJECT_CODE, OBJECT_NAME, DESCRIPTION, VERSION_NO, STATUS, CREATED_AT, UPDATED_AT, CREATED_BY, UPDATED_BY`

### APP_MODULE
`OBJECT_ID, APPLICATION_ID, OBJECT_CODE, OBJECT_NAME, DISPLAY_ORDER, DESCRIPTION, VERSION_NO, STATUS, ...`
- FK ke aplikasi = **APPLICATION_ID** (bukan APP_ID)

### APP_MENU
`OBJECT_ID, MODULE_ID, PARENT_MENU_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME, ICON, DISPLAY_ORDER, VISIBLE_FLAG, ...`
- Tidak punya APPLICATION_ID; terhubung ke app via **MODULE_ID → APP_MODULE**

### UI_PAGE
`OBJECT_ID, APPLICATION_ID, DATASET_ID, OBJECT_CODE, OBJECT_NAME, TITLE, PAGE_TYPE, LAYOUT_TYPE, DESCRIPTION, ...`

### UI_FIELD
`OBJECT_ID, PAGE_ID, LOV_ID, OBJECT_CODE, OBJECT_NAME, LABEL, COLUMN_NAME, FIELD_TYPE, DATA_TYPE, DISPLAY_ORDER, REQUIRED_FLAG, VISIBLE_FLAG, READONLY_FLAG, DEFAULT_VALUE, VERSION_NO, STATUS, ..., LOV_LABEL_COLUMN`
- Nama field = **COLUMN_NAME** (bukan FIELD_NAME)
- **TIDAK ADA**: WIDGET_TYPE, COLUMN_WIDTH, MAX_LENGTH, MIN_VALUE, MAX_VALUE, PLACEHOLDER, HELP_TEXT, OBJECT_VERSION, ACTIVE_FLAG
- Status via **STATUS**, versi via **VERSION_NO**

### DS_LOV
`OBJECT_ID, OBJECT_CODE, OBJECT_NAME, LOV_TYPE, VALUE_COLUMN, LABEL_COLUMN, SOURCE_QUERY, DESCRIPTION, VERSION_NO, STATUS, ...`
- Tipe via **LOV_TYPE** (bukan SOURCE_TYPE); nilai valid: **STATIC, SQL, VIEW**
- **TIDAK ADA**: APPLICATION_ID/APP_ID (LOV bersifat global), ORDER_COLUMN, FILTER_COLUMN, ACTIVE_FLAG

### DS_DATASET
`OBJECT_ID, OBJECT_CODE, OBJECT_NAME, SOURCE_TYPE, SOURCE_OBJECT, SOURCE_QUERY, PRIMARY_KEY_COLUMN, SOFT_DELETE_FLAG, ...`
- Nama tabel sumber = **SOURCE_OBJECT** (SOURCE_QUERY untuk tipe SQL)

### RT_PACKAGE
`OBJECT_ID, APPLICATION_ID, PACKAGE_VERSION, CHECKSUM, COMPILER_VERSION, PAYLOAD, ACTIVE_FLAG, COMPILED_AT, ACTIVATED_AT, CREATED_BY`
- Versi = **PACKAGE_VERSION** (bukan VERSION)

---

## ✅ Perbaikan yang Diterapkan

### 1. Normalisasi hasil oci8 (`NormalizesRows` trait)
Driver oci8 mengembalikan **key kolom lowercase** dan **CLOB sebagai resource**. Dibuat trait `App\Livewire\Studio\Designer\NormalizesRows`:
- Uppercase semua key
- Baca CLOB (`stream_get_contents`)

Diterapkan di ketiga komponen untuk setiap hasil `DB::select`/`selectOne`.

### 2. ApplicationDashboard
- `RT_PACKAGE.PACKAGE_VERSION` (bukan VERSION)
- MODULE_COUNT via `APP_MODULE.APPLICATION_ID`
- MENU_COUNT via `APP_MENU.MODULE_ID` subquery
- LOV_COUNT via field usage (DS_LOV tidak punya FK app)
- `Artisan::call('odaf:compile', ['application' => ..., '--activate' => true])` (argumen `application`)

### 3. FormBuilder
- UI_FIELD: hanya kolom yang ada (COLUMN_NAME, LABEL, FIELD_TYPE, DATA_TYPE, DISPLAY_ORDER, REQUIRED/READONLY/VISIBLE_FLAG, DEFAULT_VALUE, LOV_ID)
- Hapus editor untuk kolom tidak ada (max_length, placeholder, help_text, column_width, widget_type)
- INSERT pakai STATUS='PUBLISHED', DISPLAY_ORDER kelipatan 10
- DELETE juga hapus VAL_RULE terkait (FK)
- LOV dropdown pakai `LOV_TYPE`

### 4. LovDesigner
- `LOV_TYPE` (STATIC/SQL/VIEW), bukan SOURCE_TYPE/TABLE
- Hapus field: APPLICATION_ID, ORDER_COLUMN, FILTER_COLUMN, ACTIVE_FLAG
- Hapus dropdown Application dari UI (LOV global)
- INSERT/UPDATE pakai kolom aktual + STATUS='PUBLISHED'
- OBJECT_ID di-generate via `bin2hex(random_bytes(16))`

---

## ✅ Verifikasi

Script introspeksi + 4 test dijalankan di container:

| Test | Hasil |
|------|-------|
| Dashboard query | ✅ ODAF_DEMO: 1 module, 3 menu, 2 page, 18 field, 6 LOV, v1.6e20c18b46b6 |
| FormBuilder loadPage + fields | ✅ Customer Form, 9 field |
| LovDesigner list LOV | ✅ 6 LOV tersedia |
| INSERT DS_LOV (rollback) | ✅ Sukses |

---

## 📝 Pelajaran

**Selalu introspeksi skema sebelum menulis query.** Menebak nama kolom menyebabkan 3 ronde error. Introspeksi `USER_TAB_COLUMNS` sekali di awal akan menghemat waktu.

Pola akses hasil query oci8 di project ini **wajib** melewati normalisasi (uppercase key + baca CLOB), seperti yang sudah dilakukan `OracleMetadataRepository::normalize()`.
