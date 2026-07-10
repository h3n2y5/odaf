# ODAF / MDAF Platform

Oracle Dynamic Application Framework — platform aplikasi bisnis berbasis metadata.
Repositori ini adalah implementasi (kode) dari spesifikasi pada `../docs`
(Volume 1 SAD, Volume 2 Database, Volume 3 Core).

Status: **Fase F1 — Core Runtime (MVP)** (lihat `../DEVELOPMENT-PLAN.md`).

Runtime metadata-driven sudah berjalan end-to-end: metadata → compiler →
Runtime Package → kernel → engine (Dataset/Security/Validation/Audit/Render) →
UI (menu, grid, form) yang digenerate otomatis.

---

## Tumpukan Teknologi

| Layer | Teknologi |
|-------|-----------|
| Backend | PHP 8.4, Laravel 11 |
| UI | Blade, Livewire 3, Alpine.js |
| Database | Oracle 23ai (metadata + data bisnis) |
| Driver | yajra/laravel-oci8 (oci8) |
| Dev env | Docker + Docker Compose |

Host tidak perlu memasang PHP/Composer — semuanya berjalan di container.

---

## Struktur Direktori

```
odaf/
├── app/                     # Kode aplikasi Laravel (provider, http)
│   └── Providers/           # AppServiceProvider, OdafServiceProvider (binding BB)
├── src/                     # Kode platform ODAF (namespace Odaf\), berlapis per building block
│   ├── Metadata/            # BB-01 Metadata Repository (kontrak)
│   ├── Compiler/            # BB-02 Metadata Compiler (kontrak + MIR/RuntimePackage)
│   ├── Runtime/             # BB-03 Unified Runtime Kernel (kontrak)
│   ├── Engine/
│   │   ├── Dataset/         # BB-05 CRUD generik
│   │   ├── Security/        # BB-07 RBAC
│   │   ├── Validation/      # BB-09 Rule engine
│   │   ├── Render/          # BB-04 Renderer
│   │   └── Audit/           # BB-08 Audit
│   └── Support/Identity/    # Pembangkit OBJECT_ID (ULID / UUIDv7)
├── config/database.php      # Koneksi Oracle (design-time & runtime)
├── database/init/           # DDL + seed metadata (auto-run saat DB pertama dibuat)
├── docker/                  # Dockerfile PHP 8.4 + oci8, php.ini
├── docker-compose.yml       # Service: oracle (23ai) + app (PHP 8.4)
└── bootstrap/ routes/ public/  # Kerangka Laravel 11
```

Aturan dependensi (Vol.1 Bab 10): dependensi mengalir ke bawah
`Metadata → Compiler → Runtime → Engines`. Runtime hanya mengeksekusi artefak
terkompilasi, tidak pernah membaca metadata design-time (CORE-002).

---

## Prasyarat

- Docker Desktop **berjalan** (mode Linux containers).
- Koneksi internet pada build pertama (mengunduh image PHP, Oracle Instant Client, dependensi Composer).

---

## Menjalankan (Quick Start)

```bash
# 1. Bangun & jalankan Oracle + App (build pertama mengunduh image + client)
docker compose up -d --build

# 2. Pantau proses. Container 'app' otomatis: salin .env, composer install,
#    key:generate, lalu menjalankan server.
docker compose logs -f oracle   # tunggu "DATABASE IS READY TO USE"
docker compose logs -f app      # tunggu "http server di :8000"

# 3. Uji
#    http://localhost:8080           -> info platform
#    http://localhost:8080/health/db -> harus "oracle: reachable"
```

Init pertama Oracle memakan ~1-3 menit. Container `app` menunggu Oracle sehat
(via `depends_on: condition: service_healthy`) sebelum start.

Skrip di `database/init/` dijalankan otomatis oleh container Oracle **hanya saat
volume database pertama kali dibuat**. Untuk menjalankan ulang dari awal:

```bash
docker compose down -v   # menghapus volume data (destruktif) lalu up lagi
```

---

## Skema Metadata (F0)

DDL di `database/init/` mengikuti Naming Standards & Key Strategy Volume 2:

| Domain | Tabel |
|--------|-------|
| SYS_ | `SYS_STATUS`, `SYS_LANGUAGE` |
| SEC_ | `SEC_USER`, `SEC_ROLE`, `SEC_PERMISSION`, `SEC_USER_ROLE`, `SEC_ROLE_PERMISSION` |
| APP_ | `APP_APPLICATION`, `APP_MODULE`, `APP_MENU` |
| UI_ | `UI_PAGE`, `UI_FIELD` |
| DS_ | `DS_DATASET`, `DS_LOV` |
| VAL_ | `VAL_RULE` |
| RT_ | `RT_PACKAGE` (runtime, immutable) |
| AUD_ | `AUD_EVENT` (append-only) |

Konvensi: `OBJECT_ID RAW(16)` (PK, ULID/UUIDv7), `OBJECT_CODE` (business key unik),
`OBJECT_NAME` (display). FK selalu mereferensi `OBJECT_ID`. Boolean berakhiran `_FLAG`.

Seed (`90_seed.sql`) menyertakan modul demo **Master Customer** (aplikasi, modul,
menu, dataset, page, field, validasi) + tabel bisnis `CUSTOMER` sebagai bahan uji
penerimaan Fase F1.

> Catatan: `SEC_USER.PASSWORD_HASH` pada seed adalah placeholder. Setel hash bcrypt
> yang benar setelah aplikasi berjalan.

---

## Verifikasi

```bash
# Lint gaya kode
docker compose exec app composer lint

# Analisis statis
docker compose exec app composer analyse

# Test
docker compose exec app composer test
```

---

## Menjalankan Aplikasi Runtime (Fase F1)

Setelah container hidup dan Oracle sehat:

```bash
# 1. Kompilasi metadata aplikasi demo menjadi Runtime Package lalu aktifkan.
docker compose exec app php artisan odaf:compile ODAF_DEMO --activate

#    (opsional) hanya validasi tanpa menyimpan package:
docker compose exec app php artisan odaf:compile ODAF_DEMO --dry-run

# 2. Buka aplikasi lalu login:
#    http://localhost:8080/login   (redirect otomatis dari /app/... bila belum login)
#    Kredensial default: username = admin, password = password
#    Setelah login: beranda + menu; menu "Customer" -> grid -> Baru/Ubah -> form
```

### Autentikasi

Login diautentikasi terhadap tabel `SEC_USER` (guard `web`, provider `odaf`
kustom — lihat `app/Auth/`). Seluruh route `/app/*` dilindungi middleware `auth`.
Pengguna terautentikasi (OBJECT_ID + role) diteruskan ke `ExecutionContext`
sehingga Security Engine (RBAC) dan kolom audit memakai identitas nyata.

Reset / set password pengguna:

```bash
docker compose exec app php artisan odaf:user:password admin
```

Modul "Master Customer" (menu, grid, form, validasi, audit) berjalan **100% dari
metadata** — tidak ada controller/model/view khusus entitas. Menambah modul baru
= menambah tabel bisnis + baris metadata, lalu `odaf:compile` ulang.

### Alur eksekusi (Vol.1 Bab 09)

`Resolve Context → Locate Package → Authorize → Validate → Persist → Audit`

- **Compiler** (`Odaf\Compiler\MetadataCompiler`) menghasilkan Runtime Package
  deterministik (checksum SHA-256 dari payload kanonik).
- **Runtime Kernel** (`Odaf\Runtime\UnifiedRuntimeKernel`) hanya mengeksekusi
  package terkompilasi — tidak pernah membaca metadata design-time (CORE-002).
- **Engine**: Dataset (CRUD generik), Security (RBAC), Validation (rule
  metadata-driven), Audit (append-only), Render (view-model HTML).

### Notifikasi (F2)

Notification Engine (BB-10) event-driven & metadata-driven. Definisi di
`NTF_NOTIFICATION` (template subject/body dengan `{{param}}`) + `NTF_SUBSCRIPTION`
(event → notifikasi + aturan penerima), dikompilasi ke package dan diindeks per
event. Transisi workflow mem-publish event `WF.<workflow>.<aksi>` yang memicu
notifikasi:

- **SUBMIT** → anggota role `ADMIN` (approver) menerima notifikasi in-app.
- **APPROVE / REJECT** → pemilik dokumen (pengaju) menerima notifikasi.

Channel abstraksi (`NotificationChannelInterface`): **In-App** (tersimpan di
`RT_NOTIFICATION`, tampil di lonceng header) + **Log** (fallback / stand-in
Email/SMS/Webhook). Resolusi penerima: `ROLE | USER | OWNER | CURRENT`. Lonceng
notifikasi di header menampilkan jumlah belum dibaca + daftar + tandai dibaca.

Alur publish: `event → subscription → recipient → render template → channel →
RT_NOTIFICATION`. Engine hanya mengeksekusi subscription terkompilasi (NTF-002).

### Workflow (F2)

Dataset dapat diatur oleh workflow approval yang didefinisikan lewat metadata
(`WF_WORKFLOW` / `WF_ACTIVITY` / `WF_TRANSITION`) dan dikompilasi ke dalam
Runtime Package (BB-06). Demo `Master Customer` memakai alur:

```
DRAFT --SUBMIT--> PENDING_APPROVAL --APPROVE--> APPROVED
                                   --REJECT---> DRAFT
```

- Saat record dibuat, instance workflow otomatis dimulai (`RT_WORKFLOW_INSTANCE`).
- Form menampilkan status, tombol aksi (difilter per role), dan riwayat
  (`RT_WORKFLOW_HISTORY`). Transisi `APPROVE`/`REJECT` hanya untuk role `ADMIN`.
- Setiap transisi diaudit (`AUD_EVENT`, `WORKFLOW_*`).

Alur perform: `authorize (role) -> transition (guard) -> update instance ->
history -> audit`. Engine hanya mengeksekusi graph terkompilasi; definisi
workflow tidak pernah dibaca runtime secara langsung (CORE-002).

### LOV (List of Values) di form runtime

Field dapat memakai LOV agar tampil sebagai **dropdown terisi** (bukan input teks).
LOV Engine mendukung tiga tipe:
- **STATIC** — `SOURCE_QUERY` berisi JSON: `[{"value":"GOLD","label":"Emas"}, ...]`,
  atau map `{"A":"Aktif","N":"Nonaktif"}`, atau array skalar `["Kecil","Besar"]`.
- **SQL** — `SOURCE_QUERY` berisi SELECT; `VALUE_COLUMN`/`LABEL_COLUMN` menamai kolom.
- **VIEW** — `SOURCE_QUERY` berisi nama tabel/view; kolom dari `VALUE_COLUMN`/`LABEL_COLUMN`.

**LOV parametrik (bergantung record aktif):** `SOURCE_QUERY` boleh memakai
placeholder `{{NAMA_KOLOM}}` yang di-bind dari nilai record aktif. Contoh
(mengambil Customer Group dari IFS via DB link `@NEXUS`):

```sql
SELECT CF$_CUSTGROUP, CF$_CUSTGROUPDESC
FROM IFSAPP.CTM_CUST_GROUP_CLV@NEXUS
WHERE CF$_CUSTGROUP = (SELECT CF$_CUSTGROUPID FROM IFSAPP.CUST_GROUP_DETAIL_CLV@NEXUS
                       WHERE CF$_CUSTID = {{CUSTOMER_CODE}})
```

`{{CUSTOMER_CODE}}` otomatis diisi kode customer yang sedang aktif. LOV dependen
mengembalikan daftar kosong bila parameternya belum terisi.

**Simpan label ke kolom lain (companion):** `UI_FIELD.LOV_LABEL_COLUMN` menentukan
kolom yang menampung *label* pilihan. Pada contoh Customer Group, field `CUSTGROUP`
memakai LOV dan `LOV_LABEL_COLUMN = CUSTGROUPDESC`; saat sebuah group dipilih,
kode tersimpan ke `CUSTGROUP` dan deskripsinya ke `CUSTGROUPDESC` (field readonly).

> Catatan: DB link `@NEXUS` (IFS) hanya ada di environment produksi; pada demo
> lokal tanpa link, dropdown Customer Group tampil kosong (aman).

Cara membuat & memasang LOV (lewat Studio, tanpa SQL manual):
1. Studio → **DS_LOV** → Baru: isi `LOV_TYPE` (mis. STATIC) + `SOURCE_QUERY` (JSON) → Simpan.
2. Studio → **UI_FIELD** → Ubah field target: set **Lov Id** (dropdown DS_LOV) → Simpan.
3. Klik **⚡ Kompilasi & Aktifkan**.

Hasilnya: di form aplikasi, field itu menjadi dropdown berisi opsi LOV, dan di grid
nilainya ditampilkan sebagai **label** (bukan kode). Field yang punya LOV otomatis
dirender sebagai `select` tanpa perlu mengubah `FIELD_TYPE`.

### ODAF Studio — Data Manager (web)

Form generator berbasis web untuk mengelola isi **semua tabel** (metadata &
data bisnis) langsung dari browser — tidak perlu lagi insert manual via SQL/CLI.

- Buka **Studio** dari header aplikasi (atau `http://localhost:8080/studio`).
  Hanya untuk administrator (role `ADMIN`).
- Sidebar mengelompokkan tabel: Metadata / Runtime / Security / Audit / System /
  Business. Pilih tabel → grid (list/cari/paginasi) → **Baru/Ubah/Hapus**.
- Form & grid dibangkitkan otomatis dari **introspeksi tabel** (kolom, PK,
  unique, foreign key), tanpa metadata terkompilasi:
  - Widget dipilih dari tipe kolom (VARCHAR2→text/textarea, NUMBER→number,
    DATE→date, TIMESTAMP→datetime, CLOB→textarea).
  - Kolom **foreign key ditampilkan sebagai dropdown** berlabel (mis. pilih
    Application/Dataset/Page berdasarkan nama, bukan hex).
  - Primary key `RAW(16)` di-generate otomatis saat create; kolom audit/versi
    dikelola engine (disembunyikan dari form).
  - Mendukung PK komposit (mis. `SEC_ROLE_PERMISSION`, `SEC_USER_ROLE`).

Dengan ini, metadata (dataset, field, LOV, menu, workflow, notifikasi, dsb)
dapat dibuat & disunting via web.

**Input cerdas dari struktur tabel:**
- Kolom flag `NUMBER(1)` (mis. `VISIBLE_FLAG`, `ACTIVE_FLAG`) → dropdown **Ya/Tidak**.
- Kolom dengan CHECK constraint (mis. `SOURCE_TYPE`, `PAGE_TYPE`, `RULE_TYPE`) →
  dropdown daftar nilai yang diizinkan. Kolom `STATUS` → dropdown siklus hidup.
- Kolom foreign key (mis. `MODULE_ID`, `PAGE_ID`, `DATASET_ID`) → dropdown berlabel.
- `OBJECT_ID` (RAW 16) di-generate otomatis; kolom audit/versi disembunyikan.

**Kompilasi dari Studio:** karena runtime hanya membaca package terkompilasi,
perubahan metadata belum tampil di aplikasi sampai dikompilasi. Setelah menyimpan
baris metadata, Studio menampilkan pesan panduan, dan tombol **⚡ Kompilasi &
Aktifkan** di kanan atas mengompilasi ulang seluruh aplikasi seketika (tanpa CLI).

### Metadata Scaffolder (ODAF Studio)

Generator bergaya framework: mereverse-engineer **tabel Oracle yang sudah ada**
menjadi metadata ODAF lengkap (dataset + form + grid + menu + validasi), sehingga
CRUD langsung tersedia tanpa menulis SQL seed manual.

```bash
# Generate metadata dari tabel PRODUCT lalu kompilasi & aktifkan
docker compose exec app php artisan odaf:scaffold PRODUCT --app=ODAF_DEMO --label="Produk" --compile

# Timpa metadata yang sudah ada
docker compose exec app php artisan odaf:scaffold PRODUCT --force --compile
```

Yang di-generate dari struktur tabel:
- **Dataset** (`DS_<TABLE>`, sumber TABLE, PK & soft-delete terdeteksi otomatis).
- **Halaman form/grid** (`PAGE_<TABLE>`) + **field** per kolom, dengan pemetaan
  tipe (VARCHAR2→TEXT/EMAIL/TEXTAREA, NUMBER→INTEGER/DECIMAL/CHECKBOX,
  DATE/TIMESTAMP→DATE/DATETIME) dan label yang dirapikan.
- **Menu** (`MENU_<TABLE>`) di bawah modul (dibuat bila belum ada).
- **Validasi**: `REQUIRED` (kolom NOT NULL), `UNIQUE` (constraint unik satu
  kolom), `MAX_LENGTH` (panjang VARCHAR2).

Kolom sistem (`CREATED_*`, `UPDATED_*`, `DELETED_*`, `VERSION_NO`, `ACTIVE_FLAG`)
dan primary key dikecualikan dari form (dikelola engine). Metadata hasil generate
tetap dapat disunting lebih lanjut, lalu dikompilasi ulang.

## Pengujian

```bash
# Unit test (compiler, validation, identity) — tanpa Oracle:
docker compose exec app composer test

# Lint & analisis statis:
docker compose exec app composer lint
docker compose exec app composer analyse
```

## Catatan & Langkah Berikutnya

- **Autentikasi**: login berbasis session terhadap `SEC_USER` sudah aktif
  (role `ADMIN` = superuser). Remember-me belum didukung (tidak ada kolom token).
- **Fase F2** (lihat `../DEVELOPMENT-PLAN.md`): Workflow Engine **selesai**
  (approval state machine) dan Notification Engine **selesai** (in-app + log,
  terpicu event workflow). Berikutnya: Report (BB-11), Dashboard, Integration
  (BB-12), Deployment Manager (BB-13), Plugin (BB-14), serta perluasan workflow
  (guard/condition, parallel, timer, kompensasi) dan notifikasi (email/webhook,
  retry/escalation, agregasi, lokalisasi).

Detail lihat `../DEVELOPMENT-PLAN.md`.
