---
title: ODAF/MDAF - Rencana Pengembangan Aplikasi
document: DEVELOPMENT-PLAN
version: 1.0.0-draft
status: Draft
owner: Engineering Team
last_updated: 2026-07-07
---

# ODAF / MDAF — Rencana Pengembangan Aplikasi

> Dokumen ini menerjemahkan spesifikasi arsitektur (Volume 1–3) menjadi rencana
> implementasi engineering yang dapat dieksekusi. Bersifat operasional, bukan
> normatif. Jika terjadi konflik dengan Volume 1–3, spesifikasi arsitektur yang berlaku.

---

## 1. Ringkasan & Kondisi Saat Ini

**Tujuan produk:** membangun *Metadata-Driven Application Framework* — satu mesin CRUD generik yang menghasilkan aplikasi bisnis (menu, form, grid, validasi, workflow, security, laporan) sepenuhnya dari metadata di Oracle, tanpa menulis controller/model/view per entitas. Target akhir: platform ERP.

**Kondisi repositori saat ini:**

| Aset | Status |
|------|--------|
| Volume 1 — Software Architecture Document | Lengkap (spesifikasi) |
| Volume 2 — Oracle Metadata & Database Design | Lengkap (spesifikasi, 39 bab) |
| Volume 3 — ODAF Core Implementation | Lengkap (spesifikasi, 39 bab) |
| Volume 4 — ODAF Studio | Kosong |
| Volume 5 — Sample ERP | Kosong |
| Kode sumber | **Belum ada** |

Artinya: fondasi dokumentasi sangat kuat, tetapi implementasi dimulai dari nol. Rencana ini berfokus pada menghadirkan **runtime metadata-driven yang berfungsi** secepat mungkin, lalu memperluasnya menjadi platform.

**Stack teknologi (dari Volume 1):**

- Backend: PHP 8.4+ (Laravel — implikasi dari Blade/Livewire)
- Database: Oracle 21c / 23ai (sumber metadata + data bisnis)
- Frontend: Blade + Livewire + Alpine.js
- UI kit: AdminLTE atau Tabler
- Pola: Repository Pattern, Service Layer, Metadata Engine, Runtime Engine

---

## 2. Prinsip Eksekusi

1. **Vertical slice dulu, bukan horizontal.** Kejar satu alur end-to-end (metadata menu → form → simpan ke Oracle) sedini mungkin agar arsitektur terbukti nyata, baru diperluas.
2. **Compiler-driven sejak awal.** Runtime hanya mengeksekusi metadata terkompilasi. Jangan biarkan runtime membaca tabel metadata mentah — ini keputusan arsitektur inti (CORE-002) yang mahal untuk diubah belakangan.
3. **Satu engine generik.** Tidak ada `CustomerController`. Setiap penambahan modul = tambah tabel + metadata.
4. **Kontrak sebelum implementasi.** Definisikan interface tiap building block (BB-01..BB-15) sebelum mengisi implementasinya, supaya tim bisa paralel.
5. **Uji dengan modul nyata.** Setiap fase divalidasi dengan membangun modul sample (mis. Master Customer) murni lewat metadata — tanpa menyentuh kode engine.

---

## 3. Peta Fase (selaras Roadmap Vol.1 Bab 18)

| Fase | Nama | Fokus | Milestone |
|------|------|-------|-----------|
| **F0** | Setup & Fondasi Teknis | Scaffolding, koneksi Oracle, CI, skema metadata inti | M2 |
| **F1** | Core Runtime (MVP) | Compiler + Runtime Kernel + Dataset/Security/Validation/Render | M3, M4 |
| **F2** | Platform Lengkap | Workflow, Notification, Report, Dashboard, Integration, Deployment | — |
| **F3** | ODAF Studio (Volume 4) | Designer visual metadata, versioning, deploy manager | M5 |
| **F4** | Sample ERP (Volume 5) | Modul ERP referensi murni dari metadata | M6 |
| **F5** | Enterprise & Cloud | Clustering, cache terdistribusi, K8s, observability | M6–M7 |
| **F6** | Intelligent Platform | AI-assisted authoring | M8 |

Fokus rencana detail di bawah adalah **F0–F2** (fondasi yang menentukan keberhasilan seluruh platform). F3–F6 diuraikan pada level milestone.

---

## 4. Building Block → Urutan Build & Dependensi

Urutan dibuat mengikuti aturan dependensi Volume 1 Bab 10 (dependensi mengalir ke bawah):

```
BB-01 Metadata Repository        (F0)  ── tidak bergantung apa pun
   └─ BB-02 Metadata Compiler    (F1)  ── butuh BB-01
        └─ BB-03 Runtime Engine  (F1)  ── butuh BB-02
             ├─ BB-05 Dataset    (F1)
             ├─ BB-07 Security   (F1)
             ├─ BB-09 Validation (F1)
             ├─ BB-04 Renderer   (F1)
             ├─ BB-08 Audit      (F1)
             ├─ BB-06 Workflow   (F2)
             ├─ BB-10 Notification(F2)
             ├─ BB-11 Reporting  (F2)
             ├─ BB-12 Integration(F2)
             └─ BB-14 Plugin     (F2)
   BB-13 Deployment Manager      (F2)  ── butuh BB-02
   BB-15 ODAF Studio             (F3)  ── independen dari Runtime
```

---

## 5. Rencana Detail per Fase

### F0 — Setup & Fondasi Teknis  *(target M2: Metadata Repository operasional)*

Tujuan: kerangka proyek berjalan, terkoneksi Oracle, dan skema metadata inti sudah ada.

**Sprint 0.1 — Scaffolding & DevEx**
- Inisialisasi proyek Laravel (PHP 8.4), struktur direktori sesuai layer (Metadata / Compiler / Runtime / Engines / Support).
- Konfigurasi koneksi Oracle (driver `oci8` / `yajra/laravel-oci8`), environment `.env` (dev/test/prod).
- Setup CI (lint PHPStan/Psalm level tinggi, PHPCS PSR-12, PHPUnit/Pest).
- Docker Compose untuk Oracle XE/23ai lokal + app container.
- Konvensi Git, branching, dan template PR.

**Sprint 0.2 — Skema Metadata Inti (Volume 2)**
- Implementasikan DDL inti (Vol.2 bab 25 Core-DDL) untuk domain minimum: `APPLICATION`, `MODULE`, `MENU`, `PAGE/FORM`, `FIELD`, `DATASET`, `LOV`, `VALIDATION`, `SECURITY (USER/ROLE/PERMISSION)`.
- Terapkan Naming Standards (bab 06), Key Strategy (bab 08), Audit columns (bab 10), Versioning (bab 09) sebagai kolom standar semua tabel.
- Buat repository runtime terpisah dari repository design-time (isolasi CORE-001/002).
- Seed data minimal (bab 34): 1 aplikasi, 1 role admin, 1 user.

**Sprint 0.3 — Metadata Access Layer**
- Repository Pattern untuk baca/tulis metadata design-time.
- Value objects / DTO untuk tiap tipe metadata.
- Unit test terhadap CRUD metadata.

**Deliverable F0 / Kriteria selesai:**
- Skema Oracle ter-migrate & ter-seed.
- Bisa membaca definisi menu/form/field via layer repository dari kode.
- CI hijau.

---

### F1 — Core Runtime (MVP)  *(target M3 Compiler, M4 Runtime)*

Tujuan: **satu alur end-to-end berjalan** — buka menu → form ter-generate dari metadata → simpan/baca data bisnis ke Oracle lewat engine generik, dengan security & validasi.

**Sprint 1.1 — Metadata Compiler Frontend (BB-02, Vol.3 bab 03–05)**
- Parser + semantic analyzer metadata.
- Validasi: referensi tak valid, definisi tak lengkap, dependensi sirkular, inkonsistensi security.
- Bangun **Metadata IR (MIR)** sebagai representasi antara.

**Sprint 1.2 — Compiler Optimizer & Backend (Vol.3 bab 06–07)**
- Dependency analysis + optimasi.
- Backend generator → **Runtime Package** yang immutable, disimpan di Runtime Repository (Vol.2 bab 32).
- Determinisme: metadata sama → package sama (hash/checksum untuk verifikasi).

**Sprint 1.3 — Unified Runtime Kernel (BB-03, Vol.3 bab 02, 08–10)**
- Service Registry + Execution Context.
- Pipeline eksekusi: Resolve Context → Locate Package → Resolve Service → Execute Object Graph → Response → Audit.
- Runtime cache untuk package terkompilasi.

**Sprint 1.4 — Dataset Engine (BB-05, Vol.3 bab 11)**
- Mesin CRUD generik tunggal: create/read/update/delete, filter, sort, pagination, optimistic locking.
- Dukungan sumber: Table & View dulu (Custom SQL/SP/Package menyusul).
- Soft delete + audit kolom otomatis.

**Sprint 1.5 — Security Engine (BB-07, Vol.3 bab 18)**
- Autentikasi + session.
- RBAC: role, permission, menu/form/field/button permission, row-level (dasar).
- Authorization dievaluasi **sebelum** setiap operasi bisnis.

**Sprint 1.6 — Validation Engine (BB-09, Vol.3 bab 13 Rule Engine)**
- Validasi metadata-driven: required, min/max length, min/max value, regex, unique, FK, conditional.
- Dijalankan sebelum eksekusi dataset.

**Sprint 1.7 — UI Rendering Engine (BB-04, Vol.3 bab 14)**
- Form generator dinamis (Livewire + Blade): tipe field dasar (text, number, date, combo/LOV, checkbox, textarea).
- Grid/list generator: kolom, filter, paging, sort dari metadata.
- Menu generator dari metadata `MENU`.
- Layout dasar: single & two column (layout lanjutan menyusul).
- Integrasi tema (AdminLTE/Tabler) via Theme layer.

**Sprint 1.8 — Audit Engine + Integrasi End-to-End (BB-08)**
- Audit log data change (immutable).
- **Uji penerimaan MVP:** buat modul "Master Customer" HANYA dengan menambah tabel Oracle + baris metadata (menu/form/field/validasi/permission). Tidak ada kode engine baru. Harus muncul menu, form, grid, tersimpan ke Oracle, tervalidasi, terbatasi permission, dan teraudit.

**Deliverable F1 / Kriteria selesai:**
- Compiler menghasilkan runtime package deterministik.
- Runtime kernel mengeksekusi package (bukan metadata mentah).
- Modul CRUD baru dapat dibuat 100% via metadata.
- Test end-to-end untuk alur Customer lulus.

---

### F2 — Platform Lengkap

Menambah engine yang membuat platform layak enterprise.

- **Workflow Engine (BB-06, Vol.3 bab 12):** state, transition, approval, escalation, timer, history.
- **Notification Engine (BB-10, Vol.3 bab 15):** Email, SMS/WhatsApp, push, webhook; rule-based (mis. `IF stock < 10 THEN notify`).
- **Report Engine (BB-11, Vol.3 bab 17):** PDF/Excel/CSV, grouping, summary, drill-down, multi-bahasa dari metadata caption.
- **Dashboard Engine (Vol.3 terkait):** card, KPI, chart (pie/bar/line), gauge, pivot.
- **Integration Engine (BB-12, Vol.3 bab 16):** REST/SOAP/MQ/file/DB gateway sebagai sumber data & LOV.
- **Deployment Manager (BB-13, Vol.3 bab 19):** packaging, versioning, rollback, activation metadata antar environment.
- **Plugin Architecture (BB-14, Vol.3 bab 26):** extension point untuk field type, validator, renderer, dataset provider.
- **Cross-cutting:** Event Bus (bab 27), Scheduler (bab 28), Observability/Telemetry (bab 29–30).
- **Perluasan renderer:** tab, accordion, wizard, master-detail, nested detail, tipe field lanjutan (rich text, upload, signature, GPS, barcode/QR).

**Kriteria selesai:** sebuah modul dengan workflow approval + notifikasi + laporan dapat dibangun sepenuhnya via metadata.

---

### F3 — ODAF Studio (Volume 4)  *(target M5)*

Environment authoring visual agar metadata tak perlu ditulis manual di tabel.

- Metadata Designer (aplikasi/menu/form/field).
- Dataset Designer, Workflow Designer, Permission Designer.
- Metadata Version Manager + Deployment Manager UI.
- Preview runtime langsung dari Studio.

**Catatan:** Volume 4 masih kosong — perlu penulisan spesifikasi Studio lebih dulu (lihat §7).

---

### F4 — Sample ERP (Volume 5)  *(target M6)*

Bukti nyata platform: bangun modul ERP referensi (Master Data, Purchasing, Sales, Inventory, Finance) **murni via metadata/Studio**, tanpa kode engine baru. Berfungsi sebagai regression suite dan showcase.

**Catatan:** Volume 5 masih kosong — perlu penulisan spesifikasi modul ERP.

---

### F5 — Enterprise & Cloud  *(M6–M7)*

Clustering, distributed cache, API gateway, plugin marketplace, deployment Kubernetes, auto-scaling, centralized logging (Vol.3 bab 33–35).

### F6 — Intelligent Platform  *(M8)*

AI-assisted metadata authoring, auto form generation, workflow recommendation, SQL optimization (Vol.1 bab 18 Fase 5).

---

## 6. Milestone & Kriteria Penerimaan

| Milestone | Definisi Selesai (DoD) |
|-----------|------------------------|
| **M2** Metadata Repository | DDL inti ter-deploy, seed jalan, access layer teruji |
| **M3** Compiler | Metadata → runtime package deterministik + validasi error |
| **M4** Runtime Kernel | Modul CRUD baru 100% dari metadata, security & audit aktif |
| **M5** Studio | Modul dapat dibuat via UI tanpa edit SQL manual |
| **M6** Enterprise Deployment | Sample ERP jalan, deploy antar environment via package |
| **M7** Cloud | Berjalan di Kubernetes dengan auto-scaling |
| **M8** AI-Assisted | Fitur AI authoring aktif |

---

## 7. Prasyarat & Gap yang Harus Ditutup

1. **Volume 4 (Studio) & Volume 5 (Sample ERP) kosong.** Tulis spesifikasinya sebelum F3/F4, atau tetapkan bahwa desain akan lahir bersama implementasi.
2. **Pemilihan framework PHP eksplisit.** Blade/Livewire mengindikasikan Laravel — konfirmasi resmi agar scaffolding konsisten.
3. **Lisensi & edisi Oracle** untuk fitur (partitioning, AQ, scheduler) — pengaruh ke F2/F5.
4. **Strategi testing** (Vol.3 bab 32): tetapkan target coverage untuk engine (kritikal) vs metadata (fungsional).
5. **Definisi "definition of done"** per building block dan kontrak interface antar BB agar tim paralel.

---

## 8. Risiko Utama & Mitigasi

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| Runtime membaca metadata mentah (bukan compiled) | Melanggar CORE-002, sulit dibalik | Tegakkan isolasi sejak Sprint 1.2; review arsitektur |
| Scope creep (langsung kejar ERP) | Fondasi rapuh | Kunci vertical slice MVP dulu (F1) |
| Kompleksitas compiler di awal | Delay M3 | Mulai MIR minimal, iteratif; validasi dengan modul kecil |
| Ketergantungan Oracle-specific | Sulit uji lokal | Docker Oracle 23ai Free untuk dev/CI |
| Metadata model berubah besar | Migrasi mahal | Versioning + Deployment Manager sejak dini |

---

## 9. Rekomendasi Langkah Pertama (2–4 minggu ke depan)

1. Konfirmasi framework (Laravel) & inisialisasi repo kode (`F0 Sprint 0.1`).
2. Setup Docker Oracle 23ai Free + koneksi dari Laravel.
3. Implementasikan subset DDL metadata inti + seed (`F0 Sprint 0.2`).
4. Bangun metadata access layer + test (`F0 Sprint 0.3`).
5. Rancang kontrak interface BB-02 (Compiler) & BB-03 (Runtime) sebelum implementasi.

Target akhir 4 minggu: mampu membaca metadata menu/form dari Oracle lewat kode — pijakan untuk F1.

---

## 10. Ringkasan

Spesifikasi ODAF/MDAF sudah matang; tantangan berikutnya adalah eksekusi disiplin dari nol. Kunci sukses: **kejar satu alur metadata-driven end-to-end (F1) lebih dulu**, jaga isolasi compiler/runtime, dan validasi setiap fase dengan membangun modul nyata murni dari metadata. Setelah runtime inti terbukti, perluasan ke platform penuh, Studio, dan ERP menjadi penambahan metadata dan engine, bukan penulisan ulang.
