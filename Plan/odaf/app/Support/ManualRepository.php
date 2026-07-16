<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Repositori untuk manual pengguna berbasis web (tabel APP_MANUAL).
 *
 * Manual disimpan sebagai kumpulan "bagian" (rows) berisi konten Markdown yang
 * dirender menjadi HTML. Dapat dibaca semua pengguna dan diedit superuser.
 * Kelas ini juga meng-install tabelnya secara idempotent (konsisten dengan
 * konvensi ODAF yang menjalankan DDL dari sisi aplikasi).
 */
final class ManualRepository
{
    /** Cek apakah tabel APP_MANUAL sudah ada. */
    public function isInstalled(): bool
    {
        $row = DB::selectOne(
            "SELECT 1 AS X FROM USER_TABLES WHERE TABLE_NAME = 'APP_MANUAL'"
        );

        return $row !== null;
    }

    /**
     * Buat tabel + seed konten default bila belum ada. Aman dipanggil berulang.
     */
    public function ensureInstalled(): void
    {
        if (! $this->isInstalled()) {
            $this->createTable();
        }

        // Seed konten default saat tabel masih kosong (baik baru dibuat aplikasi
        // maupun disediakan lewat DDL init). Menghapus semua bagian akan
        // mengembalikan konten default pada kunjungan berikutnya.
        if ($this->isEmpty()) {
            $this->seedDefaults();
        }
    }

    private function isEmpty(): bool
    {
        if (! $this->isInstalled()) {
            return false;
        }

        $row = $this->normalizeRow(DB::selectOne('SELECT COUNT(*) AS C FROM APP_MANUAL'));

        return (int) ($row['C'] ?? 0) === 0;
    }

    private function createTable(): void
    {
        // DDL Oracle auto-commit; dibungkus PL/SQL agar idempotent bila ada race.
        DB::unprepared(<<<'SQL'
        DECLARE
            v_count NUMBER;
        BEGIN
            SELECT COUNT(*) INTO v_count FROM USER_TABLES WHERE TABLE_NAME = 'APP_MANUAL';
            IF v_count = 0 THEN
                EXECUTE IMMEDIATE '
                    CREATE TABLE APP_MANUAL (
                        OBJECT_ID     RAW(16)       DEFAULT SYS_GUID() NOT NULL,
                        SLUG          VARCHAR2(100) NOT NULL,
                        TITLE         VARCHAR2(200) NOT NULL,
                        BODY_MD       CLOB,
                        DISPLAY_ORDER NUMBER(10)    DEFAULT 100 NOT NULL,
                        STATUS        VARCHAR2(30)  DEFAULT ''PUBLISHED'' NOT NULL,
                        VERSION_NO    NUMBER(10)    DEFAULT 1 NOT NULL,
                        CREATED_AT    TIMESTAMP     DEFAULT SYSTIMESTAMP,
                        CREATED_BY    VARCHAR2(100),
                        UPDATED_AT    TIMESTAMP,
                        UPDATED_BY    VARCHAR2(100),
                        CONSTRAINT PK_APP_MANUAL PRIMARY KEY (OBJECT_ID),
                        CONSTRAINT UQ_APP_MANUAL_SLUG UNIQUE (SLUG)
                    )';
            END IF;
        END;
        SQL);
    }

    /**
     * Semua bagian manual (terurut). Setiap item: ID, SLUG, TITLE,
     * DISPLAY_ORDER, BODY_MD, HTML (render markdown).
     *
     * @return array<int, array<string, mixed>>
     */
    public function sections(): array
    {
        if (! $this->isInstalled()) {
            return [];
        }

        $rows = DB::select(<<<'SQL'
            SELECT RAWTOHEX(OBJECT_ID) AS ID, SLUG, TITLE, DISPLAY_ORDER,
                   BODY_MD, VERSION_NO, UPDATED_AT, UPDATED_BY
              FROM APP_MANUAL
             WHERE STATUS = 'PUBLISHED'
             ORDER BY DISPLAY_ORDER, TITLE
        SQL);

        return array_map(function ($row) {
            $data = $this->normalizeRow($row);
            $body = $this->clobToString($data['BODY_MD'] ?? '');

            return [
                'ID' => $data['ID'],
                'SLUG' => $data['SLUG'],
                'TITLE' => $data['TITLE'],
                'DISPLAY_ORDER' => (int) $data['DISPLAY_ORDER'],
                'BODY_MD' => $body,
                'HTML' => $this->renderHtml($body),
                'VERSION_NO' => (int) ($data['VERSION_NO'] ?? 1),
                'UPDATED_AT' => $data['UPDATED_AT'] ?? null,
                'UPDATED_BY' => $data['UPDATED_BY'] ?? null,
            ];
        }, $rows);
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        $row = DB::selectOne(<<<'SQL'
            SELECT RAWTOHEX(OBJECT_ID) AS ID, SLUG, TITLE, DISPLAY_ORDER, BODY_MD
              FROM APP_MANUAL WHERE OBJECT_ID = HEXTORAW(?)
        SQL, [$id]);

        if ($row === null) {
            return null;
        }

        $data = $this->normalizeRow($row);
        $data['BODY_MD'] = $this->clobToString($data['BODY_MD'] ?? '');
        $data['DISPLAY_ORDER'] = (int) ($data['DISPLAY_ORDER'] ?? 0);

        return $data;
    }

    public function create(string $title, string $slug, string $body, int $order, ?string $userId): void
    {
        DB::insert(<<<'SQL'
            INSERT INTO APP_MANUAL (OBJECT_ID, SLUG, TITLE, BODY_MD, DISPLAY_ORDER, STATUS, VERSION_NO, CREATED_AT, CREATED_BY)
            VALUES (SYS_GUID(), ?, ?, ?, ?, 'PUBLISHED', 1, SYSTIMESTAMP, ?)
        SQL, [$slug, $title, $body, $order, $userId]);
    }

    public function update(string $id, string $title, string $slug, string $body, int $order, ?string $userId): void
    {
        DB::update(<<<'SQL'
            UPDATE APP_MANUAL
               SET TITLE = ?, SLUG = ?, BODY_MD = ?, DISPLAY_ORDER = ?,
                   VERSION_NO = VERSION_NO + 1, UPDATED_AT = SYSTIMESTAMP, UPDATED_BY = ?
             WHERE OBJECT_ID = HEXTORAW(?)
        SQL, [$title, $slug, $body, $order, $userId, $id]);
    }

    public function delete(string $id): void
    {
        DB::delete('DELETE FROM APP_MANUAL WHERE OBJECT_ID = HEXTORAW(?)', [$id]);
    }

    /** Slug unik & aman untuk anchor id. */
    public function uniqueSlug(string $title, ?string $excludeId = null): string
    {
        $base = Str::slug($title) ?: 'bagian';
        $slug = $base;
        $i = 2;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?string $excludeId): bool
    {
        if ($excludeId !== null) {
            $row = DB::selectOne(
                'SELECT 1 AS X FROM APP_MANUAL WHERE SLUG = ? AND OBJECT_ID <> HEXTORAW(?)',
                [$slug, $excludeId]
            );
        } else {
            $row = DB::selectOne('SELECT 1 AS X FROM APP_MANUAL WHERE SLUG = ?', [$slug]);
        }

        return $row !== null;
    }

    /**
     * Render Markdown -> HTML. Raw HTML dibuang & link tak aman diblokir
     * (pertahanan berlapis walau editor hanya untuk superuser).
     */
    public function renderHtml(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Normalisasi baris oci8: kunci kolom di-uppercase (oci8 mengembalikan
     * lowercase). CLOB dibiarkan apa adanya (dikonversi terpisah bila perlu).
     *
     * @return array<string, mixed>
     */
    private function normalizeRow(mixed $row): array
    {
        $out = [];
        foreach ((array) $row as $key => $value) {
            $out[strtoupper((string) $key)] = $value;
        }

        return $out;
    }

    /** Konversi nilai CLOB (resource/objek) ke string. */
    private function clobToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_resource($value)) {
            $contents = stream_get_contents($value);

            return $contents === false ? '' : $contents;
        }

        return (string) $value;
    }

    /** Seed bagian manual default (hanya saat tabel baru dibuat). */
    private function seedDefaults(): void
    {
        $order = 10;
        foreach ($this->defaultSections() as $section) {
            $this->create(
                $section['title'],
                $this->uniqueSlug($section['title']),
                $section['body'],
                $order,
                'SYSTEM',
            );
            $order += 10;
        }
    }

    /** @return array<int, array{title: string, body: string}> */
    private function defaultSections(): array
    {
        $s = [];

        $s[] = ['title' => 'Pendahuluan & Konsep Dasar', 'body' => <<<'MD'
Selamat datang di **Manual ODAF Studio**. Panduan ini membantu Anda membuat
menu/tabel baru dari nol: dari Data Manager, Applications (Visual Designer),
tombol Design, pembuatan Menu/Tabel Baru dan Header-Detail, hingga Visual
Designer LOV.

ODAF adalah platform **metadata-driven**: menu, form, dan grid tidak
di-hardcode melainkan **digenerate dari metadata** yang dikompilasi menjadi
Runtime Package. Ada dua lapisan:

- **Metadata** (cetak biru): `APP_APPLICATION`, `APP_MENU`, `UI_PAGE`,
  `UI_FIELD`, `DS_DATASET`, `DS_LOV`.
- **Tabel fisik** (data bisnis): mis. `T_SUPPLIER`, `T_PURCHASE_ORDER`.

> **Aturan emas:** setiap perubahan metadata baru muncul di runtime setelah
> **Compile & Activate**. Konvensi nama tabel transaksi memakai awalan `T_`
> (mis. "Supplier" menjadi `T_SUPPLIER`).

**Akses cepat:** [Data Manager](/studio) · [Applications](/studio/designer) ·
[Visual Designer LOV](/studio/designer/lov/new) · [Runtime](/app/ODAF_DEMO)
MD];
        $s[] = ['title' => 'Peta ODAF Studio & Login', 'body' => <<<'MD'
Studio punya dua area yang saling melengkapi:

1. **[Visual Designer](/studio/designer)** — cara utama & disarankan. Membuat
   tabel + menu + form secara visual; otomatis menjalankan `CREATE TABLE`,
   `ALTER TABLE`, dan kompilasi.
2. **[Data Manager](/studio)** — editor CRUD generik untuk semua tabel
   (mode lanjutan). Mengedit di sini hanya menyimpan data/metadata, **tidak**
   menjalankan DDL.

### Login
1. Buka [halaman utama](/app/ODAF_DEMO) — bila belum login akan diarahkan ke
   halaman Login.
2. Masuk dengan kredensial Anda (admin awal: `admin`).
3. Setelah masuk, gunakan tautan **Studio** (kanan atas) untuk authoring, atau
   **Manual** untuk membuka panduan ini kapan saja.
MD];
        $s[] = ['title' => 'Applications Dashboard', 'body' => <<<'MD'
Buka **[Applications](/studio/designer)**. Halaman ini menampilkan semua
aplikasi sebagai kartu. Tiap kartu berisi nama & kode, badge status
(PUBLISHED/DRAFT), statistik (Forms, Fields, Menus, LOVs, versi aktif), dan
tiga tombol aksi:

- **Design** (biru, ikon pensil) — masuk ke **Application Overview**. Ini pintu
  utama untuk membuat menu.
- **⚡ Compile** (ikon petir) — meng-compile & mengaktifkan aplikasi. Wajib
  dijalankan agar perubahan tampil di runtime.
- **⚙ Settings** (ikon gir) — membuka edit baris `APP_APPLICATION` di Data
  Manager (ganti nama/deskripsi/status).

> **Catatan:** tombol **New Application** saat ini belum berfungsi (menampilkan
> "App Wizard - Coming Soon"). Untuk aplikasi baru dari nol, gunakan Data
> Manager (lihat bagian berikut). Untuk sekadar menambah menu pada aplikasi
> yang sudah ada, langsung klik **Design**.
MD];
        $s[] = ['title' => 'Membuat Aplikasi Baru (via Data Manager)', 'body' => <<<'MD'
Lakukan bagian ini hanya jika Anda perlu **aplikasi baru** (bukan sekadar menu
baru). Karena wizard visual belum ada, buat lewat Data Manager:

1. Buka **[Data Manager → All Tables](/studio)**.
2. Cari grup **APP** dan pilih tabel `APP_APPLICATION`.
3. Klik **+ Baru**, lalu isi:
   - **OBJECT_CODE**: kode unik huruf besar tanpa spasi, mis. `MYAPP`.
   - **OBJECT_NAME**: nama tampilan, mis. `Aplikasi Saya`.
   - **STATUS**: `PUBLISHED`.
4. Klik **Simpan**, lalu kembali ke [Applications](/studio/designer).
5. Klik **Design** pada kartu aplikasi baru untuk mulai menambah menu.

> Setelah aplikasi memiliki minimal satu menu dan sudah dikompilasi, ia dapat
> dibuka di `/app/MYAPP`.
MD];
        $s[] = ['title' => 'Design → Application Overview', 'body' => <<<'MD'
Di **[Applications](/studio/designer)**, klik **Design** pada kartu aplikasi
(mis. ODAF Demo Application). Anda masuk ke **Application Overview** yang
menampilkan:

- Judul aplikasi + kode.
- **Daftar form** yang sudah ada (tiap form = kartu; klik untuk mendesain).
- Dua tombol di kanan atas:
  - **Header + Detail** — membuat pasangan tabel master-detail.
  - **Menu / Tabel Baru** — membuat satu menu/tabel tunggal.

Dari halaman inilah semua pembuatan menu dilakukan.
MD];
        $s[] = ['title' => 'Membuat Menu / Tabel Baru (tunggal)', 'body' => <<<'MD'
Gunakan ini untuk entitas mandiri (mis. Supplier, Customer, Produk).

1. Di **Application Overview**, klik tombol **Menu / Tabel Baru** (biru).
2. Pada modal **Buat Menu / Tabel Baru**:
   - **Nama Menu / Entitas** — ketik nama, mis. `Supplier`.
   - **Nama Tabel (otomatis)** — read-only; mem-preview nama tabel, mis.
     `T_SUPPLIER` (awalan `T_` = transaksi).
3. Klik **Buat & Buka**.

Yang terjadi otomatis:

- `CREATE TABLE T_SUPPLIER` dengan kolom dasar: PK `<ENTITY>_ID`,
  `<ENTITY>_NAME`, dan kolom audit.
- Dibuat metadata **DS_DATASET**, **UI_PAGE**, **APP_MENU**, dan 1 field awal.
- Aplikasi **di-compile & activate** otomatis.
- Anda diarahkan ke **Form Builder** halaman baru tersebut.

> Setelah ini menu sudah muncul di [Runtime](/app/ODAF_DEMO) namun baru punya
> satu kolom (Nama). Tambahkan kolom lain di Form Builder (bagian berikut).
MD];
        $s[] = ['title' => 'Form Builder — Mendesain Form & Kolom', 'body' => <<<'MD'
Form Builder punya layout **3 kolom**: palette Field Types (kiri), canvas
preview (tengah), dan Field Properties (kanan). Di header ada toggle preview
desktop/tablet/mobile dan tombol hijau **Compile & Activate**.

### Menambah field
1. Klik salah satu widget di **Field Types**: Text, Email, Number, Date,
   Checkbox, Text Area, Dropdown.
2. Pada modal **Tambah Field**, isi:
   - **Label / Judul Field** — teks tampil, mis. `Alamat` atau `BUMN`.
   - **Nama Kolom (otomatis)** — label distandarisasi jadi nama kolom Oracle
     (spasi & karakter khusus jadi `_`, huruf besar, maks 30 karakter).
   - **Tipe Field** — Text, Email, Number, Integer, Decimal, Date, Date & Time,
     Checkbox, Text Area, Dropdown, List of Values, Password.
   - **Panjang Maksimum** (untuk Text/Email/Password).
   - **Tambahkan kolom ke tabel jika belum ada** — biarkan tercentang agar
     `ALTER TABLE` dijalankan sehingga field bisa menyimpan data.
3. Klik **Tambah Field**.

> Field tanpa kolom fisik hanya jadi metadata dan tidak bisa menyimpan data.

### Properti field (kolom kanan)
Klik field di canvas untuk mengatur: **Label**, **Type**, **List of Values**
(tautkan LOV), **Required/Read-only/Visible**, **Default Value**, **Opsi
Tanggal** (default kosong/SYSDATE, sertakan jam), dan **Opsi Angka** (jumlah
desimal, pemisah ribuan). Klik **Update Field**. Ikon tempat sampah menghapus
field. Tarik **drag handle** untuk mengurutkan.

### Kompilasi
Klik **Compile & Activate** (hijau) agar perubahan tampil di
[Runtime](/app/ODAF_DEMO).
MD];
        $s[] = ['title' => 'Visual Designer LOV', 'body' => <<<'MD'
**LOV (List of Values)** adalah daftar pilihan untuk field dropdown (mis. tipe
pelanggan, status, referensi ke tabel lain). LOV bersifat global sehingga bisa
dipakai di form mana pun.

Buka **[Visual Designer LOV](/studio/designer/lov/new)**. Layout 2 kolom:
Editor (kiri) + Preview (kanan), dengan tombol **Save LOV** di kanan atas.

### Basic Information
- **Code** — kode unik, mis. `LOV_CUSTOMER_TYPE`.
- **Name** — nama tampilan, mis. `Customer Types`.
- **Description** — opsional.

### Source Type
- **Static** — nilai tetap. Klik **Add Row**, isi **Value (Code)** dan
  **Label (Display)**.
- **SQL Query** — tulis SELECT, klik **Test Query**, lalu isi **Value Column**
  dan **Label Column** sesuai alias.
- **Table/View** — pilih tabel/view, tentukan **Value Column** (nilai
  disimpan) dan **Label Column** (teks tampil).

Panel kanan menampilkan pratinjau. Klik **Save LOV**.

### Menautkan LOV ke field
LOV tidak otomatis menempel. Buka **Form Builder**, klik field, lalu pilih LOV
di **Field Properties → List of Values**, klik **Update Field**, dan
**Compile & Activate**.

> Value Column = nilai yang disimpan di kolom Anda; Label Column = yang
> ditampilkan ke pengguna.
MD];
        $s[] = ['title' => 'Membuat Header-Detail (Master-Detail)', 'body' => <<<'MD'
Gunakan ini bila satu dokumen (header) punya banyak baris (detail), mis.
**Purchase Order** dengan banyak **PO Line**.

### Membuat pasangan tabel
1. Di **Application Overview**, klik **Header + Detail**.
2. Pada modal, isi **Nama Header** (mis. `Purchase Order`) dan
   **Nama Detail (baris)** (mis. `PO Line`).
3. Klik **Buat & Buka Header**.

Yang terjadi otomatis:

- `CREATE TABLE` header (`T_PURCHASE_ORDER`) dan detail (`T_PO_LINE`).
- Kolom **FK `<HEADER>_ID`** di tabel detail menautkan baris ke header-nya.
- Header punya kolom **STATUS** (default `DRAFT`); tabel detail sengaja tanpa
  STATUS agar baris tidak terkena aturan visibilitas draft.
- Metadata `DETAIL_CONFIG` diisi & aplikasi dikompilasi.

### Menambah kolom
- **Header**: tambah field di Form Builder header (mis. No PO, Tanggal,
  Supplier).
- **Detail**: kembali ke Overview, buka form detail (PO Line), tambah kolom
  baris (mis. Produk, Qty, Harga). Lalu **Compile & Activate**.

### Tampilan runtime
Satu halaman terbagi dua oleh **splitter horizontal** yang bisa digeser
naik/turun (double-click untuk reset). Atas = form header, bawah = grid detail.
Grid detail hanya menampilkan baris milik header yang sedang dibuka. Simpan
header dulu sebelum menambah baris detail.
MD];
        $s[] = ['title' => 'Menjalankan Menu di Runtime', 'body' => <<<'MD'
Setelah compile, buka **[Runtime](/app/ODAF_DEMO)**.

### Navigasi
Menu baru muncul di sidebar kiri. Sidebar bisa di-resize (tarik tepi) dan
di-hide. Klik menu untuk menampilkan grid data.

### Fitur grid (berlaku di semua halaman tabular)
- **Filter per kolom** — kotak isian di bawah tiap judul kolom.
- **Tombol Kolom** — pilih kolom yang ditampilkan (column chooser).
- **Jumlah record per halaman** — atur page-size.
- **Wrap** & **resize lebar kolom** dengan menarik batas kolom.
- **Checkbox + Clone** — salin baris jadi record baru berstatus DRAFT dengan
  `CREATED_BY` = user aktif; baris DRAFT hanya terlihat pembuatnya.

### Form (tambah/ubah data)
Klik **+ Baru** atau baris untuk membuka form; isi field (dropdown LOV,
tanggal, angka mengikuti opsi format), lalu **Simpan**. Untuk header-detail,
simpan header dulu, lalu di grid detail klik **Tambah Baris** dan
**Simpan Baris**.

### Catatan format
Angka mengikuti locale browser klien (aman di-copy ke Excel sebagai number).
Kolom tanggal bertipe DATE (tanggal + jam sesuai opsi "Sertakan jam").
MD];
        $s[] = ['title' => 'Data Manager Lanjutan & Troubleshooting', 'body' => <<<'MD'
### Data Manager — mode lanjutan
Buka **[Data Manager](/studio)** untuk hal yang belum tersedia di designer,
atau untuk melihat data mentah. **All Tables** menampilkan katalog tabel per
domain; tiap tabel punya CRUD generik (filter kolom, column chooser, page-size,
clone).

> Mengedit metadata di sini **tidak** menjalankan DDL. Menambah baris di
> `UI_FIELD` tidak membuat kolom fisik — gunakan Form Builder untuk itu.

### Troubleshooting
- **Menu/kolom baru tak muncul di runtime** — belum Compile & Activate.
  Jalankan Compile lalu hard refresh (Ctrl+F5).
- **Data tidak tersimpan / kolom hilang** — field hanya metadata tanpa kolom
  fisik. Tambah lewat Form Builder dengan opsi "Tambahkan kolom" tercentang.
- **Dropdown LOV kosong** — LOV belum ditautkan ke field atau belum compile.
- **Tampilan masih lama** — lakukan hard refresh (Ctrl+F5).
- **Baris detail tidak muncul** — header belum disimpan/dipilih; grid detail
  ter-scope per header.
- **"App Wizard - Coming Soon"** — wizard aplikasi baru belum tersedia; buat
  aplikasi via [Data Manager](/studio) `APP_APPLICATION`.

Kompilasi via CLI (alternatif):
`docker exec odaf-app php artisan odaf:compile ODAF_DEMO --activate`
MD];

        $s[] = ['title' => 'Sinkronisasi (Ekspor/Impor) Aplikasi', 'body' => <<< 'MD'
ODAF memiliki fitur bawaan untuk melakukan penarikan (ekspor) keseluruhan arsitektur aplikasi (metadata) beserta tabel fisik dan isi datanya secara penuh, untuk kemudian dipindahkan (impor) ke server lain.

Proses ini dilakukan melalui antarmuka baris perintah (CLI) atau terminal server. 

### 1. Ekspor (Menyedot Aplikasi)
Jalankan perintah ini di server sumber (lokal):
```bash
docker compose exec app php artisan odaf:metadata-export <KODE_APLIKASI>
# Contoh: docker compose exec app php artisan odaf:metadata-export NEXUS
```
Perintah ini akan secara ajaib menyedot:
1. Seluruh susunan menu, form, kolom, dan akses kontrol (Metadata).
2. Seluruh tabel fisik di Oracle yang berawalan `<KODE_APLIKASI>_` (DDL).
3. Seluruh isi baris data dari tabel-tabel fisik tersebut (DML).

Hasilnya akan disimpan dalam **satu file JSON** di `storage/app/metadata/<KODE_APLIKASI>.json`.

### 2. Impor (Menimpa ke Server Tujuan)
Pastikan Anda telah menyalin file JSON hasil ekspor tadi ke server tujuan di dalam direktori `storage/app/metadata/`.
Jalankan perintah ini di server tujuan:
```bash
docker compose exec app php artisan odaf:metadata-import storage/app/metadata/<KODE_APLIKASI>.json
# Contoh: docker compose exec app php artisan odaf:metadata-import storage/app/metadata/NEXUS.json
```
Perintah ini akan:
1. Memasukkan seluruh metadata form dan menu.
2. Membentuk tabel-tabel fisik jika belum ada.
3. **Mengosongkan** tabel fisik lama, lalu memasukkan data hasil ekspor (konsep timpa semua / overwrite).
4. Melakukan *Compile & Activate* secara otomatis agar aplikasi langsung dapat dipakai.
MD];

        // __SECTIONS__
        return $s;
    }
}
