# Software Requirements Specification
## for Checklist Engineer Aqua

**Version 1.0**
**Prepared by AI Assistant (Antigravity)**
**Aqua Engineering Team**
**2026-08-13**

---

## Table of Contents
1. [Introduction](#1-introduction)
2. [Overall Description](#2-overall-description)
3. [External Interface Requirements](#3-external-interface-requirements)
4. [System Features](#4-system-features)
5. [Other Nonfunctional Requirements](#5-other-nonfunctional-requirements)
6. [Other Requirements](#6-other-requirements)
7. [Appendix A: Glossary](#appendix-a-glossary)
8. [Appendix B: Analysis Models](#appendix-b-analysis-models)
9. [Appendix C: To Be Determined List](#appendix-c-to-be-determined-list)

---

## 1. Introduction

### 1.1 Purpose
Dokumen ini mendefinisikan *Software Requirements Specification* (SRS) untuk sistem **Checklist Engineer Aqua**. Sistem ini adalah aplikasi berbasis web yang berfungsi sebagai *Dashboard Live* untuk sentralisasi pemantauan pengisian checklist harian/berkala dari tim engineering, yang disinkronkan secara *real-time* dengan form pengisian (Google Forms).

### 1.2 Document Conventions
- Prioritas dari setiap fitur utama dinilai dengan skala `High`, `Medium`, atau `Low`.
- Semua istilah teknis spesifik dicetak *miring* (italic) dan dijelaskan pada glosarium.
- Penamaan fungsi atau kode yang mengacu pada sistem (misalnya `GoogleFormsSyncService`) ditulis menggunakan `inline code`.

### 1.3 Intended Audience and Reading Suggestions
Dokumen ini ditujukan untuk:
- **Pengembang (Developers):** Memahami kebutuhan sistem, integrasi API, dan fitur-fitur teknis yang harus dibangun/dikelola.
- **Tim Engineering (Users):** Mengerti fungsionalitas *Dashboard Live* dan parameter validasi status (*Good/Not Good*).
- **Manajemen/Supervisor:** Mengetahui fitur monitoring dan rekapitulasi data.

Disarankan membaca dari bagian *Overall Description* terlebih dahulu untuk memahami gambaran besar sistem, dilanjutkan dengan *System Features*.

### 1.4 Product Scope
Sistem **Checklist Engineer Aqua** memecahkan masalah pemantauan pengisian form checklist engineering yang sebelumnya tersebar atau sulit dilacak. Dengan sistem ini, pengisian dilakukan melalui antarmuka pihak ketiga (Google Forms), namun datanya ditarik, diproses, divalidasi, dan direpresentasikan secara terpusat dalam *Dashboard Live*. Hal ini memudahkan supervisor untuk melihat persentase penyelesaian, lokasi kegagalan (kerusakan), serta melihat hierarki fasilitas secara terstruktur.

### 1.5 References
- *Dokumentasi API Google Forms.*
- *PANDUAN_PENGGUNAAN.md (Petunjuk Pengguna).*
- *AGENTS.md (Spesifikasi Internal Pengembangan).*

---

## 2. Overall Description

### 2.1 Product Perspective
Aplikasi ini adalah sistem *self-contained* yang menjadi perantara antara pengguna (melalui Google Forms) dan supervisor (melalui Dashboard Live). Sistem ini menggantikan pemantauan manual dengan menyediakan visualisasi matriks yang otomatis ditarik dari ekosistem Google.

### 2.2 Product Functions
- Sinkronisasi data submission secara *live* menggunakan Google Forms API.
- Visualisasi matriks checklist (menampilkan status Terisi/Kosong, *Good/Not Good*).
- Pemetaan dan *parsing* format submission yang kompleks (seperti form Ladder, Rolling Door).
- Panel konfigurasi rahasia (*Pandu Admin*) untuk manajemen token API.
- Ekspor data laporan matriks bulanan/harian ke format Excel.

### 2.3 User Classes and Characteristics
1. **Guest / Regular User (Tanpa Login):** Tim engineering atau pihak lain yang hanya membutuhkan akses ke halaman Live Dashboard untuk melihat status pengerjaan checklist.
2. **Pandu Admin (System Configurator):** Pengembang atau IT Support yang menangani konfigurasi API, token otentikasi Google, dan parameter *back-end* yang sensitif.

### 2.4 Operating Environment
- **Server:** Linux/Windows Server dengan PHP 8.2+ dan Laravel 11.
- **Database:** MySQL 8.x atau SQLite.
- **Client (Frontend):** *Web Browser* modern (Chrome, Firefox, Safari, Edge) yang mendukung JavaScript (untuk mekanisme *client-side polling*).
- **Dependencies:** Google API Client Library.

### 2.5 Design and Implementation Constraints
- Limitasi API Google Forms: Sistem harus menggunakan teknik *polling/chunking* agar tidak terkena pemblokiran limit API (Rate Limiting & Timeout) saat memuat ratusan form secara bersamaan.
- *Response Time*: Halaman *live* tidak boleh menunggu eksekusi server yang terlalu lama. Sinkronisasi harus dilakukan *asynchronously*.
- Validasi data harus fleksibel karena struktur pengisian Google Forms bisa berubah.

### 2.6 User Documentation
- *PANDUAN_PENGGUNAAN.md* (Bisa diekspor ke Microsoft Word/PDF).
- Dokumentasi internal *AGENTS.md*.

### 2.7 Assumptions and Dependencies
- Responden mengisi data selalu melalui URL Google Forms yang sudah dipetakan di dalam sistem.
- Google Forms API akan selalu beroperasi secara stabil (tidak ada *downtime* major dari pihak Google).

---

## 3. External Interface Requirements

### 3.1 User Interfaces
- **Live Dashboard:** Berupa tabel matriks responsif dengan indikator warna (Hijau = Good, Merah = Not Good, Abu-abu = Kosong).
- **Pandu Admin Login:** Formulir login sederhana tanpa *branding* publik untuk menjaga keamanan.

### 3.2 Hardware Interfaces
Tidak ada interaksi dengan perangkat keras (*hardware*) secara langsung.

### 3.3 Software Interfaces
- **Sistem Database Lokal:** MySQL/SQLite untuk menyimpan *cache* dan *Master Data*.
- **Otentikasi:** Middleware kustom untuk otentikasi rahasia (Pandu Admin).

### 3.4 Communications Interfaces
- **Protokol Eksternal:** HTTPS digunakan untuk komunikasi dengan *Google Forms REST API*.
- **Protokol Internal:** Interaksi antara browser pengguna dengan server menggunakan HTTP/HTTPS melalui metode `fetch()` (*polling/chunking*) JavaScript.

---

## 4. System Features

### 4.1 Live Matrix Dashboard Synchronization
#### 4.1.1 Description and Priority
**Priority: High.** Fitur utama untuk menampilkan matriks status checklist berdasarkan data *submissions*.

#### 4.1.2 Stimulus/Response Sequences
- *User* membuka halaman utama `/`.
- Halaman memuat struktur *skeleton* matriks.
- *Browser* mengirimkan perintah *polling/chunking* (`/poll/init`, `/poll/chunk`, `/poll/finish`).
- Sistem memberikan respons status pembaruan dan merender indikator warna pada setiap sel kalender.

#### 4.1.3 Functional Requirements
- **REQ-1:** Sistem harus bisa melakukan inisialisasi sinkronisasi tanpa *blocking/timeout*.
- **REQ-2:** Sistem harus mampu mengevaluasi *latest submission* pada bulan tersebut menjadi status: *Good*, *Not Good*, atau *Unfilled*.
- **REQ-3:** Sistem harus men-cache data menggunakan `global_live_submissions` untuk meminimalkan beban pemanggilan API.

### 4.2 Dynamic Form Parsing
#### 4.2.1 Description and Priority
**Priority: High.** Kemampuan sistem untuk membaca form dengan format pertanyaan rumit (seperti *Fix Ladder*, *Rolling Door*, *Sliding Door*).

#### 4.2.2 Stimulus/Response Sequences
- Data submission baru diterima dari sinkronisasi Google.
- *Trait MatrixSummary* menguraikan jawaban (*answers*) dengan mencocokkannya dengan daftar opsi standar (*standard options*).

#### 4.2.3 Functional Requirements
- **REQ-4:** Sistem harus mendeteksi keberadaan *tag* `[IMPORTED FAILURES]` pada *notes*.
- **REQ-5:** Jika `[IMPORTED FAILURES]` tidak ada, sistem akan mendeteksi manual mana *option* yang tidak terdapat di dalam set jawaban *answers* dan menilainya sebagai `non_standard`.
- **REQ-6:** Sistem memvalidasi apakah form bertindak sebagai sebuah *Leaf* (daun tanpa turunan form di bawahnya).

### 4.3 Pandu Admin (Hidden Configuration)
#### 4.3.1 Description and Priority
**Priority: Medium.** Akses khusus tersembunyi untuk *developer* atau *IT Support* mengubah parameter API Google.

#### 4.3.2 Stimulus/Response Sequences
- Pengguna membuka `/pandu-admin/login`.
- Memasukkan kredensial rahasia (yang di-set di lingkungan server/`.env`).
- Mengatur Token, Sheet ID, atau *cache flushing*.

#### 4.3.3 Functional Requirements
- **REQ-7:** Rute ini harus diproteksi middleware khusus `pandu.auth`.
- **REQ-8:** Panel ini tidak boleh terhubung atau terdaftar di database `users` umum untuk mencegah eksploitasi otentikasi.

---

## 5. Other Nonfunctional Requirements

### 5.1 Performance Requirements
- Proses sinkronisasi *Live Matrix* (dari *Init* ke *Finish*) tidak boleh melebihi rata-rata 30 detik untuk 100+ form, yang difasilitasi oleh proses *chunking*.
- Halaman *live* awal harus termuat (TTFB) dalam waktu kurang dari 2 detik (menggunakan *cache* prioritas).

### 5.2 Safety Requirements
Sistem tidak memodifikasi data asli di Google Forms (bersifat *Read-Only* dari perspektif sumber daya Google).

### 5.3 Security Requirements
- URL *Google Service Account JSON/Token* tidak boleh diunggah ke *repository* (harus dijaga di server *environment*).

### 5.4 Software Quality Attributes
- **Adaptability:** Penambahan jenis form baru (misalnya *Fire Extinguisher*) harus secara otomatis dapat di-*parse* oleh sistem tanpa perombakan kode.
- **Maintainability:** Komponen UI dipisah (Livewire/Blade) dari kontrol logika *backend*.

### 5.5 Business Rules
- Status kelayakan (*Good*) hanya diberikan jika seluruh parameter spesifikasi (standar) dari sebuah fasilitas terpenuhi 100%.

---

## 6. Other Requirements
Tidak ada spesifikasi tambahan yang diperlukan saat ini.

---

## Appendix A: Glossary
- **Leaf (Daun):** Form tingkat paling akhir dalam sebuah hierarki yang tidak memiliki form sub-kategori (*child*) di bawahnya.
- **Chunking/Polling:** Teknik pengambilan data dalam potongan kecil (chunk) secara berulang-ulang melalui permintaan AJAX di klien.
- **Passkeys:** Otentikasi keamanan tanpa sandi (*passwordless*) menggunakan autentikator perangkat keras/biometrik.
- **Matrix Summary:** Tabel perangkum harian atau bulanan yang divisualisasikan menggunakan kode warna (Hijau/Merah/Kosong).

---

## Appendix B: Analysis Models
- TBD (To Be Determined) - *Class diagram* / ERD tersedia di konfigurasi *environment* pengembangan atau referensi *migration* database.

---

## Appendix C: To Be Determined List
1. Rencana untuk menyimpan riwayat historis laporan tahunan secara arsip (*Archive Logging*).
2. Mekanisme integrasi *push webhook* (notifikasi *real-time*) tanpa perlu *polling* manual jika Google Forms mendukung fitur *Trigger script*.
