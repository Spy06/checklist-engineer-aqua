# AGENTS.md — PANDU

> Dokumentasi teknis internal untuk AI Agent agar dapat melakukan debugging, implementasi fitur, dan pemeliharaan dengan efisien di proyek PANDU (Platform Analitik dan Digitalisasi Checklist Plant Cianjur).

---

## 1. Deskripsi Proyek

**PANDU (Platform Analitik dan Digitalisasi Checklist Plant Cianjur)** adalah aplikasi dashboard analitik berbasis web untuk memantau pengisian matriks checklist dari berbagai fasilitas engineering (berbasis Google Forms) di Danone Aqua Plant Cianjur. 

Aplikasi ini menyinkronkan data secara real-time, mem-parsing format submission yang kompleks, dan menyajikan visualisasi analitik yang interaktif dengan didukung oleh sistem caching lokal berkinerja tinggi.

### Stack Teknologi
| Layer | Teknologi |
|-------|-----------|
| Backend Framework | Laravel 11 (PHP 8.2+) |
| Frontend Interaktif | Alpine.js, Blade, Vanilla JS (AJAX Fetch API) |
| Konfigurasi Admin | Custom Blade + Controller (`PanduAdmin`) |
| Styling | Tailwind CSS |
| Database Default | MySQL (Production) / SQLite (Development) |
| Cache Driver | File Cache Driver (`CACHE_STORE=file` untuk stabilitas data besar) |
| Sinkronisasi Data | Google Forms API + Google Client SDK |

---

## 2. Peta Fitur & Arsitektur Utama

### 2.1. Dasbor Analitik Utama (`/`)
Halaman publik yang menyajikan data secara interaktif:
* **Filter Tanggal:** Input rentang waktu (tanggal mulai s.d. tanggal selesai) untuk membatasi visualisasi matriks secara real-time.
* **Live Mode:** Sakelar (toggle) untuk mengaktifkan pemantauan otomatis langsung dari Google Forms API.
* **Auto Refresh:** Opsi refresh otomatis secara periodik sesuai interval waktu yang ditentukan (dalam detik).
* **Scrape Data (Ambil Data Live):** Tombol yang memicu penarikan data secara asynchronous menggunakan teknik *chunked polling* untuk mencegah timeout API Google:
  1. `/guest/matrix/live/poll/init` -> Menginisialisasi daftar form.
  2. `/guest/matrix/live/poll/chunk` -> Mengunduh data Google Form per slug secara terpisah ke cache sementara.
  3. `/guest/matrix/live/poll/finish` -> Menggabungkan seluruh data sementara, memotong data usang, menghitung status matriks, dan menyimpan ke cache global.
* **Visualisasi Diagram Donat:** Representasi persentase kondisi checklist:
  * **Filled:** Persentase form yang sudah terisi.
  * **Unfilled:** Persentase form yang belum terisi.
  * **Good:** Persentase pengisian yang bernilai baik (tanpa indikasi kerusakan).
  * **Not Good:** Persentase pengisian yang memiliki catatan kegagalan/kerusakan.
* **Matriks Checklist Bulanan:** Tabel komprehensif bulanan (Jan s.d. Des) per form yang menunjukkan status pengisian menggunakan indikator visual 3-state:
  * 🔴 **None (Merah/Kosong):** Belum ada pengisian sama sekali pada bulan tersebut.
  * 🟡 **Partial (Kuning/Setengah):** Pengisian baru dilakukan pada sebagian objek (untuk kategori yang memiliki beberapa anak).
  * 🟢 **Full (Hijau/Penuh):** Pengisian telah selesai sepenuhnya untuk seluruh objek.
* **Export Excel:** Fitur untuk mengunduh laporan matriks ke dalam format spreadsheet Excel sesuai rentang tanggal yang dipilih.

### 2.2. Panel Admin Tersembunyi (Pandu Admin — `/pandu-admin`)
Panel khusus dengan otentikasi middleware `pandu.auth` untuk mengelola sistem:
* **Google API Credentials:** Form input untuk menyimpan JSON Key Service Account Google Cloud (Base64 encoded) langsung ke dalam konfigurasi lokal.
* **Form Registry CRUD:** Pemetaan data Google Form yang fleksibel:
  * Input Google Form ID & Slug Database.
  * Input Nama Tampilan kustom di halaman matriks.
  * Dropdown penentuan Form Induk (Parent Form) untuk menyusun struktur hierarki di matriks.
* **Fitur Preview Form Baru (Sebelum Publikasi):**
  * Halaman khusus (`/pandu-admin/form/preview?slug=xxx`) yang menampilkan struktur elemen pertanyaan Google Form API secara langsung (pilihan ganda, checklist, section).
  * Menampilkan 20 respons terbaru dari Google Form untuk memastikan data berhasil ditarik.
  * Menampilkan visualisasi struktur hierarki database (Parent → Child → Grandchild).
  * Tombol **"Sinkronkan Sekarang"** untuk menjalankan dry-run pembentukan tabel database.
* **Finalisasi Form:** Tombol persetujuan akhir yang memindahkan data konfigurasi sementara ke dalam database aktif agar langsung tampil secara live di halaman utama `/`.

---

## 3. Struktur Routing (`routes/web.php`)

| Method | URI | Controller/Action | Keterangan |
|--------|-----|-------------------|------------|
| GET | `/` / `/guest/matrix/live` | `GuestMatrixController@live` | Halaman utama dasbor matriks |
| GET | `/guest/matrix/live/poll/init` | `GuestMatrixController@livePollInit` | Inisialisasi daftar ambil data |
| POST | `/guest/matrix/live/poll/chunk` | `GuestMatrixController@livePollChunk` | Penarikan data parsial per form |
| POST | `/guest/matrix/live/poll/finish` | `GuestMatrixController@livePollFinish` | Finalisasi & kalkulasi akhir data live |
| GET | `/matrix/submission-detail` | `GuestMatrixController@submissionDetail` | Modal detail pengisian objek |
| GET | `/guest/matrix/export` | `GuestMatrixController@exportExcel` | Unduh matriks ke format Excel |
| GET | `/guest/matrix/view` | `GuestMatrixController@viewByDateRange` | AJAX filter tanggal matriks |
| GET | `/pandu-admin/login` | `PanduAdminController@showLogin` | Halaman masuk panel admin |
| POST | `/pandu-admin/login` | `PanduAdminController@processLogin` | Autentikasi sesi admin |
| GET | `/pandu-admin` | `PanduAdminController@index` | Dashboard kelola form & kredensial |
| POST | `/pandu-admin/config` | `PanduAdminController@updateConfig` | Update data config JSON |
| GET | `/pandu-admin/form/preview` | `PanduAdminController@previewForm` | Halaman peninjau struktur & data form |
| POST | `/pandu-admin/form/sync` | `PanduAdminController@syncNewForm` | AJAX trigger sinkronisasi DB |
| POST | `/pandu-admin/form/finalize` | `PanduAdminController@finalizeForm` | Publikasi form baru ke matriks utama |

---

## 4. Skema Caching & Database

### Caching Driver (Sangat Penting)
Aplikasi PANDU menyimpan data ter-scrape di dalam cache global menggunakan key `global_live_submissions`. 
* **Driver yang digunakan:** `file` (dikonfigurasi via `CACHE_STORE=file` di berkas `.env`). 
* **Mengapa tidak menggunakan `database` cache?** Data akumulasi Google Forms berukuran sangat besar (Megabyte). Query penyimpanan MySQL memicu error `MySQL server has gone away` karena limitasi `max_allowed_packet`. Menggunakan driver `file` menyelesaikan masalah ini dengan menulis data langsung ke disk lokal (`storage/framework/cache/data`).

### Database Modul (`forms` table)
Tabel utama untuk memetakan hirarki checklist:
* `id`: ID unik.
* `parent_id`: ID form induk (null jika root).
* `slug`: Kode unik pengenal.
* `title`: Nama tampilan matriks.
* `is_category`: Boolean penentu kategori/daun.
* `options`: Struktur array pertanyaan checklist yang valid.

---

## 5. Komponen Kritis & Catatan Pemeliharaan

### 5.1. Filter ID Daun saat Penghapusan Cache Lama
Di dalam `livePollFinish`, pastikan pencarian daun (`fetchedLeafIds`) menyertakan kondisi khusus jika form tersebut adalah form mandiri (tanpa anak):
```php
foreach ($fetchedRootForms as $rf) {
    if ($rf->children->count() === 0) {
        $fetchedLeafIds[] = $rf->id;
    } else {
        $fetchedLeafIds = array_merge($fetchedLeafIds, $this->getLeafDescendantIds($rf));
    }
}
```
*Gagal menyertakan logika ini akan mengakibatkan duplikasi data bertumpuk di cache karena data lama tidak terbuang sebelum data baru di-merge.*

### 5.2. File `pandu_config.json`
Berada di `storage/app/pandu_config.json`, berkas ini menjadi pusat penyimpanan daftar link Google Forms API yang terhubung sebelum di-sync ke database. Jangan biarkan berkas ini terhapus secara sengaja.

---

## 6. Komando Penting

| Perintah | Fungsi |
|----------|--------|
| `php artisan serve` | Menjalankan server lokal |
| `php artisan config:clear` | Membersihkan cache konfigurasi (setelah edit `.env`) |
| `php artisan cache:clear` | Membersihkan seluruh data cache aplikasi |
| `php artisan forms:sync-structure` | Melakukan sinkronisasi struktur form secara manual |
| `php artisan forms:sync-structure --slug=xxx --id=yyy` | Sinkronisasi spesifik untuk form tunggal (digunakan oleh AJAX admin) |
