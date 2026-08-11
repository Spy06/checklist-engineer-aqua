# AGENTS.md — Checklist Engineer Aqua

> Dokumentasi teknis internal untuk AI Agent agar dapat melakukan debugging, feature implementation, dan maintenance dengan efisien di proyek Checklist Engineer Aqua.

---

## 1. Deskripsi Proyek

**Checklist Engineer Aqua** adalah aplikasi berbasis web untuk digitalisasi dan pengelolaan checklist serta form pelaporan harian/berkala bagi tim engineering. Aplikasi ini mendukung struktur form hierarkis (kategori dan sub-form) serta pelacakan pengisian (submissions) oleh responden.

### Tujuan Utama
- Digitalisasi sistem form checklist engineer.
- Hierarki form yang dinamis (Kategori > Form).
- Pengelolaan penugasan form (`user_form_assignments`) kepada user.
- Autentikasi modern berbasis Passkeys/WebAuthn.
- Sistem monitoring submission oleh Admin.

### Stack Teknologi
| Layer | Teknologi |
|-------|-----------|
| Backend Framework | Laravel 13 (PHP 8.3) |
| Frontend Interaktif | Livewire 4 + Flux UI |
| Admin Panel | Filament v3 |
| Styling | Tailwind CSS v4 |
| Build Tool | Vite 8 |
| Database Default | MySQL 8 / SQLite |
| Auth System | Laravel Fortify (Passkeys/WebAuthn) |
| Dev Tools | Larastan (PHPStan), Pest 4, Pint |

---

## 2. Fitur yang Sudah Tersedia

### 2.1. Authentication System
- **Laravel Fortify + Passkeys**
  - Autentikasi tanpa password (passwordless) menggunakan Passkeys/WebAuthn (`@laravel/passkeys`).
  - Terdapat tiga Role utama: `super_admin`, `admin`, `responden`.
  - Hanya `super_admin` dan `admin` yang bisa mengakses panel Filament.

### 2.2. Dynamic Form Management
- Form disimpan secara hierarkis (menggunakan `parent_id`).
- Terdapat flag `is_category` untuk membedakan antara folder/kategori dan form yang bisa diisi.
- Mendukung kustomisasi tampilan (icon, color, bg_color, border_color, text_color).

### 2.3. Form Submissions
- Menyimpan hasil pengisian form pada field `answers` dengan tipe JSON.
- Tersedia field `notes` untuk catatan tambahan.
- Form type dapat dicatat sebagai metadata di `form_submissions`.

### 2.4. User Assignments
- User (`admin` atau `responden`) dapat ditugaskan untuk mengelola atau mengisi form tertentu melalui `user_form_assignments`.

### 2.5. Filament Admin Panel
- Mengelola data Users, Forms, dan Form Submissions.
- Role `super_admin` memiliki akses penuh.

### 2.6. Guest Matrix
- Terdapat halaman `/guest/matrix` dan `/matrix` yang berfungsi sebagai overview atau matriks checklist.

---

## 3. Struktur Routing

### 3.1. Web Routes (`routes/web.php`)

| Method | URI | Name | Controller/Action | Middleware |
|--------|-----|------|-------------------|------------|
| GET | `/` | `home` | Redirect ke `/login` | - |
| GET | `/guest/matrix` | `guest.matrix` | `GuestMatrixController@index` | - |
| GET | `/dashboard` | `dashboard` | `FormController@index` | `auth`, `verified`, `RedirectAdminToPanel` |
| GET | `/matrix` | `matrix` | `MatrixController@index` | `auth`, `verified`, `RedirectAdminToPanel` |
| GET | `/forms/{path}` | `forms.show` | `FormController@show` | `auth`, `verified`, `RedirectAdminToPanel` |
| POST | `/forms/{path}` | `forms.store`| `FormController@store` | `auth`, `verified`, `RedirectAdminToPanel` |

---

## 4. Skema Database

### 4.1. Tabel Inti

#### `users`
| Column | Type | Keterangan |
|--------|------|------------|
| `id` | bigint PK | |
| `name` | string | |
| `username` | string unique | |
| `password` | string | hashed |
| `role` | string | `super_admin`, `admin`, `responden` |

#### `forms`
| Column | Type | Keterangan |
|--------|------|------------|
| `id` | bigint PK | |
| `parent_id` | FK nullable → forms | Relasi hierarkis |
| `slug` | string indexed | |
| `title` | string | |
| `is_category` | boolean | default false |
| `options` | json nullable | |
| `order` | integer | default 0 |
| `icon`, `color`, `bg_color` | string nullable | Kustomisasi tampilan |

#### `form_submissions`
| Column | Type | Keterangan |
|--------|------|------------|
| `id` | bigint PK | |
| `user_id` | FK → users (cascadeOnDelete) | |
| `form_id` | FK → forms (cascadeOnDelete) | |
| `form_type` | string | |
| `date` | date | |
| `answers` | json | |
| `notes` | text nullable | |

#### `user_form_assignments`
| Column | Type | Keterangan |
|--------|------|------------|
| `user_id` | FK → users | |
| `form_id` | FK → forms | |

#### `passkeys` (WebAuthn)
- Disediakan oleh Laravel Fortify/Passkeys.

---

## 5. Models & Relationships

```
User
  ├─ implements FilamentUser, PasskeyUser
  ├─ (passkeys via Laravel Passkeys trait)
  ├─ hasMany FormSubmission
  └─ belongsToMany Form (assignedForms)

Form
  ├─ belongsTo Form (parent)
  ├─ hasMany Form (children)
  ├─ hasMany FormSubmission
  └─ belongsToMany User (assignedUsers)

FormSubmission
  ├─ belongsTo User
  └─ belongsTo Form
```

---

## 6. Struktur Direktori Penting

```
checklist-engineer-aqua/
├── app/
│   ├── Console/Commands/
│   │   └── ImportCsvSubmissions.php
│   ├── Filament/
│   │   └── Resources/          # Forms, FormSubmissions, Users
│   ├── Http/
│   │   ├── Controllers/        # FormController, GuestMatrixController, MatrixController
│   │   └── Middleware/         # RedirectAdminToPanel
│   ├── Models/                 # User, Form, FormSubmission
│   └── Providers/
├── bootstrap/app.php
├── database/
│   └── migrations/
├── resources/
│   ├── css/app.css
│   └── views/
├── routes/
│   ├── web.php
│   └── settings.php
└── vite.config.js
```

---

## 7. Komponen Kritis & Catatan Teknis

### 7.1. Hierarki Form
- `Form::parent()` dan `Form::children()` digunakan untuk nested categories. Selalu waspada saat menghapus parent, karena `cascadeOnDelete()` akan menghapus semua turunannya.

### 7.2. Role Management & Filament Access
- Diatur di `User::canAccessPanel()`.
- Middleware `RedirectAdminToPanel` digunakan untuk memaksa user `admin` dan `super_admin` langsung ke panel Filament dari web route utama, memisahkan view responden.

### 7.3. Form Submissions
- Model menggunakan `$casts = ['answers' => 'array']`.
- Tipe form tersimpan pada `form_type`, dan direlasikan pada `form_id`.

### 7.4. Data Import
- Terdapat `ImportCsvSubmissions.php` dan skrip PHP terpisah (`verify_import.php`, `read_headers.php`) untuk utilitas migrasi data dan inspeksi dari file CSV (`headers_output.txt`).

---

## 8. Tracking Progress

### 8.1. Status Implementasi per Fitur
| Fitur | Status | Catatan |
|-------|--------|---------|
| User Roles & Passkeys | ✅ Done | |
| Hierarki Form & Migrasi | ✅ Done | Kategori vs Form tunggal |
| Form Submissions | ✅ Done | |
| Filament Panel & Middleware | ✅ Done | |
| Csv Import Commands | ✅ Done | |

---

## 9. Konvensi & Protokol AI Agent

### 9.1. Prinsip Debugging
1. **Evidence First**: Jangan tebak. Selalu baca model dan class Filament/Livewire sebelum mengubah kode.
2. **Role Check**: Jika ada bug authorization, periksa fungsi `isSuperAdmin()`, `isAdmin()`, `isResponden()` di model `User`.
3. **Verifikasi**: Jalankan perintah code quality (`lint:check`, `types:check`, `test`).

### 9.2. Komando Penting
| Perintah | Fungsi |
|----------|--------|
| `composer run dev` | Start server + queue + Vite |
| `composer run lint:check` | Cek format tanpa ubah (Pint) |
| `composer run types:check` | PHPStan/Larastan |
| `composer run test` | Pest PHP test suite |

### 9.3. Keamanan & Konvensi
- **JANGAN** commit `.env` ke repository.
- **Passkeys**: Perubahan pada login flow harus berkoordinasi dengan trait PasskeyAuthenticatable Fortify.
- **JSON Casts**: Selalu gunakan property JSON model sebagai array (`['answers' => 'array']`).

---

## 10. Quick Reference: File-File Kritis

| Untuk mengedit/memahami... | Lihat file... |
|----------------------------|---------------|
| Form Model & Hierarchy | `app/Models/Form.php` |
| Submissions Logic | `app/Models/FormSubmission.php` |
| Role & Panel Auth | `app/Models/User.php` |
| Rute & Middleware Admin | `routes/web.php` dan `app/Http/Middleware/RedirectAdminToPanel.php` |
| Skrip Import Data CSV | `app/Console/Commands/ImportCsvSubmissions.php` |
