# Software Requirements Specification (SRS)
# PANDU (Platform Analisis dan Digitalisasi Checklist Plant Cianjur)

**Versi:** 1.0 (Revisi)
**Tanggal:** 13 Agustus 2026

## Daftar Isi
- 1. Pendahuluan
- 2. Deskripsi Keseluruhan
- 3. Kebutuhan Antarmuka Eksternal
- 4. Fitur Sistem
- 5. Kebutuhan Non-Fungsional Lainnya
- 6. Kebutuhan Lainnya
- Lampiran A: Glosarium
- Lampiran B: Daftar TBD (To Be Determined)

## Riwayat Revisi
| Nama | Tanggal | Alasan Perubahan | Versi |
|---|---|---|---|
| Tim Pengembang | 13 Agustus 2026 | Pembuatan dokumen awal berdasarkan struktur repositori (Revisi penyesuaian fungsionalitas aktual). | 1.0 |

---

## 1. Pendahuluan

### 1.1 Tujuan
Dokumen Software Requirements Specification (SRS) ini bertujuan untuk mendefinisikan spesifikasi kebutuhan perangkat lunak untuk sistem **PANDU (Platform Analisis dan Digitalisasi Checklist Plant Cianjur)**. Dokumen ini mencakup ruang lingkup produk, fungsionalitas, antarmuka, serta kebutuhan non-fungsional dari sistem yang akan dikembangkan. Spesifikasi ini digunakan sebagai acuan utama bagi tim pengembang (developer), penguji (tester), dan pemangku kepentingan (stakeholder) terkait.

### 1.2 Konvensi Dokumen
Dokumen ini ditulis menggunakan format standar IEEE yang disesuaikan. Ketentuan tipografi yang digunakan:
- **Cetak Tebal (Bold):** Digunakan untuk penekanan nama fitur, entitas basis data, atau istilah penting.
- Penomoran hierarkis digunakan untuk melacak fitur utama dan sub-fitur.
- Kebutuhan fungsional diidentifikasi dengan kode unik seperti REQ-01, REQ-02.

### 1.3 Audiens yang Dituju dan Saran Pembacaan
Dokumen ini ditujukan kepada Tim SHE dan Tim Engineer Plant Cianjur. Disarankan untuk membaca mulai dari **Bagian 2 (Deskripsi Keseluruhan)** untuk memahami konteks dan fungsi utama produk, kemudian dilanjutkan ke **Bagian 4 (Fitur Sistem)** untuk melihat detail teknis pengembangan.

### 1.4 Ruang Lingkup Produk
**PANDU** adalah aplikasi berbasis web yang dibangun menggunakan kerangka kerja (*framework*) Laravel. Aplikasi ini dirancang untuk mendigitalisasi proses inspeksi (*checklist*) perangkat dan keamanan kerja di area Plant Cianjur (seperti inspeksi *Fix Ladder, Rolling Door, Pintu Geser, Rak, Skylight, Sodu Gas, Scaffolding*, dan *Work At Height*). Sistem ini memusatkan pengumpulan data yang sebelumnya tersebar (termasuk dari Google Forms) ke dalam satu dasbor analitik. Fitur unggulannya meliputi sinkronisasi otomatis dengan Google Forms, *parsing* jawaban dinamis, dan matriks pelaporan berbasis *real-time*.

### 1.5 Referensi
- Kode Sumber Aplikasi (GitHub Repository: checklist-engineer-aqua)
- Dokumen *SRS Template.pdf* (Karl E. Wiegers, 1999)
- Dokumentasi Resmi Laravel 11.x

---

## 2. Deskripsi Keseluruhan

### 2.1 Perspektif Produk
PANDU merupakan sistem mandiri namun memiliki fungsionalitas terintegrasi erat dengan ekosistem pihak ketiga, khususnya Google Workspace (Google Forms API). Sistem ini menggantikan pemantauan inspeksi manual yang ada di lapangan, memungkinkan inspektur dan manajemen Plant Cianjur untuk memantau status aset pabrik secara terpusat. Arsitektur sistem menggunakan pola *Model-View-Controller* (MVC) yang didukung oleh komponen Laravel, Blade Templates, TailwindCSS, dan Vanilla JavaScript (untuk *client-side polling*).

### 2.2 Fungsi Produk
Secara garis besar, PANDU memiliki fungsi-fungsi berikut:
- **Dasbor Analitik dan Matriks Checklist (Guest View):** Halaman pemantauan penyelesaian inspeksi yang dapat diakses publik tanpa login.
- **Sinkronisasi Data Otomatis:** Menarik data dari Google Forms secara berkala/manual menggunakan `GoogleFormsSyncService` dengan metode *chunk polling*.
- **Parsing Data Dinamis:** Mengevaluasi hasil inspeksi kompleks menjadi indikator sederhana (*Good/Not Good/Unfilled*).
- **Manajemen Konfigurasi Tersembunyi (Pandu Admin):** Mengatur kredensial API dan sinkronisasi melalui *hidden panel*.
- *(Eksperimental)* **Ekstraksi PDF:** Ekstraksi dan *parsing* data otomatis dari berkas PDF menggunakan *script* Python.

### 2.3 Kelas dan Karakteristik Pengguna
- **Guest / Regular User:** Pengguna tanpa otentikasi (seperti inspektur atau supervisor di lapangan) yang bisa melihat status pengisian formulir beserta matriksnya secara langsung.
- **Pandu Admin:** Pengembang atau IT Support yang memiliki otorisasi (melalui *hidden login*) untuk mengonfigurasi token API dan mengelola *cache* sistem.

### 2.4 Lingkungan Operasi
- **Platform:** Aplikasi Web (Akses melalui peramban modern seperti Chrome, Firefox, Edge, Safari).
- **Sistem Operasi Server:** Linux (Ubuntu/Debian) atau lingkungan berbasis Docker.
- **Backend:** PHP 8.2 atau yang lebih baru, kerangka kerja Laravel 11.
- **Database:** MySQL atau SQLite.
- **Frontend:** HTML5, CSS3 (TailwindCSS), Vanilla JavaScript.
- **Eksternal (Opsional/Eksperimental):** Python 3.x (untuk mengeksekusi skrip PDF parser seperti `extract_rak_pdf.py`).

### 2.5 Batasan Desain dan Implementasi
- Sistem dikembangkan menggunakan kerangka kerja Laravel dengan antarmuka yang memanfaatkan Blade Templates (tanpa kebergantungan penuh pada Livewire untuk halaman utama).
- Mekanisme sinkronisasi matriks (di halaman *live*) harus menggunakan *Vanilla JS polling (chunking)* untuk menghindari *timeout* dari Google Forms API saat memuat ratusan form.
- Tidak ada tabel *users* konvensional untuk *Guest*; keamanan panel administrasi dilakukan melalui otentikasi *middleware* khusus (Pandu Admin Auth).

### 2.6 Dokumentasi Pengguna
Dokumentasi pengguna akhir (*User Manual*) disediakan dalam bentuk file panduan (Markdown), difokuskan pada penjelasan alur per *role* dan cara pembacaan data matriks (*Good, Not Good, Total Leaf*).

### 2.7 Asumsi dan Ketergantungan
- Koneksi internet yang stabil diperlukan baik di sisi klien (untuk mengakses web dan proses *polling*) maupun di sisi server (untuk memanggil Google API).
- Skema kuesioner pada Google Forms tidak boleh diubah strukturnya secara ekstrem tanpa menyesuaikan logika pemetaan (*mapping*) di backend PANDU.

---

## 3. Kebutuhan Antarmuka Eksternal

### 3.1 Antarmuka Pengguna (User Interfaces)
PANDU menggunakan antarmuka berbasis Blade Templates dan Tailwind CSS yang bersifat *responsive*. Elemen standar meliputi:
- **Matriks Kalender (Dashboard Live):** Tabel interaktif dengan indikator warna (Hijau, Merah, Abu-abu) yang memperlihatkan status inspeksi aset harian/bulanan.
- **Indikator Loading/Polling:** Elemen visual (seperti *progress bar* atau teks status) yang menandakan sinkronisasi dengan Google Forms sedang berjalan di latar belakang.
- **Form Login Pandu Admin:** Formulir otentikasi sederhana dan tersembunyi yang hanya ditujukan bagi pengelola sistem.

### 3.2 Antarmuka Perangkat Keras (Hardware Interfaces)
Tidak ada kebutuhan antarmuka perangkat keras secara spesifik selain kompatibilitas pada perangkat PC/Laptop dan perangkat seluler pintar (*Smartphone/Tablet*) yang digunakan inspektur di lapangan.

### 3.3 Antarmuka Perangkat Lunak (Software Interfaces)
- **Database:** Berinteraksi dengan RDBMS menggunakan Laravel Eloquent ORM.
- **Google API:** Terhubung dengan *Google REST API* menggunakan format JSON untuk layanan pengambilan struktur formulir dan respons submisi.
- **Python Interpreter (Modul Eksperimental):** Eksekusi antarmuka baris perintah (CLI) lokal untuk meneruskan jalur berkas PDF ke modul Python dan membaca kembali keluaran teks hasil ekstraksinya.

### 3.4 Antarmuka Komunikasi (Communications Interfaces)
Semua komunikasi data antara peramban klien dan server menggunakan protokol HTTP/HTTPS. Sistem memanfaatkan metode `fetch()` pada sisi klien untuk melakukan sinkronisasi data secara *asynchronous* (polling *chunk-by-chunk*).

---

## 4. Fitur Sistem

### 4.1 Sinkronisasi Google Forms (Polling Chunking)
#### 4.1.1 Deskripsi dan Prioritas
Prioritas: Tinggi (High). Karena pengisian data dilakukan melalui Google Forms, PANDU harus menarik data terbaru tanpa membebani memori server atau memicu *timeout*.

#### 4.1.2 Urutan Stimulus/Respons
- Pengguna (Guest) mengakses Dashboard Utama (`/` atau `/guest/matrix/live`).
- Halaman memuat kerangka matriks kosong.
- Skrip JS klien memanggil rute `/poll/init` untuk memulai sinkronisasi.
- Klien melakukan perulangan ke rute `/poll/chunk` hingga seluruh form ditarik.
- Klien memanggil `/poll/finish` untuk menyimpan data final ke *cache*.

#### 4.1.3 Kebutuhan Fungsional
- **REQ-1.1:** Sistem harus membaca struktur pertanyaan formulir dari Google API.
- **REQ-1.2:** Sistem harus memetakan jawaban (*responses*) pengguna ke dalam tabel `form_submissions`.
- **REQ-1.3:** Sinkronisasi harus dipecah (*chunk*) dan di-cache secara global (`global_live_submissions`) agar pengunjung lain tidak perlu mengulang *fetching* yang memakan waktu lama.

### 4.2 Manajemen Matriks Checklist dan Submisi
#### 4.2.1 Deskripsi dan Prioritas
Prioritas: Tinggi (High). Modul analitik inti tempat hasil inspeksi dievaluasi kelayakannya.

#### 4.2.2 Urutan Stimulus/Respons
- Sinkronisasi data submisi selesai.
- *Trait* `MatrixSummary` dipanggil untuk mengevaluasi *latest submission* pada bulan berjalan.
- Sel pada matriks kalender diberi warna sesuai evaluasi kelayakan.

#### 4.2.3 Kebutuhan Fungsional
- **REQ-2.1:** Sistem hanya menghitung formulir tingkat paling akhir (*Leaf*) yang tidak memiliki turunan (*children*).
- **REQ-2.2:** Sistem harus membandingkan antara opsi standar form dengan jawaban submisi untuk menentukan status kelayakan (*Good / Not Good*).
- **REQ-2.3:** Jika pada catatan submisi terdapat teks `[IMPORTED FAILURES]`, sistem harus mem-parsing teks tersebut dan menjadikannya dasar penolakan kelayakan (status *Not Good*).

### 4.3 Konfigurasi Sistem Tersembunyi (Pandu Admin)
#### 4.3.1 Deskripsi dan Prioritas
Prioritas: Sedang (Medium). Antarmuka manajemen konfigurasi bagi *developer*.

#### 4.3.2 Urutan Stimulus/Respons
- Admin membuka rute `/pandu-admin/login`.
- Memasukkan kredensial konfigurasi sistem.
- Admin mengubah token sinkronisasi Google API atau menghapus *cache* sistem.

#### 4.3.3 Kebutuhan Fungsional
- **REQ-3.1:** Rute konfigurasi harus diproteksi dengan *middleware* kustom (`pandu.auth`).
- **REQ-3.2:** Fitur ini tidak boleh terkoneksi dengan mekanisme login publik.

### 4.4 (Eksperimental) Modul Ekstraksi Data Dokumen PDF (Parser)
#### 4.4.1 Deskripsi dan Prioritas
Prioritas: Rendah (Low / Eksperimental). Skrip *scratch* berbasis Python untuk mengekstrak laporan inspeksi lama (PDF) ke format relasional.

#### 4.4.2 Kebutuhan Fungsional
- **REQ-4.1:** Skrip Python (`extract_rak_pdf.py`) harus dapat mengekstrak teks baris-demi-baris menggunakan regular expressions (RegEx).
- **REQ-4.2:** Integrasi ke dalam ekosistem Laravel masih bersifat *To Be Determined* (TBD).

---

## 5. Kebutuhan Non-Fungsional Lainnya

### 5.1 Kebutuhan Kinerja (Performance Requirements)
- Penarikan data dari Google API secara *chunking* tidak boleh melampaui rata-rata respons 30 detik secara total, dan setiap *chunk* harus merespons di bawah 5 detik.
- Halaman dasbor awal (dari *cache*) harus dimuat di bawah 2 detik.

### 5.2 Kebutuhan Sekuritas (Security Requirements)
- Sistem wajib memaksa perlindungan CSRF (*Cross-Site Request Forgery*) pada seluruh rute pengiriman *polling* dan submisi konfigurasi.
- Token Google API (`.json`) tidak boleh terekspos ke direktori *public* dan dijaga keamanannya di sisi server.

### 5.4 Atribut Kualitas Perangkat Lunak
- **Adaptabilitas (Adaptability):** Skema pemetaan *dynamic parsing* harus cukup fleksibel untuk membaca format pertanyaan baru (misalnya *Ladder* vs *Rolling Door*).
- **Kegunaan (Usability):** Dasbor harus sangat sederhana, menampilkan visual warna (Hijau/Merah) yang seketika dapat dipahami oleh inspektur tanpa panduan teknis yang rumit.

### 5.5 Aturan Bisnis
- **Evaluasi 100%:** Matriks hasil pengecekan bersifat tegas. Jika 1 dari 10 opsi inspeksi gagal, maka status form tersebut dihitung sebagai **Not Good** secara keseluruhan.

---

## 6. Kebutuhan Lainnya

- **Database Standar:** Sistem menggunakan migrasi relasional untuk memetakan hubungan hierarkis *parent-child* dari entitas formulir.
- **Lokalisasi:** Antarmuka, format waktu sinkronisasi, dan konvensi nama matriks disesuaikan dengan bahasa Indonesia dan zona waktu lokal (WIB).

---

## Lampiran A: Glosarium
- **SRS:** Software Requirements Specification.
- **PANDU:** Platform Analisis dan Digitalisasi Checklist Plant Cianjur.
- **Leaf (Daun):** Form tingkat paling akhir dalam hierarki yang tidak memiliki turunan (*child*), yang menjadi target evaluasi dalam matriks.
- **Polling / Chunking:** Teknik penarikan data berulang dalam porsi kecil (*chunk*) oleh klien untuk mencegah penutupan koneksi akibat lambatnya respons server API (timeout).
- **Matrix Summary:** Logika sistem untuk merangkum, mengevaluasi, dan merepresentasikan seluruh hasil jawaban checklist ke dalam format visual.

## Lampiran B: Daftar TBD (To Be Determined)
1. Integrasi penuh skrip modul parser PDF (`extract_rak_pdf.py`) ke dalam ekosistem Laravel melalui pemanggilan proses asinkron (*Jobs*).
2. Mekanisme arsip data (*Archive Logging*) apabila jumlah pengisian harian telah melebihi batas memori *cache* maksimum selama kurun waktu tahunan.
