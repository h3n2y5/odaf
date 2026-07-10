Project Vision

Tujuan proyek ini bukan membuat aplikasi CRUD biasa, melainkan membangun sebuah Metadata Driven Application Framework (MDAF) atau Enterprise Low-Code Platform yang mampu menghasilkan aplikasi bisnis secara dinamis hanya berdasarkan metadata yang disimpan di Oracle Database.

Target akhirnya adalah membuat sebuah ERP Application Platform, bukan sekadar aplikasi.

Tujuan Utama

Semua modul aplikasi dibangun tanpa membuat kode CRUD baru.

Untuk menambahkan modul baru, developer hanya perlu:

Membuat tabel Oracle
Mengisi metadata
Framework otomatis menghasilkan aplikasi

Tidak perlu membuat:

Controller
Model
View
Form
Grid
Validation
Menu
Permission

Semuanya dibuat otomatis oleh framework.

Filosofi Framework

Framework hanya memiliki satu mesin CRUD generik.

Sebagai contoh, tidak ada lagi file seperti:

CustomerController.php
SupplierController.php
BarangController.php

melainkan hanya ada:

CrudController
CrudService
CrudRepository
CrudEngine

Semua entity diproses oleh engine yang sama berdasarkan metadata.

Framework tidak mengenal Customer atau Supplier secara khusus.

Framework hanya mengenal:

metadata form
metadata field
metadata table
metadata workflow
metadata security

Arsitektur Tingkat Tinggi
Oracle Database
│
├── Metadata
│     Menu
│     Form
│     Field
│     Validation
│     LOV
│     Workflow
│     Security
│     Dashboard
│     Report
│     Theme
│
├── Business Data
│     Customer
│     Supplier
│     Inventory
│     Purchasing
│     Sales
│     Finance
│     dll
│
PHP Framework
│
├── Metadata Engine
├── CRUD Engine
├── Form Generator
├── Grid Generator
├── Menu Generator
├── Validation Engine
├── Workflow Engine
├── Security Engine
├── Report Engine
├── Dashboard Engine
├── Notification Engine
└── Runtime Engine
Cara Kerja Framework

Semua perilaku aplikasi berasal dari metadata.

Misalnya developer ingin membuat menu:

Master

   Customer

Developer tidak membuat kode.

Cukup menambahkan data pada tabel metadata:

MENU

Framework akan membaca metadata tersebut dan langsung menghasilkan menu.

Saat menu dibuka, framework membaca metadata FORM untuk mengetahui:

tabel yang digunakan
judul halaman
layout
workflow
hak akses

Kemudian framework membaca metadata FIELD untuk menghasilkan form secara otomatis.

Dynamic Form Generation

Seluruh komponen form dibangun dari metadata.

Contoh tipe field yang didukung:

Text
Number
Decimal
Currency
Date
DateTime
Time
TextArea
Rich Text Editor
Password
Email
Phone
URL
Combo Box
Radio Button
Check Box
Multi Select
File Upload
Image Upload
QR Code
Barcode
Signature Pad
GPS Location
Map Picker
Color Picker
dan tipe lain yang dapat ditambahkan melalui plugin.
Dynamic Validation

Seluruh aturan validasi berasal dari metadata.

Contoh:

Required
Minimum Length
Maximum Length
Minimum Value
Maximum Value
Regular Expression
Email
Phone
Unique
Foreign Key
Formula
Conditional Validation

Tidak ada validasi yang ditulis di PHP untuk setiap form.

Dynamic Layout

Layout form juga dikendalikan metadata.

Framework harus mampu menghasilkan:

Single Column
Two Column
Three Column
Responsive Layout
Tab
Accordion
Panel
Wizard
Step Form
Master Detail
Nested Detail

tanpa perubahan kode.

Dynamic LOV

List of Values berasal dari metadata.

LOV dapat berupa:

Static List
SQL Query
Oracle View
Stored Procedure
REST API

Framework otomatis merender menjadi:

Combo Box
Auto Complete
Search Dialog
Popup LOV
Dynamic Security

Hak akses juga berasal dari metadata.

Framework harus mendukung:

Role
Permission
Menu Permission
Form Permission
Field Permission
Button Permission
Object Permission
Row Level Security

Misalnya:

Admin dapat melihat field Salary.

Staff tidak dapat melihat field tersebut.

Framework otomatis menyembunyikan objek tanpa perlu menambahkan kode.

Dynamic Workflow

Workflow tidak ditulis di program.

Workflow berasal dari metadata.

Contoh:

Draft

↓

Submitted

↓

Approved

↓

Closed

Framework membaca:

state
transition
approval
action
notification

kemudian menjalankan workflow secara otomatis.

Dynamic CRUD

Framework mendukung berbagai sumber data:

Table
View
Materialized View
Custom SQL
Stored Procedure
Package Oracle

Semua tetap menggunakan engine CRUD yang sama.

Audit dan Soft Delete

Semua tabel mendukung secara otomatis:

Created By
Created Date
Updated By
Updated Date
Deleted By
Deleted Date

Soft Delete maupun Hard Delete dikendalikan metadata.

Dashboard Engine

Dashboard juga dibangun dari metadata.

Komponen yang didukung:

Card
KPI
Pie Chart
Bar Chart
Line Chart
Gauge
Table
Pivot
Notification Engine

Notifikasi berasal dari metadata.

Contoh:

IF STOCK < 10

THEN

Send Email
Send WhatsApp
Create Notification

Framework menjalankan rule tersebut tanpa kode tambahan.

Report Engine

Report juga dihasilkan dari metadata.

Mendukung:

PDF
Excel
CSV
Print
Pivot Report
Grouping
Summary
Drill Down
Multi Language

Seluruh caption disimpan dalam metadata.

Framework hanya membaca bahasa aktif pengguna.

Contoh:

Indonesia

Nama Pelanggan

English

Customer Name

Tidak ada hardcode teks di source code.

Theme Engine

Framework memisahkan logika dengan tampilan.

UI dapat diganti menggunakan tema berbeda tanpa mengubah engine.

Contoh:

AdminLTE
Tabler
Bootstrap
Tailwind
Tema perusahaan
Teknologi yang Digunakan

Framework dibangun menggunakan teknologi modern dengan fokus pada stabilitas jangka panjang.

Backend

PHP 8.4+

Database

Oracle Database 21c / 23ai

Frontend

Blade
Livewire
Alpine.js

UI

AdminLTE atau Tabler

Arsitektur

Repository Pattern
Service Layer
Metadata Engine
Runtime Engine

Prinsip Desain

Framework harus memenuhi prinsip-prinsip berikut:

Metadata-first: seluruh perilaku aplikasi berasal dari metadata.
Configuration over Coding: konfigurasi lebih diutamakan daripada penulisan kode.
Engine-based: semua modul dijalankan oleh engine generik yang sama.
Extensible: mudah diperluas melalui plugin dan metadata baru.
Enterprise-grade: mampu mendukung aplikasi berskala ERP dengan performa, keamanan, audit, dan workflow yang kuat.
Maintainable: penambahan fitur baru dilakukan dengan memperluas engine dan metadata, bukan menggandakan kode.
Oracle-centric: Oracle menjadi sumber utama metadata, data bisnis, workflow, keamanan, dan konfigurasi sistem.
Visi Akhir

Target akhir proyek ini adalah membangun Enterprise Metadata Driven Application Framework (MDAF) yang menjadikan Oracle sebagai single source of truth untuk konfigurasi aplikasi. Framework ini mampu menghasilkan aplikasi bisnis secara dinamis—mulai dari menu, form, grid, validasi, keamanan, workflow, dashboard, notifikasi, hingga laporan—tanpa perlu membuat kode CRUD baru untuk setiap modul. Dengan pendekatan ini, pengembangan ERP baru menjadi jauh lebih cepat, konsisten, mudah dipelihara, dan dapat berkembang selama bertahun-tahun sebagai platform inti organisasi.
