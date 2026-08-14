Software Requirements Specification - PANDU v1.0
---

# SRS
### Software Requirements Specification PANDU — Platform Analitik dan Digitalisasi Checklist Plant Cianjur

| Atribut | Detail |
|---|---|
| **Versi** | 1.0 |
| **Tanggal** | Agustus 2026 |
| **Status** | Draft |

## Revision History
| Nama | Tanggal | Alasan Perubahan | Versi |
|---|---|---|---|
| AI Assistant | Agustus 2026 | Dokumen awal dibuat | 1.0 |

---

## 1. Pendahuluan

### 1.1 Tujuan
Dokumen Software Requirements Specification (SRS) ini mendeskripsikan seluruh kebutuhan fungsional dan non-fungsional dari sistem **PANDU (Platform Analitik dan Digitalisasi Checklist Plant Cianjur)** versi 1.0. 
Dokumen ini menjadi acuan resmi bagi tim pengembang, penguji, dan pemangku kepentingan dalam membangun dan memvalidasi sistem.
Ruang lingkup dokumen ini mencakup subsistem sinkronisasi Google Forms, parsing data dinamis, dashboard live matriks, serta panel konfigurasi tersembunyi (Pandu Admin).

### 1.2 Konvensi Dokumen
- Setiap kebutuhan fungsional diidentifikasi dengan kode unik format REQ-XX-YY, di mana XX adalah kode fitur dan YY adalah nomor urut kebutuhan.
- Tingkat prioritas: Tinggi (T), Sedang (S), Rendah (R).
- Istilah yang didefinisikan dalam Lampiran A ditulis dengan huruf awal kapital atau dicetak miring pada awal kemunculannya.

### 1.3 Pembaca dan Saran Membaca
| Pembaca | Relevansi | Saran Bagian |
|---|---|---|
| Pengembang | Seluruh dokumen | Baca berurutan dari Bab 1 s.d. 6 |
| Supervisor / Engineer | Bab 2, 4, 5 | Fokus pada operasional dashboard dan aturan bisnis (penentuan Good/Not Good) |
| System Configurator | Bab 3, 4 | Fokus pada integrasi API dan Pandu Admin |

### 1.4 Ruang Lingkup Produk
PANDU adalah platform berbasis web yang berfungsi sebagai Dashboard Live untuk menampilkan matriks checklist dari berbagai fasilitas engineering di Plant Cianjur. Pengisian data dilakukan melalui Google Forms, sedangkan PANDU bertugas menyinkronkan data secara real-time, mem-parsing format submission yang kompleks, dan menampilkannya dalam UI matriks kalender harian/bulanan.
Tujuan bisnis utama sistem ini adalah:
- Sentralisasi monitoring pengisian form checklist harian/berkala tim engineering.
- Meningkatkan efisiensi pengecekan fasilitas melalui indikator visual (Hijau/Merah) secara *real-time*.

### 1.5 Referensi
- Karl E. Wiegers, Software Requirements Specification Template, 1999.
- Laravel Documentation, https://laravel.com/docs/11.x
- Dokumentasi Google Forms API.

---

## 2. Deskripsi Umum

### 2.1 Perspektif Produk
PANDU adalah sistem yang berdiri sendiri (*standalone*) dan berfokus pada integrasi *read-only* dengan ekosistem Google. Sistem ini dibangun dengan arsitektur web berbasis Laravel (backend) dan Tailwind CSS beserta Vanilla JS (frontend) untuk menangani *polling* data.

### 2.2 Fungsi Produk
Fungsi-fungsi utama yang disediakan oleh PANDU adalah:
- **Dashboard Matriks Live:** Menampilkan kalender matriks status checklist (Terisi/Kosong, Good/Not Good).
- **Sinkronisasi Data (Google Forms API):** Melakukan *fetching* data submission terbaru melalui proses *client-side polling/chunking*.
- **Parsing Form Dinamis:** Memproses dan membaca hasil form yang kompleks (seperti Fix Ladder, Rolling Door, Sliding Door) menggunakan *trait* MatrixSummary.
- **Konfigurasi Pandu Admin:** Akses *hidden panel* bagi *developer* untuk mengatur token API dan *cache*.

### 2.3 Kelas Pengguna dan Karakteristik
| Role | Deskripsi | Hak Akses Utama | Frekuensi |
|---|---|---|---|
| Guest (Reguler) | Tim engineering, supervisor, pihak manajemen tanpa perlu akun. | Melihat Live Matrix, melakukan sinkronisasi otomatis, ekspor ke Excel. | Tinggi - Setiap hari kerja |
| Pandu Admin | Pengelola sistem dan IT Support. | Konfigurasi Token Google API, Spreadsheet ID, manajemen cache. | Rendah - Saat setup/maintenance |

### 2.4 Lingkungan Operasional
- **Server:** Linux (Ubuntu/Debian) atau Windows Server, PHP 8.2+, MySQL 8.0+ / SQLite.
- **Framework Backend:** Laravel 11.
- **Frontend:** Blade Templates, Tailwind CSS, Vanilla JS.
- **Browser:** Google Chrome, Mozilla Firefox, Microsoft Edge, Safari.

### 2.5 Batasan Desain dan Implementasi
- Sistem dibangun menggunakan arsitektur MVC (Laravel).
- Tidak menggunakan database pengguna (*users table*) untuk autentikasi Guest.
- Panel *Pandu Admin* diproteksi menggunakan *middleware custom* tanpa tabel `users` utama (kredensial dari environment/`.env`).
- Harus menggunakan teknik *chunking* (inisiasi, chunk, finish) untuk menghindari *timeout* dari Google Forms API saat data sangat banyak.

### 2.6 Dokumentasi Pengguna
- Panduan Penggunaan (User Guide) untuk Guest/Admin: Penjelasan pembacaan status *Leaf*, *Good*, dan *Not Good*.

### 2.7 Asumsi dan Dependensi
**Asumsi:**
- Hierarki kategori dan form sudah dipetakan dengan benar di database lokal (menyisakan entitas *Leaf* sebagai target evaluasi).
**Dependensi Eksternal:**
- Google Forms API: Performa dan ketersediaan sistem pembaruan matriks bergantung langsung pada respons API Google.

---

## 3. Kebutuhan Antarmuka Eksternal

### 3.1 Antarmuka Pengguna
- **Live Matrix Dashboard:** Halaman utama yang responsif, menampilkan kalender matriks beserta detail submission (siapa yang mengisi, catatan kerusakan).
- **Form Login Pandu Admin:** Halaman rahasia yang tidak mencantumkan navigasi publik.

### 3.2 Antarmuka Perangkat Keras
- Tidak ada ketergantungan perangkat keras khusus di sisi klien.

### 3.3 Antarmuka Perangkat Lunak
| Komponen | Keterangan |
|---|---|
| Laravel 11.x | Framework backend utama |
| MySQL / SQLite | Database relasional untuk menyimpan hierarki form dan cache submission |
| Google Forms API | Layanan eksternal sumber data submission |

### 3.4 Antarmuka Komunikasi
- Komunikasi dengan Google API melalui HTTPS (TLS).
- Komunikasi *client-to-server* untuk *polling* data matriks menggunakan AJAX Fetch API.

---

## 4. Fitur Sistem

Sistem direpresentasikan dalam fungsi-fungsi esensial yang berfokus pada integrasi data.

### 4.1 Sinkronisasi Live Matriks & Polling
Deskripsi: Sistem mengambil data terbaru dari Google Forms menggunakan mekanisme *chunking* di latar belakang agar halaman tidak *freeze*. | Prioritas: Tinggi

| ID | Deskripsi Kebutuhan | Prioritas |
|---|---|---|
| REQ-SYN-01 | Sistem harus menyediakan rute `/poll/init` untuk mempersiapkan status sinkronisasi. | Tinggi |
| REQ-SYN-02 | Sistem harus menyediakan rute `/poll/chunk` yang menangani permintaan *fetch* terpotong (*chunk*) secara berulang. | Tinggi |
| REQ-SYN-03 | Sistem harus menyimpan hasil sinkronisasi final di dalam cache global (TTL tertentu) saat `/poll/finish` dipanggil. | Tinggi |
| REQ-SYN-04 | Halaman antarmuka harus menampilkan *loading bar* atau *progress indicator* selama proses polling berlangsung. | Sedang |

### 4.2 Parsing Form Dinamis & Matrix Summary
Deskripsi: Evaluasi kelayakan setiap checklist (Leaf) berdasarkan komparasi antara standar opsi form dengan jawaban responden. | Prioritas: Tinggi

| ID | Deskripsi Kebutuhan | Prioritas |
|---|---|---|
| REQ-PRS-01 | Sistem hanya mengevaluasi data untuk entitas form yang tidak memiliki turunan (*Leaf*). | Tinggi |
| REQ-PRS-02 | Sistem harus menetapkan status **Good (Hijau)** jika seluruh opsi standar pada form terpenuhi di dalam jawaban (*answers*). | Tinggi |
| REQ-PRS-03 | Sistem harus menetapkan status **Not Good (Merah)** jika ada minimal satu opsi standar yang terlewat. | Tinggi |
| REQ-PRS-04 | Sistem harus mendeteksi *tag* `[IMPORTED FAILURES]` pada kolom notes dan mengubah status menjadi Not Good sesuai kegagalan yang tercatat. | Tinggi |

### 4.3 Pandu Admin Configurator
Deskripsi: Pengaturan parameter *back-end* melalui panel web tersembunyi. | Prioritas: Sedang

| ID | Deskripsi Kebutuhan | Prioritas |
|---|---|---|
| REQ-ADM-01 | Sistem menyediakan URL `/pandu-admin/login` dengan validasi kredensial statis. | Tinggi |
| REQ-ADM-02 | Akses dibatasi menggunakan custom middleware `pandu.auth`. | Tinggi |
| REQ-ADM-03 | Admin dapat memperbarui parameter sinkronisasi Google API. | Tinggi |

---

## 5. Kebutuhan Non-Fungsional

### 5.1 Kebutuhan Performa
- Halaman *Live Matrix* harus merender kerangka kalender dalam waktu kurang dari 2 detik (menggunakan data *cache*).
- Proses *polling chunk* ke API Google Forms maksimal berdurasi rata-rata 3-5 detik per *chunk* untuk mencegah *timeout* jaringan.

### 5.2 Kebutuhan Keamanan
- Kredensial *Pandu Admin* tidak disimpan bersama data entitas umum, melindungi dari eskalasi *privilege*.
- Proteksi CSRF dari Laravel aktif untuk seluruh *request* POST (termasuk *polling*).

### 5.3 Kebutuhan Keamanan Data
- Token *Google Service Account* (`.json`) disimpan di direktori server secara aman dan tidak pernah dikirim ke klien (*browser*).

### 5.4 Atribut Kualitas Perangkat Lunak
| Atribut | Penjelasan |
|---|---|
| Adaptabilitas | Fungsi sinkronisasi harus bisa membaca penambahan struktur form baru di Google Forms tanpa modifikasi kode (*dynamic parsing*). |
| Keandalan | Mekanisme *chunking* memastikan aplikasi tetap stabil dan tidak *crash* meski harus menyinkronkan ratusan form. |

### 5.5 Aturan Bisnis
- **Checklist Terisi:** Ditandai saat ada minimal satu submission terbaru untuk form (*Leaf*) tersebut di bulan berjalan.
- **Validasi Good/Not Good:** Validasi bersifat ketat (100% *compliant*). Satu cacat atau opsi tak terceklis berarti keseluruhan status form adalah *Not Good*.

---

## 6. Kebutuhan Lainnya

### 6.1 Kebutuhan Database
- Tabel form berelasi hierarkis (Parent-Child) menggunakan mekanisme *self-referencing* di MySQL/SQLite.

### 6.2 Kebutuhan Lokalisasi
- Sistem menggunakan Bahasa Indonesia untuk indikator pesan di *Dashboard*.

---

## Lampiran A: Glosarium
| Istilah | Definisi |
|---|---|
| Leaf | Form pada level terendah dalam hierarki yang tidak memiliki turunan, yang dievaluasi dalam matriks. |
| Polling / Chunking | Teknik memecah request ke server menjadi bagian-bagian kecil secara bertahap via JavaScript untuk mencegah timeout jaringan. |
| Matrix Summary | Fitur sistem untuk merangkum dan mengevaluasi seluruh hasil checklist menjadi representasi warna kalender (Good/Not Good/Unfilled). |
| Pandu Admin | Role administratif rahasia untuk konfigurasi API dan token sistem. |

---

## Lampiran B: Model Analisis
(Model relasi direpresentasikan melalui hierarki objek Form)
- `Form` memiliki banyak `Form` sebagai *children* (Sub-Kategori).
- `Form` (*Leaf*) memiliki banyak `FormSubmission` (Jawaban responden yang ditarik dari Google Forms).

---

## Lampiran C: Daftar TBD (To Be Determined)
| No | Item TBD | Bab Terkait | Target Resolusi |
|---|---|---|---|
| 1 | Spesifikasi mekanisme auto-trigger sinkronisasi dari *Google Apps Script* (jika *polling* manual ingin diganti). | REQ-SYN-01 | Fase Pengembangan Lanjutan |
| 2 | Sistem log histori perubahan konfigurasi *Pandu Admin*. | REQ-ADM-03 | Jika dibutuhkan audit sistem |

