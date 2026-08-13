# AGENTS.md — Checklist Engineer Aqua

> Dokumentasi teknis internal untuk AI Agent agar dapat melakukan debugging, feature implementation, dan maintenance dengan efisien di proyek Checklist Engineer Aqua.

---

## 1. Deskripsi Proyek

**Checklist Engineer Aqua** adalah aplikasi berbasis web yang berfungsi sebagai Dashboard Live untuk menampilkan matriks checklist dari berbagai fasilitas engineering (berbasis Google Forms). Aplikasi menyinkronkan data secara real-time, mem-parsing format submission yang kompleks, dan menampilkannya dalam UI matriks yang efisien menggunakan sistem caching.

### Tujuan Utama
- Sentralisasi monitoring pengisian form checklist harian/berkala tim engineering.
- Real-time dashboard view (Live Matrix) dengan indikator visual.
- Sinkronisasi data via Google Forms API (`GoogleFormsSyncService`).
- Parsing dinamis untuk berbagai tipe form (termasuk form kompleks seperti Ladder, Rolling Door, Sliding Door, dsb).
- Custom hidden admin panel (`PanduAdmin`) untuk manajemen konfigurasi.
- Admin Panel Filament untuk manajemen master data (Forms, Submissions, dll).

### Stack Teknologi
| Layer | Teknologi |
|-------|-----------|
| Backend Framework | Laravel 11 (PHP 8.2+) |
| Frontend Interaktif | Blade, Vanilla JS untuk Live Polling (Chunking) |
| Admin Panel | Filament v3 |
| Hidden Config Panel| Custom Blade + Controller (`PanduAdmin`) |
| Styling | Tailwind CSS |
| Database Default | MySQL / SQLite |
| Sinkronisasi Data | Google Forms API + Google Client |

---

## 2. Fitur yang Sudah Tersedia

### 2.1. Sinkronisasi Data (Google Forms API)
- Menggunakan `GoogleFormsSyncService` untuk mengambil submission form dari Google.
- Terdapat mekanisme *chunking* atau *polling* pada sisi client (di halaman `/guest/matrix/live`) untuk menghindari limit API (Timeout) jika form sangat banyak. Mekanismenya menggunakan rute `/poll/init`, `/poll/chunk`, `/poll/finish`.

### 2.2. Parsing Dinamis (Form Inspeksi)
- Aplikasi menangani form dengan format submission yang cukup variatif, beberapa form memiliki struktur dinamis seperti:
  - Form Ladder (Fix/Portable)
  - Form Rolling Door
  - Form Pintu Geser / Sliding Door
- Mekanisme parsing data Google Forms diekstrak dan dibaca sesuai dengan *mapping* / *template* yang telah dibuat.

### 2.3. Dashboard Matrix Live
- Halaman utamanya `/` (Redirect ke `/guest/matrix/live`).
- Menyediakan cache global (contoh cache dengan TTL tertentu) untuk menampilkan status submission di semua kalender (Hijau/Merah/Kosong).
- Mendukung export data ke Excel.

### 2.4. Pandu Admin (Hidden Panel)
- Panel konfigurasi rahasia untuk mengatur sinkronisasi dan token (`/pandu-admin`).
- Menggunakan middleware kustom `pandu.auth`.

### 2.5. Filament Admin Panel
- Mengelola data hirarki form dan log sistem.

---

## 3. Struktur Routing

### 3.1. Web Routes (`routes/web.php`)

| Method | URI | Controller/Action |
|--------|-----|-------------------|
| GET | `/` dan `/guest/matrix/live` | `GuestMatrixController@live` |
| GET | `/guest/matrix/live/poll/init` | `GuestMatrixController@livePollInit` |
| POST | `/guest/matrix/live/poll/chunk` | `GuestMatrixController@livePollChunk` |
| POST | `/guest/matrix/live/poll/finish` | `GuestMatrixController@livePollFinish` |
| GET | `/matrix/submission-detail` | `GuestMatrixController@submissionDetail` |
| GET | `/guest/matrix/export` | `GuestMatrixController@exportExcel` |
| GET | `/guest/matrix/view` | `GuestMatrixController@viewByDateRange` |
| GET | `/pandu-admin/login` | `PanduAdminController@showLogin` |
| POST | `/pandu-admin/login` | `PanduAdminController@processLogin` |
| GET | `/pandu-admin` | `PanduAdminController@index` |

---

## 4. Skema Database (Penting)

Tabel masih berbasis `forms`, `form_submissions`, dan konfigurasi aplikasi (`settings` atau custom json). Data submission disinkronisasi dari Google Forms (seringnya tidak langsung ditulis via web).

---

## 5. Struktur Direktori Penting

```
checklist-engineer-aqua/
├── app/
│   ├── Concerns/
│   │   └── MatrixSummary.php
│   ├── Filament/
│   ├── Http/
│   │   ├── Controllers/        # GuestMatrixController, PanduAdminController
│   ├── Models/                 # Form, FormSubmission
│   └── Services/
│       └── GoogleFormsSyncService.php
├── resources/
│   └── views/
├── routes/
│   ├── web.php
└── .agents/
    └── AGENTS.md
```

---

## 6. Komponen Kritis & Catatan Teknis

### 6.1. Google Forms API Polling
- Ketika memuat halaman live, aplikasi tidak melakukan fetching 100+ forms secara bersamaan. Ia menggunakan proses polling (init -> chunk looping via fetch JS -> finish) lalu me-reload cache.
- Jika ada masalah "data tidak sinkron", cek mekanisme fetch chunking ini di blade `live.blade.php`.

### 6.2. Parsing Form Dinamis
- Logika parsing untuk jenis inspeksi khusus (seperti Fix Ladder, Rolling Door, dll) cukup kompleks dan bervariasi pada Google Forms JSON API. Selalu periksa data raw (menggunakan scratch script atau dd) sebelum membuat asumsi keys (pertanyaan).

### 6.3. Pandu Admin Access
- Akun atau password untuk masuk ke `/pandu-admin` mungkin di-set secara statis atau dari file `.env`. Pastikan tidak membocorkan password ini.

---

## 7. Tracking Progress
| Fitur | Status |
|-------|--------|
| Google Forms Integration | ✅ Done |
| Live Matrix Dashboard | ✅ Done |
| Async Polling Fetch (Chunk) | ✅ Done |
| Parsing Form (Ladder, dll) | ✅ Done / In Progress |
| Pandu Admin Auth | ✅ Done |

---

## 8. Konvensi & Protokol AI Agent

### 8.1. Prinsip Debugging
1. **Evidence First**: Jangan tebak struktur respons JSON dari Google Forms. Selalu buat scratch file PHP untuk inspeksi (seperti yang ada di `scratch/inspect_...php`).
2. **Caching**: Ingat bahwa hasil sinkronisasi sering kali di-cache (contoh `global_live_submissions`). Gunakan `Cache::forget()` saat testing agar data up-to-date.
3. **Chunking**: Hati-hati dengan timeout. Biarkan flow chunking berjalan sebagaimana mestinya. Jangan ubah ke fetching sinkron untuk semua form.

### 8.2. Komando Penting
| Perintah | Fungsi |
|----------|--------|
| `php artisan serve` | Menjalankan server development |
| `php artisan cache:clear` | Menghapus cache (Penting saat testing sinkronisasi) |
