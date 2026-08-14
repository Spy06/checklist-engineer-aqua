# Panduan Penggunaan Checklist Engineer Aqua

Dokumen ini menjelaskan alur penggunaan program secara detail, mulai dari interaksi masing-masing peran (role) pengguna hingga cara sistem membaca dan mengkategorikan data (Good, Not Good, Terisi, Tidak Terisi).

---

## 1. Alur Penggunaan Berdasarkan Peran (Role)

Aplikasi ini dibagi menjadi tiga area utama yang diakses oleh peran yang berbeda-beda.

### A. Pengguna Biasa / Guest (Tanpa Login)
Pengguna biasa adalah tim engineering, supervisor, atau siapa saja yang ingin melihat status harian/bulanan pengisian form. Mereka tidak memerlukan akun atau password.

1. **Akses Dashboard Utama:** Pengguna cukup membuka URL utama aplikasi (`/` atau `/guest/matrix/live`).
2. **Proses Sinkronisasi Live (Polling):** 
   - Saat halaman dibuka, aplikasi tidak langsung memuat semua data sekaligus untuk menghindari *timeout* atau *loading* yang terlalu lama.
   - Aplikasi akan melakukan *polling/chunking* di latar belakang (client-side via JavaScript) untuk menyinkronkan data terbaru dari Google Forms.
   - Pengguna akan melihat progres *loading* hingga seluruh data selesai diperbarui.
3. **Melihat Matriks Checklist:** Setelah sinkronisasi selesai, pengguna akan disajikan tabel/matriks kalender yang menunjukkan status setiap *leaf* (form akhir) dalam bentuk indikator warna (Hijau untuk Good, Merah untuk Not Good, dan Abu-abu/Kosong untuk Tidak Terisi).
4. **Melihat Detail & Export:** Pengguna dapat mengklik sel pada matriks untuk melihat detail *submission* (siapa yang mengisi, apa yang tidak sesuai) serta dapat mengekspor matriks ke format Excel.


### B. Pandu Admin / System Config (Hidden Admin)
Ini adalah role khusus untuk konfigurasi sistem tingkat lanjut, biasanya digunakan oleh *developer* atau *IT Support*.

1. **Login:** Mengakses URL rahasia `/pandu-admin/login`.
2. **Autentikasi:** Memasukkan *password* atau kredensial rahasia yang tidak terhubung dengan tabel `users` utama (biasanya diset statis atau di `.env`).
3. **Konfigurasi API:** Digunakan untuk mengatur Token Google API, *Spreadsheet/Form ID*, dan parameter sinkronisasi.

---

## 2. Cara Sistem Membaca dan Mengkalkulasi Data

Sistem menampilkan rekapitulasi data berdasarkan hierarki Form. Data dihitung menggunakan *trait* `MatrixSummary`. Berikut adalah penjelasan indikator datanya:

### A. Total (Leaf)
- **Definisi:** Form disusun seperti pohon bercabang (Kategori Utama > Sub Kategori > Form Checklist). 
- **Penghitungan:** Sistem hanya menghitung **"Leaf"** atau Daun, yaitu form yang tidak memiliki *child* (turunan) lagi. 
- Jika ada 10 form akhir yang harus diinspeksi, maka nilai **Total = 10**.

### B. Checklist Terisi (Filled) & Tidak Terisi (Unfilled)
- **Checklist Terisi (Filled):** Jika sistem menemukan setidaknya **satu submission (jawaban)** dari Google Forms untuk *leaf* tersebut pada bulan yang sedang dilihat. Sistem akan mengambil submission paling terbaru (*latest submission*).
- **Checklist Tidak Terisi (Unfilled):** Jika pada bulan/hari tersebut tidak ditemukan satu pun data submission untuk *leaf* tersebut. Form ini akan masuk dalam hitungan `unfilled_count`.

### C. Good & Not Good
Ketika form sudah berstatus **Terisi**, sistem akan mengevaluasi jawaban dari submission paling terbaru untuk menentukan status *Good* atau *Not Good*.

1. **Good (Hijau):**
   - Form memiliki sejumlah *options* (standar pengecekan).
   - Sistem membandingkan *options* standar dengan *answers* (jawaban dari responden).
   - Jika **semua standar/opsi** telah terpenuhi (semua *options* ada di dalam *answers*), atau jika tidak ada catatan kegagalan (tidak ada *[IMPORTED FAILURES]*), maka form dianggap **Good**.
   - Artinya, tidak ada kerusakan atau anomali pada objek inspeksi.

2. **Not Good (Merah):**
   - Jika ada minimal **satu opsi standar yang tidak dicentang/terpenuhi** di *answers*, form tersebut dianggap **Not Good**.
   - Opsi yang tidak dicentang ini akan dicatat sebagai `non_standard` (kerusakan/anomali).
   - *Kasus Khusus (Imported Failures):* Jika data ditarik/diimpor dan di bagian `notes` terdapat teks `[IMPORTED FAILURES]`, sistem akan memecah teks kegagalan tersebut. Jika ada opsi standar yang terdeteksi di dalam kegagalan tersebut, maka form langsung diputuskan sebagai **Not Good**.

### D. Kalkulasi Persentase
Pada rekapitulasi bulanan, sistem akan menghitung:
- `filled_percent` = `(Filled / Total) * 100%`
- `good_percent` = `(Good / Filled) * 100%` (Persentase kelayakan dihitung hanya dari form yang sudah diinspeksi).

---
*Catatan: Dokumen ini dalam format Markdown (.md). Untuk mengubahnya menjadi format Microsoft Word (.docx), Anda bisa membuka file ini di aplikasi seperti Typora/Obsidian lalu mengekspornya, atau sekadar menyalin seluruh teks dan mem-paste (Copy-Paste) langsung ke Microsoft Word (format heading dan tabel akan otomatis menyesuaikan).*
