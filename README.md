# Sistem Permohonan Sertifikat Elektronik

Aplikasi web untuk pengelolaan permohonan penerbitan **Sertifikat Elektronik (Tanda Tangan Elektronik / TTE)**.

Aplikasi memfasilitasi alur pengajuan oleh pegawai (pemohon), verifikasi berkas oleh petugas (verifikator), serta pengelolaan pengguna dan template surat oleh administrator.

---

## Fitur Utama

**Pemohon**
- Registrasi mandiri (NIP opsional bagi yang belum memiliki) dan login.
- Membuat, menyimpan draft, dan mengirim permohonan TTE beserta unggahan berkas persyaratan (Surat Permohonan, SK Jabatan, SK Pangkat, KTP).
- Memperbaiki dan mengajukan ulang permohonan yang ditolak.
- Menghapus (soft delete) permohonan yang sudah diterima.
- Memperbarui data profil termasuk NIP.

**Verifikator**
- Meninjau daftar permohonan masuk beserta berkasnya.
- Menerima atau menolak permohonan disertai catatan alasan.
- Mengaktivasi akun pemohon yang baru mendaftar.
- Mengunggah dan mengelola template surat permohonan.

**Administrator**
- Mengelola seluruh pengguna (aktivasi/nonaktivasi, ubah peran).
- Mengelola template surat permohonan.

**Umum**
- Notifikasi in-app (dropdown menampilkan notifikasi belum dibaca, halaman penuh untuk seluruh riwayat).
- Lokalisasi Bahasa Indonesia dengan zona waktu **Asia/Jakarta (WIB)**.

---

## Teknologi

| Komponen | Versi |
|----------|-------|
| PHP | ^8.3 |
| Laravel | ^13.0 |
| Livewire | ^4.3 |
| Tailwind CSS | ^3.4 (via Vite ^7) |
| Database | MySQL / MariaDB |
| Alpine.js | bawaan Livewire |

---

## Persyaratan Sistem

- PHP 8.3 atau lebih baru beserta ekstensi standar Laravel (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, dll.).
- Composer 2.x
- Node.js 18+ dan NPM
- MySQL / MariaDB
- Disarankan menggunakan **Laragon**.

---

## Struktur Project

```
sertifikat-elektronik/
├── app/
│   ├── Enums/              # JenisPermohonan, JenisDokumen, StatusPermohonan, RoleUser, TipeNotifikasi
│   ├── Http/
│   │   ├── Controllers/    # DownloadController (unduh/lihat dokumen & template)
│   │   └── Middleware/     # EnsureUserHasRole, EnsureUserIsActive, SecurityHeaders
│   ├── Models/             # User, Permohonan, DokumenPermohonan, TemplateDokumen, Notifikasi, RiwayatVerifikasi
│   ├── Policies/           # Otorisasi per model (PermohonanPolicy, DokumenPolicy, dll.)
│   ├── Providers/          # AppServiceProvider (registrasi policy, locale)
│   └── Services/           # PermohonanService, NotifikasiService (logika domain)
├── database/
│   ├── migrations/         # Skema tabel
│   ├── seeders/            # UserSeeder (akun default)
│   └── factories/
├── resources/
│   ├── css/ js/            # Sumber aset (Tailwind, Alpine via Livewire)
│   └── views/
│       ├── components/     # Komponen Blade (sidebar-link, toast, badge-status, dll.)
│       ├── layouts/        # app (terautentikasi), guest (login/register)
│       └── pages/          # Komponen halaman Livewire per peran
│           ├── auth/       # login, register, verify-email
│           ├── pemohon/    # dashboard, buat-permohonan, detail, perbaiki-berkas
│           ├── verifikator/# dashboard, detail-permohonan, template
│           ├── admin/      # dashboard, pengguna, template
│           └── profile/    # profil pengguna
├── routes/
│   ├── web.php             # Rute aplikasi (per peran)
│   └── auth.php            # Rute autentikasi
├── storage/app/private/    # Penyimpanan berkas (dokumen & template) — non-publik
└── sertifikat_elektronik.sql  # Cadangan basis data contoh
```

Aplikasi menggunakan **Livewire single-file components**: setiap berkas di `resources/views/pages/` memuat kelas komponen (blok `<?php ... ?>`) sekaligus tampilannya dalam satu file `.blade.php`.

---

## Instalasi & Menjalankan

### 1. Siapkan kode & dependensi

```bash
# Masuk ke direktori project (lokasi default Laragon)
cd C:\laragon\www\sertifikat-elektronik

composer install
npm install
```

### 2. Konfigurasi environment

```bash
# Salin file environment (jika .env belum ada)
copy .env.example .env

php artisan key:generate
```

Lalu sesuaikan konfigurasi basis data dan aplikasi pada file `.env`:

```env
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sertifikat_elektronik
DB_USERNAME=root
DB_PASSWORD=
```

Buat basis data kosong bernama `sertifikat_elektronik` terlebih dahulu (mis. melalui phpMyAdmin/HeidiSQL di Laragon).

### 3. Migrasi & data awal

Jalankan migrasi sekaligus mengisi akun default:

```bash
php artisan migrate --seed
```

> Alternatif: berkas `sertifikat_elektronik.sql` dapat diimpor langsung sebagai contoh data. Setelah impor, tetap jalankan `php artisan migrate` agar perubahan skema terbaru ikut diterapkan.

### 4. Bangun aset frontend

```bash
npm run build
```

### 5. Jalankan aplikasi

```bash
php artisan serve
```

Akses melalui `http://localhost:8000`. Jika menggunakan virtual host Laragon, aplikasi juga dapat diakses via `http://sertifikat-elektronik.test`.

Untuk pengembangan dengan _hot reload_ aset, jalankan Vite pada terminal terpisah:

```bash
npm run dev
```

Atau jalankan seluruh layanan (server, queue, log, vite) sekaligus:

```bash
composer dev
```

---

## Akun Default

Seeder membuat akun berikut (semua sudah teraktivasi, kata sandi: **`password`**). Login menggunakan **email** dan kata sandi.

| Peran | Email | Kata Sandi |
|-------|-------|------------|
| Administrator | `admin@bengkaliskab.go.id` | `password` |
| Verifikator | `198505102010011002@bengkaliskab.go.id` | `password` |
| Verifikator | `199002152015032003@bengkaliskab.go.id` | `password` |
| Pemohon | `198803122012011004@bengkaliskab.go.id` | `password` |
| Pemohon | `199105202018012005@bengkaliskab.go.id` | `password` |

> Pemohon lain mengikuti pola email `{NIP}@bengkaliskab.go.id`. Akun pemohon yang **baru mendaftar sendiri** berstatus belum aktif dan harus diaktivasi terlebih dahulu oleh verifikator melalui menu **Aktivasi Akun**.

---

## Alur Singkat Aplikasi

1. **Pemohon** mendaftar, lalu menunggu aktivasi oleh verifikator.
2. Setelah aktif, pemohon mengisi data, mengunggah berkas, dan mengirim permohonan TTE.
3. **Verifikator** meninjau berkas, lalu **menerima** atau **menolak** (dengan catatan).
4. Jika ditolak, pemohon dapat memperbaiki berkas dan mengajukan ulang.
5. Setiap perubahan status memicu **notifikasi** untuk pemohon.

---

## Catatan Penyimpanan Berkas

Berkas dokumen dan template disimpan pada disk **`local`** (`storage/app/private/`), bukan direktori publik. Akses unduh/lihat dilayani lewat `DownloadController` dengan pemeriksaan otorisasi, sehingga berkas tidak dapat diakses langsung melalui URL.

---

## Perintah Berguna

```bash
# Bersihkan seluruh cache (config, route, view) — jalankan setelah mengubah .env atau Blade
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Jalankan ulang migrasi dari awal beserta seeder (menghapus seluruh data)
php artisan migrate:fresh --seed

# Menjalankan pengujian
php artisan test
```

---

## Keamanan

Aplikasi menerapkan beberapa lapis pengamanan: validasi sisi server pada setiap input, otorisasi berbasis _policy_ dan _middleware_ peran, _rate limiting_ pada login, header keamanan (`SecurityHeaders` middleware), serta penyimpanan berkas non-publik. Header HSTS dan Content-Security-Policy aktif khusus pada lingkungan `production`.

---

## Lisensi

Framework Laravel berlisensi [MIT](https://opensource.org/licenses/MIT).
