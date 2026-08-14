# Low Level Design (LLD)
## PANDU (Platform Analisis dan Digitalisasi Checklist Plant Cianjur)

**Versi:** 1.0  
**Tanggal:** 13 Agustus 2026

---

## 1. Pendahuluan

### 1.1 Tujuan Dokumen
Dokumen Low Level Design (LLD) ini menguraikan arsitektur internal, skema basis data, dan logika komponen perangkat lunak tingkat rendah untuk sistem PANDU. Dokumen ini digunakan sebagai panduan teknis bagi tim pengembang dalam memahami detail implementasi kelas, fungsi, dan interaksi basis data.

### 1.2 Ruang Lingkup Sistem
PANDU dikembangkan menggunakan ekosistem web modern berbasis Laravel, Blade, TailwindCSS, dan Vanilla JavaScript. Fungsionalitas utamanya meliputi sinkronisasi data dari Google Forms API menggunakan metode *chunking polling*, kalkulasi dan visualisasi matriks inspeksi secara *real-time*, serta manajemen konfigurasi rahasia melalui modul *Pandu Admin*. Seluruh fitur autentikasi publik/Filament telah dihapus untuk mengutamakan efisiensi *dashboard* ringan berorientasi publik (Guest).

---

## 2. Arsitektur Sistem

Sistem ini mengadopsi pola arsitektur *Model-View-Controller* (MVC) berbasis Laravel yang difokuskan pada kecepatan dan *caching*. Berikut adalah lapisan-lapisan utamanya:

- **Presentation Layer (View):** Komponen Blade Templates, TailwindCSS, dan Vanilla JavaScript (khususnya untuk *AJAX Fetch* pada proses sinkronisasi matriks).
- **Business Logic Layer (Controller/Service):** *Controllers* (seperti `GuestMatrixController`, `PanduAdminController`), *Services* (`GoogleFormsSyncService`), dan *Traits* (`MatrixSummary`).
- **Data Access Layer (Model):** Eloquent ORM (*Models*: `Form`, `FormSubmission`).

---

## 3. Desain Basis Data (Entity Relationship)

Sistem ini dirancang menggunakan basis data relasional. Berikut adalah rincian skema tabel-tabel utama berdasarkan migrasi yang terdapat pada repositori (*tabel `users` dan pivot-nya merupakan struktur legacy yang tidak lagi menjadi aktor utama*):

### 3.1 Tabel `forms`
Menyimpan hierarki master formulir (Kategori Induk > Sub Kategori > Form Akhir/Leaf). Diimplementasikan menggunakan pola *Self-Referencing*.

| Nama Kolom | Tipe Data | Keterangan |
|---|---|---|
| `id` | BIGINT (PK) | Primary key |
| `parent_id` | BIGINT (FK) | Foreign key ke tabel `forms` itu sendiri (null jika *root/kategori utama*) |
| `name` / `title` | VARCHAR | Nama kategori atau nama form (misal: "Fix Ladder Area A") |
| `google_form_id` | VARCHAR | ID referensi API Google Forms (*jika ada*) |
| `options` | JSON | Opsi standar kelayakan dari pertanyaan Google Form |
| `is_category` | BOOLEAN | Penanda apakah baris ini sekadar kategori folder atau sebuah formulir nyata |

### 3.2 Tabel `form_submissions`
Menyimpan data respons/inspeksi yang telah tersinkronisasi dari Google Forms.

| Nama Kolom | Tipe Data | Keterangan |
|---|---|---|
| `id` | BIGINT (PK) | Primary key |
| `form_id` | BIGINT (FK) | Relasi ke tabel `forms` |
| `date` | DATE | Tanggal pengisian formulir |
| `answers` | JSON | Kumpulan jawaban pengguna yang dipetakan dari struktur Google Form |
| `notes` | TEXT | Catatan tambahan (juga memuat teks `[IMPORTED FAILURES]` jika berasal dari skrip *legacy* PDF) |
| `submitted_at` / `created_at` | TIMESTAMP | Waktu penyerahan/sinkronisasi form |

---

## 4. Desain Komponen & Logika Modul

### 4.1 GoogleFormsSyncService
Kelas *Service* sentral yang menangani komunikasi HTTP dengan API Google Forms. Diinvokasi secara bertahap berdasarkan permintaan dari antarmuka klien (*polling chunking*).

- **Method pengambilan struktur:** Menarik struktur pertanyaan Google Form dan memetakan *items* (pertanyaan) menjadi array JSON untuk disimpan ke kolom `options` di tabel `forms`.
- **Method sinkronisasi respons:** Menarik seluruh respons dari responden (inspektur). Melakukan iterasi terhadap setiap baris respons dan menggunakan fungsi `updateOrCreate` pada Model `FormSubmission` untuk mencegah duplikasi entri data.

### 4.2 Polling Chunking pada GuestMatrixController
Pemuatan data sinkronisasi secara *live* diproses menggunakan teknik *chunking* agar tidak terjadi batas waktu henti (*timeout*) dan menjaga antarmuka tetap responsif.

**Pseudo-code Alur Polling:**
```text
1. Klien mengirim GET /guest/matrix/live/poll/init
   Server menghitung total form yang perlu disinkronkan dan merespons dengan nilai 'total_chunks'.
   
2. Klien me-looping request ke POST /guest/matrix/live/poll/chunk
   Klien mengirim payload { chunk_index, ... }.
   Server memanggil GoogleFormsSyncService untuk sekelompok form (limit/offset) dan merespons: 
   { status: "processing", progress: % }
   
3. Setelah semua chunk selesai, Klien memanggil POST /guest/matrix/live/poll/finish
   Server menyusun status rekapitulasi, menyimpan ke Cache (key: 'global_live_submissions'), dan merespons sinyal selesai.
```

### 4.3 Logika Kalkulasi Matriks pada Trait MatrixSummary
Trait ini adalah komponen inti (*Core Engine*) untuk menentukan kelayakan inspeksi. Disertakan ke dalam kontroler atau layanan matriks untuk memproses data secara komputasi agregat tanpa melakukan *query* N+1.

**Pseudo-code Penentuan Status Good vs Not Good:**
```text
FUNCTION calculateStatus(Form $leaf):
    // 1. Ambil Submission Terakhir bulan tersebut
    latestSubmission = $leaf->submissions->sortByDesc('date')->first()

    // 2. Evaluasi Form Kosong
    IF latestSubmission IS NULL:
        RETURN "Unfilled" (Abu-abu)

    // 3. Evaluasi Imported Failures (Data dari Legacy PDF/Excel)
    IF latestSubmission->notes CONTAINS "[IMPORTED FAILURES]":
        failures = extractFailures(latestSubmission->notes)
        IF failures MATCHES $leaf->options:
            RETURN "Not Good" (Merah)

    // 4. Evaluasi Normal berdasarkan perbandingan opsi
    unmatchedOptions = COMPARE($leaf->options, latestSubmission->answers)
    
    IF unmatchedOptions IS NOT EMPTY:
        RETURN "Not Good" (Merah) // Ada standar yang tidak dipenuhi
        
    RETURN "Good" (Hijau) // Semua standar terpenuhi
```

### 4.4 Keamanan Modul Pandu Admin
Terdapat autentikasi terpisah dari aplikasi utama. `PanduAdminController` menggunakan `pandu.auth` Middleware untuk membatasi akses tanpa memerlukan tabel database *users*.

- **Kredensial:** Disimpan secara statis pada berkas `.env` (misalnya kredensial master atau token).
- **Aksi Konfigurasi:** Jika pengguna tervalidasi, mereka dapat melakukan tindakan pembaruan Token Google JSON maupun me-*reset* (flush) *Cache* secara langsung dari web.

### 4.5 Utilitas Parsing Eksternal (Folder `scratch/`)
Modul eksperimental berbentuk skrip lepas (*standalone scripts*) yang digunakan untuk mengekstrak *legacy data* sebelum atau selama migrasi ke sistem PANDU.

- **extract_rak_pdf.py:** Skrip Python yang menggunakan *regular expressions* untuk mengidentifikasi baris-baris standar pemeriksaan dari dokumen PDF laporan riwayat.
- **inspect_*.php:** Skrip PHP eksternal (misal: `inspect_rolling_door_options.php`) untuk melakukan pengujian *regex* (`preg_match`) atau membongkar struktur JSON dari berbagai format *Google Forms* yang lebih kompleks sebelum aturan ekstraknya diterapkan ke dalam `MatrixSummary`.
