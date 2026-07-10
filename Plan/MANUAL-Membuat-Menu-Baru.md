# Manual ODAF Studio — Membuat Menu Baru (Step by Step dari 0)

Dokumen ini memandu Anda dari nol membuat menu/tabel baru menggunakan **ODAF
Studio**, mencakup: Data Manager, Visual Designer (Applications), tombol
**Design** & **New Application**, pembuatan **Menu/Tabel Baru** dan
**Header-Detail**, serta **Visual Designer LOV**. Semua langkah ditulis
sesuai UI yang berjalan.

- **Runtime**: `http://localhost:8080`
- **Login**: `admin` / `password`
- **Environment**: Docker (Oracle 23ai + PHP 8.4 / Laravel 11)

---

## 0. Konsep Dasar (wajib dipahami dulu)

ODAF adalah platform **metadata-driven**: tampilan (menu, form, grid) tidak
di-hardcode, melainkan **digenerate dari metadata** yang dikompilasi menjadi
"Runtime Package". Ada 2 lapisan:

| Lapisan | Isi | Contoh |
|---------|-----|--------|
| **Metadata** (cetak biru) | Definisi aplikasi, menu, halaman, field, LOV | `APP_APPLICATION`, `APP_MODULE`, `APP_MENU`, `UI_PAGE`, `UI_FIELD`, `DS_DATASET`, `DS_LOV` |
| **Tabel fisik** (data) | Tempat data bisnis disimpan | `T_SUPPLIER`, `CUSTOMER`, dll |

Hierarki metadata sebuah aplikasi:

```
APP_APPLICATION (aplikasi)
 └─ APP_MODULE (modul, mis. TRANSAKSI)
     └─ APP_MENU (item menu di sidebar) ──► UI_PAGE (halaman)
                                             ├─ DS_DATASET (sumber data → tabel fisik)
                                             └─ UI_FIELD[] (kolom/field di form & grid)
```

**Aturan emas:** Setiap perubahan metadata **baru muncul di runtime setelah
di-Compile & Activate**. Jika sudah mengubah sesuatu tapi belum terlihat di
`/app/...`, kemungkinan besar Anda lupa meng-compile.

**Konvensi nama tabel:** tabel transaksi baru selalu diberi awalan **`T_`**
(mis. ketik "Supplier" → tabel `T_SUPPLIER`).

---

## 1. Peta ODAF Studio

Studio punya **dua area** yang saling melengkapi. Pindah antar-keduanya lewat
menu sidebar kiri.

### A. Visual Designer — `http://localhost:8080/studio/designer`
Cara **utama & disarankan**. Membuat tabel + menu + form secara visual, dan
otomatis melakukan pembuatan tabel fisik (`CREATE TABLE`), penambahan kolom
(`ALTER TABLE`), serta kompilasi.

### B. Data Manager — `http://localhost:8080/studio`
Editor **CRUD generik** untuk SEMUA tabel (metadata maupun data). Ini "mode
lanjutan". Penting: mengedit tabel di sini **hanya menyimpan data/metadata,
TIDAK menjalankan CREATE/ALTER TABLE**. Dipakai untuk: membuat baris
`APP_APPLICATION` baru, mengedit metadata tingkat lanjut, atau melihat data
mentah.

### Sidebar Studio (kiri)
- **DESIGNER**: Applications · Workflows (Soon) · LOVs
- **DATA MANAGER**: All Tables
- Sidebar bisa **di-resize** (tarik tepi kanan) & **di-hide** (tombol ‹‹).
- Kanan atas: link **Runtime**, nama user, tombol **Keluar**.

---

## 2. Langkah 0 — Login

1. Buka `http://localhost:8080` → otomatis diarahkan ke halaman **Login**.
2. Masukkan `admin` / `password` → **Masuk**.
3. Anda tiba di runtime aplikasi demo (`ODAF_DEMO`). Klik **Studio** (kanan
   atas) untuk masuk ke area authoring.

---

## 3. Studio Applications (Dashboard) — `/studio/designer`

Halaman ini menampilkan semua **aplikasi** sebagai kartu. Tiap kartu berisi:

- **Nama** & **kode** aplikasi (mis. ODAF Demo Application / `ODAF_DEMO`).
- **Badge status**: PUBLISHED / DRAFT.
- **Statistik**: jumlah Forms, Fields, Menus, modules, LOVs, dan **versi aktif**
  (`v...`) package terkompilasi.
- **Tiga tombol aksi** di bawah kartu:
  1. **Design** (biru, ikon pensil) → masuk ke **Application Overview** (daftar
     form + tombol buat menu). **Ini pintu utama untuk membuat menu.**
  2. **⚡ Compile** (ikon petir) → meng-compile & mengaktifkan aplikasi. Muncul
     dialog konfirmasi. Wajib dijalankan agar perubahan tampil di runtime.
  3. **⚙ Settings** (ikon gir) → membuka edit baris `APP_APPLICATION` di Data
     Manager (untuk ganti nama/deskripsi/status aplikasi).

Di kanan atas ada tombol **New Application**.

> **Catatan penting:** tombol **New Application** saat ini **belum berfungsi**
> (menampilkan "App Wizard - Coming Soon"). Untuk membuat aplikasi baru dari
> nol, gunakan Data Manager (lihat Bagian 4). Jika Anda hanya ingin menambah
> **menu baru pada aplikasi yang sudah ada** (mis. ODAF_DEMO), **lewati Bagian
> 4** dan langsung ke Bagian 5 (klik **Design**).

---

## 4. (Opsional) Membuat APLIKASI Baru dari Nol

Lakukan bagian ini **hanya** jika Anda perlu aplikasi baru (bukan sekadar menu
baru). Karena wizard visual belum ada, buat lewat Data Manager:

1. Sidebar → **All Tables** (atau buka `/studio`).
2. Cari grup **APP** → klik tabel **`APP_APPLICATION`**.
3. Klik **+ Baru** (kanan atas).
4. Isi field:
   - **OBJECT_CODE**: kode unik, huruf besar, tanpa spasi — mis. `MYAPP`.
   - **OBJECT_NAME**: nama tampilan — mis. `Aplikasi Saya`.
   - **STATUS**: pilih `PUBLISHED`.
   - (Kolom audit/ID akan terisi otomatis.)
5. Klik **Simpan**.
6. Kembali ke **Applications** (`/studio/designer`) — kartu aplikasi baru
   akan muncul (statistik masih 0).
7. Lanjut Bagian 5 dengan menekan **Design** pada kartu aplikasi baru itu.

> Setelah aplikasi punya minimal satu menu & sudah di-compile, ia dapat dibuka
> di runtime lewat `http://localhost:8080/app/MYAPP`.

---

## 5. Masuk ke Aplikasi: tombol Design → Application Overview

1. Di **`/studio/designer`**, pada kartu aplikasi (mis. **ODAF Demo
   Application**), klik **Design**.
2. Anda masuk ke **Application Overview** (`/studio/designer/app/{id}`) yang
   menampilkan:
   - Judul aplikasi + kode.
   - **Daftar form** yang sudah ada (tiap form = kartu; klik untuk mendesain).
   - Dua tombol di kanan atas:
     - **Header + Detail** (garis luar) → membuat pasangan tabel master-detail.
     - **Menu / Tabel Baru** (biru) → membuat satu menu/tabel tunggal.

Dari sinilah semua pembuatan menu dilakukan.

---

## 6. Membuat MENU / TABEL BARU (tunggal)

Gunakan ini untuk entitas mandiri (mis. **Supplier**, **Customer**, **Produk**).

1. Di **Application Overview**, klik tombol **Menu / Tabel Baru** (biru).
2. Muncul modal **"Buat Menu / Tabel Baru"**:
   - **Nama Menu / Entitas** `*` — ketik nama, mis. `Supplier`.
   - **Nama Tabel (otomatis)** — kolom read-only yang langsung mem-preview
     nama tabel, mis. `T_SUPPLIER`. (Awalan `T_` = tabel transaksi.)
   - Kotak info biru menjelaskan: setelah dibuat, tabel + menu langsung
     dikompilasi dan Anda diarahkan ke Form Builder.
3. Klik **Buat & Buka** (tombol berubah jadi "Membuat..." saat proses).

Yang terjadi otomatis di belakang layar:
- **CREATE TABLE `T_SUPPLIER`** dengan kolom dasar: `<ENTITY>_ID` (PK RAW(16)),
  `<ENTITY>_NAME`, dan kolom audit (`CREATED_AT`, `CREATED_BY`, dst).
- Dibuat metadata: **DS_DATASET** (source = tabel tadi), **UI_PAGE**,
  **APP_MENU** (item menu di sidebar), dan **1 field awal** (Nama).
- Aplikasi otomatis **di-compile & activate**.
- Anda diarahkan ke **Form Builder** halaman baru tersebut.

> Setelah ini, menu **Supplier** sudah muncul di runtime, tapi baru punya satu
> kolom (Nama). Tambahkan kolom lain di Form Builder (Bagian 7).

---

## 7. Form Builder — Mendesain Form & Kolom

URL: `/studio/designer/form/{pageId}`. Layout **3 kolom**:

```
┌─────────────┬──────────────────────────┬────────────────────┐
│ Field Types │        Canvas            │  Field Properties  │
│ (palette)   │  (preview form + urut)   │  (editor field)    │
└─────────────┴──────────────────────────┴────────────────────┘
```

Header atas: judul form, toggle **preview desktop / tablet / mobile**, dan
tombol hijau **Compile & Activate**.

### 7.1 Menambah field baru
1. Di kolom kiri **Field Types**, klik salah satu widget: **Text, Email,
   Number, Date, Checkbox, Text Area, Dropdown**. (Klik = "Add field".)
2. Muncul modal **"Tambah Field"**:
   - **Label / Judul Field** `*` — teks yang tampil di form, mis. `Alamat` atau
     `BUMN`.
   - **Nama Kolom (otomatis)** — read-only; label distandarisasi jadi nama
     kolom Oracle (spasi & karakter khusus → `_`, huruf besar, maks 30 char).
     Contoh: `Nama Kontak` → `NAMA_KONTAK`.
   - **Tipe Field** — dropdown lengkap: `Text, Email, Number, Integer, Decimal,
     Date, Date & Time, Checkbox, Text Area, Dropdown, List of Values,
     Password`.
   - **Panjang Maksimum (VARCHAR2)** — muncul hanya untuk Text/Email/Password.
   - Kotak info **Tabel target** & **Tipe dataset**.
   - **Tambahkan kolom ke tabel jika belum ada** (checkbox) — muncul bila
     dataset bertipe TABLE. Biarkan **tercentang** agar dijalankan `ALTER TABLE`
     (kolom fisik dibuat sehingga field bisa menyimpan data).
3. Klik **Tambah Field**. Kolom fisik ditambahkan (bila perlu) dan field muncul
   di canvas.

> **Penting:** Field tanpa kolom fisik hanya jadi metadata dan **tidak bisa
> menyimpan data**. Untuk dataset TABLE, selalu biarkan opsi "Tambahkan kolom"
> tercentang saat kolom belum ada.

### 7.2 Mengatur properti field (kolom kanan)
Klik field mana pun di canvas → panel **Field Properties** aktif:
- **Label** — ubah judul tampilan.
- **Type** — ubah tipe field.
- **List of Values** — dropdown untuk menautkan **LOV** (lihat Bagian 8). Isi
  "-- None --" bila tidak pakai.
- **Required / Read-only / Visible** — checkbox perilaku field.
- **Default Value** — nilai awal opsional.
- **Opsi Tanggal** (muncul untuk Date / Date & Time):
  - **Nilai Default**: `Kosong` atau `Tanggal hari ini (SYSDATE)`.
  - **Sertakan jam (tanggal & waktu)**.
- **Opsi Angka** (muncul untuk Number / Integer / Decimal):
  - **Jumlah Desimal** (mis. 0 atau 2; kosongkan = apa adanya).
  - **Pemisah ribuan (1.000.000)**.
- Klik **Update Field** untuk menyimpan. Ikon **🗑 (tempat sampah)** di pojok
  panel untuk menghapus field.

### 7.3 Mengurutkan field
Tarik **drag handle** (ikon garis dua) di sisi kiri kartu field ke atas/bawah.
Urutan otomatis tersimpan.

### 7.4 Kompilasi
Setelah selesai menata field, klik **Compile & Activate** (hijau, kanan atas) →
konfirmasi. Perubahan langsung tampil di runtime.

---

## 8. Visual Designer LOV — `/studio/designer/lov/new`

**LOV (List of Values)** adalah daftar pilihan untuk field dropdown (mis. tipe
pelanggan, status, referensi ke tabel lain). LOV bersifat **global** (tidak
terikat satu aplikasi), sehingga bisa dipakai di form mana pun.

### 8.1 Membuka designer
- Sidebar Studio → **LOVs**, atau langsung buka `/studio/designer/lov/new`.
- Layout **2 kolom**: Editor (kiri) + **Preview** (kanan). Tombol **Save LOV**
  di kanan atas.

### 8.2 Basic Information
- **Code** `*` — kode unik, mis. `LOV_CUSTOMER_TYPE`.
- **Name** `*` — nama tampilan, mis. `Customer Types`.
- **Description** — opsional.

### 8.3 Source Type (pilih salah satu kartu)

**a) Static** — daftar nilai tetap (key-value).
- Klik **Add Row** untuk menambah baris.
- Isi **Value (Code)** = nilai disimpan (mis. `RETAIL`) dan **Label (Display)**
  = teks tampil (mis. `Retail Customer`).
- **Refresh Preview** untuk melihat hasil. Ikon **✕** menghapus baris.

**b) SQL Query** — daftar dari query SELECT bebas.
- Tulis query di editor, mis.:
  `SELECT CUSTOMER_TYPE AS VALUE, CUSTOMER_TYPE_NAME AS LABEL FROM ... ORDER BY ...`
- Klik **Test Query** untuk memvalidasi (hasil hijau = sukses, merah = error).
- Isi **Value Column** (mis. `VALUE`) dan **Label Column** (mis. `LABEL`) sesuai
  alias di query.

**c) Table/View** — daftar dari tabel/view yang sudah ada.
- Pilih **Table / View Name** dari dropdown.
- Isi **Value Column** (nilai disimpan, mis. `OBJECT_ID`) dan **Label Column**
  (teks tampil, mis. `OBJECT_NAME`).
- **Refresh Preview** untuk melihat hasil.

### 8.4 Preview & simpan
- Panel kanan menampilkan dropdown pratinjau, jumlah opsi, dan contoh nilai.
- Klik **Save LOV**.
- **Ingat**: LOV baru muncul di form runtime setelah aplikasi terkait
  **di-compile & activate**.

### 8.5 Menautkan LOV ke field (poin penting)
LOV yang disimpan **tidak otomatis** menempel ke field. Untuk memakainya:
1. Buka **Form Builder** halaman terkait.
2. Klik field yang ingin dijadikan dropdown referensi (mis. field `Tipe`).
3. Di panel **Field Properties → List of Values**, pilih LOV Anda dari dropdown.
4. Klik **Update Field**, lalu **Compile & Activate**.

> Konsep referensi: **Value Column** adalah nilai yang benar-benar disimpan di
> kolom tabel Anda; **Label Column** hanya yang ditampilkan ke pengguna. Jadi
> saat memilih "referensi pelanggan", tentukan Value = kolom ID/kode pelanggan
> dan Label = kolom nama pelanggan.

---

## 9. Membuat HEADER-DETAIL (Master-Detail) — contoh Purchase Order

Gunakan ini bila satu dokumen (header) punya banyak baris (detail), mis.
**Purchase Order** (header) dengan banyak **PO Line** (detail).

### 9.1 Membuat pasangan tabel
1. Di **Application Overview**, klik **Header + Detail**.
2. Muncul modal **"Buat Header + Detail"**:
   - **Nama Header** `*` — mis. `Purchase Order`.
   - **Nama Detail (baris)** `*` — mis. `PO Line`.
   - Info: dibuat 2 tabel — `T_PURCHASE_ORDER` (header) & `T_PO_LINE` (detail)
     dengan kolom **FK** ke header.
3. Klik **Buat & Buka Header**.

Yang terjadi otomatis:
- **CREATE TABLE** header (`T_PURCHASE_ORDER`) + detail (`T_PO_LINE`).
- Kolom **FK `<HEADER>_ID`** di tabel detail menautkan baris ke header-nya.
- Header punya kolom **STATUS** (default `DRAFT`); **tabel detail sengaja tanpa
  STATUS** agar baris tidak terkena aturan visibilitas draft.
- Metadata `DETAIL_CONFIG` pada UI_PAGE header (agar runtime tahu menampilkan
  grid detail).
- Menu detail standalone dihapus (detail hanya diakses via header).
- Aplikasi di-compile & Anda diarahkan ke Form Builder **header**.

### 9.2 Menambah kolom
- **Header**: tambah field di Form Builder header (mis. `No PO`, `Tanggal`,
  `Supplier`) — lihat Bagian 7.
- **Detail**: kembali ke Overview, buka form **PO Line**, tambah kolom baris
  (mis. `Produk`, `Qty`, `Harga`). Lalu **Compile & Activate**.

### 9.3 Tampilan runtime
- Satu halaman terbagi dua secara vertikal oleh **splitter horizontal** yang
  bisa digeser naik/turun (double-click untuk reset proporsi).
- **Atas** = form header. **Bawah** = grid detail.
- Grid detail **hanya menampilkan baris milik header yang sedang dibuka**
  (ter-scope oleh FK). Simpan header dulu sebelum menambah baris detail.

---

## 10. Menjalankan Menu di Runtime (`/app/{appCode}`)

Setelah compile, buka runtime (link **Runtime** di kanan atas Studio, atau
`http://localhost:8080/app/ODAF_DEMO`).

### 10.1 Navigasi
- Menu baru muncul di **sidebar kiri**. Sidebar bisa **di-resize** (tarik tepi)
  dan **di-hide**.
- Klik menu → tampil **grid** (daftar data).

### 10.2 Fitur grid (berlaku di semua halaman tabular)
- **Filter per kolom** — kotak isian di bawah tiap judul kolom untuk menyaring
  spesifik kolom itu (selain kotak "Cari" global).
- **Tombol Kolom** (column chooser) — pilih kolom mana yang ditampilkan.
- **Jumlah record per halaman** — atur page-size sebelum pindah halaman.
- **Wrap** — bungkus teks panjang; **resize lebar kolom** dengan menarik batas
  kolom.
- **Checkbox + Clone** — centang baris lalu **Clone**: data tersalin sebagai
  record baru berstatus **DRAFT** dengan `CREATED_BY` = user aktif. Baris DRAFT
  hanya terlihat oleh pembuatnya sampai diubah ke PUBLISHED.
- Grid mengisi ruang vertikal maksimal (ruang abu-abu kosong diminimalkan).

### 10.3 Form (tambah/ubah data)
- Klik **+ Baru** / baris untuk membuka form. Isi field sesuai desain (dropdown
  LOV, tanggal, angka mengikuti opsi format yang Anda set).
- Klik **Simpan**.
- Untuk **header-detail**: simpan header dulu, lalu di grid detail bawah klik
  **Tambah Baris** untuk menambah baris satu per satu, dan **Simpan Baris**.

### 10.4 Catatan format (mengikuti setting klien)
- **Angka**: pemisah desimal/ribuan mengikuti locale browser klien, sehingga
  bila di-copy ke Excel tetap terbaca sebagai number.
- **Tanggal**: kolom bertipe DATE (bukan timestamp-with-timezone), menyimpan
  tanggal + jam sesuai pilihan "Sertakan jam".

---

## 11. Data Manager — Mode Lanjutan (`/studio`)

Dipakai untuk hal yang belum tersedia di designer visual, atau untuk melihat
data mentah.

- **All Tables** menampilkan katalog tabel dikelompokkan per domain (APP, UI,
  DS, T_, dst).
- Klik tabel → **grid** CRUD generik dengan fitur yang sama (filter kolom,
  column chooser, page-size, clone).
- **+ Baru / Ubah / Hapus** langsung ke baris tabel.
- **Ingat**: mengedit metadata di sini **tidak menjalankan DDL**. Menambah baris
  di `UI_FIELD` **tidak** membuat kolom fisik — gunakan Form Builder untuk itu.
  Data Manager cocok untuk: membuat `APP_APPLICATION`, mengubah nama/urutan
  menu, memperbaiki nilai metadata, atau inspeksi data.

### Alur manual metadata (bila tanpa designer)
Menambah menu secara manual = buat `DS_DATASET` (pilih source table → tabel
fisik dibuat saat save) → tambah kolom via `UI_FIELD` (butuh ALTER manual) →
buat `UI_PAGE` + `APP_MENU`. Alur ini rawan salah; **disarankan pakai tombol
"Menu / Tabel Baru"** yang mengotomasi semuanya.

---

## 12. Ringkasan Alur & Checklist

**Menu tunggal baru:**
1. `/studio/designer` → **Design** pada aplikasi.
2. **Menu / Tabel Baru** → isi Nama → **Buat & Buka**.
3. Form Builder → tambah field (biarkan "Tambahkan kolom" tercentang).
4. (Opsional) buat LOV di `/studio/designer/lov/new`, tautkan via Field
   Properties → List of Values.
5. **Compile & Activate**.
6. Cek di runtime `/app/{appCode}`.

**Header-Detail:**
1. **Design** → **Header + Detail** → isi Nama Header & Detail → **Buat**.
2. Tambah kolom header (Form Builder header).
3. Tambah kolom detail (Overview → form detail).
4. **Compile & Activate** → cek runtime (form header + grid detail).

**Checklist wajib sebelum bilang "selesai":**
- [ ] Kolom fisik dibuat (bukan hanya metadata) untuk tiap field data.
- [ ] LOV (bila ada) sudah ditautkan ke field, bukan sekadar disimpan.
- [ ] Sudah **Compile & Activate**.
- [ ] Diverifikasi tampil & tersimpan di runtime.

---

## 13. Troubleshooting

| Gejala | Penyebab & Solusi |
|--------|-------------------|
| Menu/kolom baru tak muncul di runtime | Belum **Compile & Activate**. Jalankan tombol Compile, lalu **hard refresh** (Ctrl+F5). |
| Data tidak tersimpan / kolom hilang saat simpan | Field hanya metadata tanpa kolom fisik. Tambah lewat Form Builder dengan opsi "Tambahkan kolom ke tabel" tercentang. |
| Dropdown LOV kosong di runtime | LOV belum ditautkan ke field, atau belum compile. Cek Field Properties → List of Values, lalu compile. |
| Perubahan sudah save tapi tampilan lama | Cache tampilan. Lakukan **Ctrl+F5** (hard refresh). |
| Baris detail tidak muncul | Header belum disimpan, atau Anda belum memilih header. Grid detail ter-scope per header. |
| "App Wizard - Coming Soon" saat New Application | Wizard aplikasi baru belum tersedia. Buat aplikasi via Data Manager `APP_APPLICATION` (Bagian 4). |
| Kompilasi gagal | Baca pesan error merah di layar; umumnya nama kolom/tabel tidak valid atau duplikat. Perbaiki lalu compile ulang. |

### Kompilasi via CLI (alternatif)
```bash
docker exec odaf-app php artisan odaf:compile ODAF_DEMO --activate
```

---

*Selesai. Dokumen ini mengikuti UI ODAF Studio yang berjalan saat penulisan;
bila ada tombol/label berubah setelah pembaruan aplikasi, sesuaikan langkahnya.*
