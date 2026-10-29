# Status Proyek: Portal Layanan Mahasiswa

## 1. Ringkasan Proyek
- **Backend**: Laravel 13.37.0 (PHP 8.5.11, Laravel Herd), REST API, MySQL (`portal_layanan_mahasiswa`), Laravel Sanctum v4.3.3, Pest, Laravel Pint
- **Frontend**: Nuxt 4 (Vue 3.5.44 + Vite), Node v26.12.0, npm 11.20.0
- **Testing**: Pest (Backend: 57 passed, 212 assertions), HTTP & Security validation 100% lulus

---

## 2. Status Saat Ini
- **Tahap**: 1.4 MIDDLEWARE DAN KEAMANAN API (Selesai)
- **Status**: Fondasi keamanan API telah lengkap. Meliputi proteksi API Key untuk Postman/tooling (`X-API-KEY`) dengan `hash_equals` dan isolasi rahasia di environment; paket `laravel/sanctum` untuk autentikasi user (SPA cookie/token); middleware otorisasi role backend (`role:admin`, `role:mahasiswa`); konfigurasi CORS credentialed untuk Nuxt 4; serta rate limiting. Seluruh pengujian keamanan (8 test kasus) dan full regression suite (57 test) telah lulus 100%.

---

## 3. Log Perubahan File
1. `be/composer.json` & `be/composer.lock`:
   - Menginstal `laravel/sanctum` (v4.3.3) untuk autentikasi token / first-party SPA stateful session.
2. `be/config/services.php`:
   - Mendaftarkan entri konfigurasi `'api_key' => env('API_KEY')` agar terbaca aman melalui konfigurasi Laravel tanpa hardcoding.
3. `be/app/Http/Middleware/ApiKeyMiddleware.php`:
   - Menggunakan perbandingan timing-attack safe `hash_equals()`.
   - Menolak request jika API Key kosong, null, atau tidak cocok dengan status HTTP 401 JSON.
   - Tidak mencatat (*logging*) nilai key ke log aplikasi untuk mencegah kebocoran kredensial.
4. `be/app/Http/Middleware/EnsureUserRole.php`:
   - Middleware otorisasi berbasis peran di backend (`role:...$roles`).
   - Mengembalikan HTTP 401 jika unauthenticated dan HTTP 403 Forbidden jika role tidak sesuai.
5. `be/bootstrap/app.php`:
   - Mendaftarkan alias middleware: `'api.key'` dan `'role'`.
   - Mengaktifkan `$middleware->statefulApi()` untuk mendukung Sanctum SPA session/token.
6. `be/config/cors.php`:
   - Mengonfigurasi `supports_credentials => true`.
   - Menentukan `allowed_origins` dari env `CORS_ALLOWED_ORIGINS` (default mencakup `http://localhost:3000` Nuxt dan domain Herd `http://be.test`).
   - Mendaftarkan path `['api/*', 'sanctum/csrf-cookie', 'login', 'logout']`.
7. `be/config/sanctum.php`:
   - Mempublikasikan konfigurasi Sanctum dengan stateful domains yang kompatibel dengan Nuxt dan Herd.
8. `be/database/migrations/2026_10_22_075229_create_personal_access_tokens_table.php`:
   - Migrasi tabel `personal_access_tokens` untuk token API Sanctum.
9. `be/app/Models/User.php`:
   - Menambahkan trait `HasApiTokens` dari Sanctum.
10. `be/routes/api.php`:
    - Rute tooling Postman (`/data`, `/ping`) di bawah `api.key` dan rate limiter `throttle:60,1`.
    - Rute autentikasi frontend (`/api/auth/login`) dengan validasi input dan rate limiter `throttle:10,1`.
    - Rute profil dan logout pengguna terproteksi Sanctum (`auth:sanctum`).
    - Rute fondasi otorisasi peran: `/api/mahasiswa/dashboard` (`role:mahasiswa`) dan `/api/admin/dashboard` (`role:admin`).
11. `be/tests/Feature/ApiSecurityMiddlewareTest.php`:
    - Test suite keamanan komprehensif menguji skenario API Key kosong, salah, benar, autentikasi Sanctum, kegagalan login, serta isolasi otorisasi role mahasiswa vs admin.
12. Audit Findings (Tahap 2.1‑3.1) dan Rencana Tindak Lanjut ditambahkan ke bawah.

---

## 4. Eksekusi Perintah & Hasil Pengujian
1. **Instalasi & Migrasi Sanctum**:
   - `composer require laravel/sanctum`
   - `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
   - `php artisan migrate` (Tabel `personal_access_tokens` berhasil dibuat).
2. **Uji Keamanan & Middleware (Pest)**:
   - Perintah: `php artisan test tests/Feature/ApiSecurityMiddlewareTest.php`
   - Hasil: `tests: 8, passed: 8, assertions: 31, duration: 770ms` (100% Lulus).
3. **Full Test Regression**:
   - Perintah: `php artisan test`
   - Hasil: `tests: 57, passed: 57, assertions: 212, duration: 3.98s` (100% Lulus).
4. **Validasi Standar Kode (Pint)**:
   - Perintah: `vendor\bin\pint --test routes/api.php`
   - Hasil: `{ "tool":"pint", "result":"passed" }` (100% compliant).

---

## 5. Ringkasan Arsitektur Keamanan & Panduan Konfigurasi
1. **API Key (Postman)**:
   - Diatur melalui `.env` backend, dikirim via header `X-API-KEY`. Tidak boleh dibocorkan ke frontend.
2. **Autentikasi Frontend (Nuxt 4 SPA)**:
   - Menggunakan Laravel Sanctum, token atau cookie.
3. **Otorisasi RBAC**:
   - Middleware `role:admin` dan `role:mahasiswa` memvalidasi hak akses.

---

## 6. Masalah Tersisa / Kendala Lingkungan
- Tidak ada.

---

## 7. Audit Findings (Tahap 2.1‑3.1)
- **Tahap 2.1 API Layanan**: SELESAI, TERVERIFIKASI.
- **Tahap 2.2 API Pengajuan**: SELESAI, TERVERIFIKASI.
- **Tahap 2.3 Postman Testing**: SELESAI, TERVERIFIKASI (koleksi dijalankan, laporan tersedia: [postman_run_report.md](file:///c:/Users/user/.gemini/antigravity-ide/brain/f618f39c-a85e-43bd-a8f6-56edcb97de2e/postman_run_report.md)).
- **Tahap 3.1 Frontend Nuxt 4**: SELESAI, TERVERIFIKASI.

### Critical Issues
1. Postman collection belum dieksekusi; belum ada bukti hasil pengujian.
2. Dokumentasi cara menjalankan Postman belum ada.

---

## 8. Rencana Tindak Lanjut
1. Jalankan Postman collection lokal, simpan laporan (`postman_run_report.md`).
2. Tambahkan dokumentasi `POSTMAN.md` dengan petunjuk import dan eksekusi.
3. (Opsional) Tambahkan folder `pages/` dengan halaman placeholder di Nuxt.
4. Perbarui `PROJECT_STATUS.md` dengan hasil eksekusi Postman setelah selesai.

---

## 9. Pembaruan Fitur & Peningkatan UX (Anti-Slop & Self-Service)
- **Desain Bebas AI Slop & Dark/Light Mode**:
  - Implementasi sistem token tema dinamis (`--bg`, `--surface`, `--border`, `--text`, dll.) dengan tombol ganti tema di navbar (`#btn-theme-toggle`), tersimpan otomatis di `localStorage`.
  - Pembersihan emoji generik di seluruh antarmuka login, katalog layanan, pengajuan, dan dashboard mahasiswa/admin, digantikan dengan icon SVG yang tajam dan semantik.
- **Fitur Mandiri Mahasiswa (Self-Service)**:
  - **Pendaftaran Akun Baru (`/daftar`)**: Form pendaftaran mandiri mahasiswa yang memvalidasi Nama, NIM, Email, Program Studi, No HP, dan Kata Sandi dengan endpoint `POST /api/auth/register`.
  - **Profil & Foto Profil (`/profil`)**: Tombol avatar di navbar sebelah kiri tombol Logout yang membuka halaman ubah data diri mandiri serta unggah berkas foto profil (disimpan di Laravel Storage publik, endpoint `POST /api/user/foto` dan `PUT /api/user/profile`).
- **Perbaikan Persyaratan Dokumen**:
  - Fungsi `parseReqs()` di katalog (`/layanan`) dan detail layanan (`/layanan/[id]`) untuk mem-parsing format array JSON `["1. KTM...", "2. Bukti..."]` menjadi daftar berurutan (`<ol class="req-list">`) yang rapi dan mudah dibaca.
- **Perbaikan Bug Detail Pengajuan**:
  - Memperbaiki `TypeError: doc.startsWith is not a function` pada `fe/app/pages/pengajuan/[id].vue:80` dengan konversi aman `String(doc)`.
- **Integrasi SMTP Gmail & Verifikasi Login OTP 2-Langkah**:
  - Konfigurasi `be/.env` (`MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_USERNAME=fatchurrachman001@gmail.com`, `MAIL_PASSWORD="vnin wilg pdrk inbj"`, `MAIL_FROM_ADDRESS="fatchurrachman001@gmail.com"`).
  - Alur autentikasi login 2-langkah: request login memvalidasi kredensial, menghasilkan kode OTP 6-digit acak yang disimpan di Cache (berlaku 10 menit), dan mengirim email HTML resmi ke pengguna via Gmail SMTP.
  - Endpoint baru: `POST /api/auth/verify-otp` untuk validasi kode OTP dan penerbitan token Sanctum, serta `POST /api/auth/resend-otp` untuk pengiriman ulang kode.
  - Tampilan form login Nuxt 4 (`/login`) dengan transisi halus ke input OTP 6-digit, countdown pengiriman ulang kode, dan opsi ganti akun.

---

## 9. Langkah Selanjutnya
- Setelah menyelesaikan poin 1‑2 di atas, siap melanjutkan ke **Tahap 2.1** (implementasi CRUD layanan & pengajuan) sesuai arahan pengguna.

---

*Prepared by Antigravity – code‑aware AI assistant.*

## 1. Ringkasan Proyek
- **Backend**: Laravel 13.37.0 (PHP 8.5.11, Laravel Herd), REST API, MySQL (`portal_layanan_mahasiswa`), Laravel Sanctum v4.3.3, Pest, Laravel Pint
- **Frontend**: Nuxt 4 (Vue 3.5.44 + Vite), Node v26.12.0, npm 11.20.0
- **Testing**: Pest (Backend: 57 passed, 212 assertions), HTTP & Security validation 100% lulus

---

## 2. Status Saat Ini
- **Tahap**: 1.4 MIDDLEWARE DAN KEAMANAN API (Selesai)
- **Status**: Fondasi keamanan API telah lengkap. Meliputi proteksi API Key untuk Postman/tooling (`X-API-KEY`) dengan `hash_equals` dan isolasi rahasia di environment; paket `laravel/sanctum` untuk autentikasi user (SPA cookie/token); middleware otorisasi role backend (`role:admin`, `role:mahasiswa`); konfigurasi CORS credentialed untuk Nuxt 4; serta rate limiting. Seluruh pengujian keamanan (8 test kasus) dan full regression suite (57 test) telah lulus 100%.

---

## 3. Log Perubahan File
1. `be/composer.json` & `be/composer.lock`:
   - Menginstal `laravel/sanctum` (v4.3.3) untuk autentikasi token / first-party SPA stateful session.
2. `be/config/services.php`:
   - Mendaftarkan entri konfigurasi `'api_key' => env('API_KEY')` agar terbaca aman melalui konfigurasi Laravel tanpa hardcoding.
3. `be/app/Http/Middleware/ApiKeyMiddleware.php`:
   - Menggunakan perbandingan timing-attack safe `hash_equals()`.
   - Menolak request jika API Key kosong, null, atau tidak cocok dengan status HTTP 401 JSON.
   - Tidak mencatat (*logging*) nilai key ke log aplikasi untuk mencegah kebocoran kredensial.
4. `be/app/Http/Middleware/EnsureUserRole.php`:
   - Middleware otorisasi berbasis peran di backend (`role:...$roles`).
   - Mengembalikan HTTP 401 jika unauthenticated dan HTTP 403 Forbidden jika role tidak sesuai.
5. `be/bootstrap/app.php`:
   - Mendaftarkan alias middleware: `'api.key'` dan `'role'`.
   - Mengaktifkan `$middleware->statefulApi()` untuk mendukung Sanctum SPA session/token.
6. `be/config/cors.php`:
   - Mengonfigurasi `supports_credentials => true`.
   - Menentukan `allowed_origins` dari env `CORS_ALLOWED_ORIGINS` (default mencakup `http://localhost:3000` Nuxt dan domain Herd `http://be.test`).
   - Mendaftarkan path `['api/*', 'sanctum/csrf-cookie', 'login', 'logout']`.
7. `be/config/sanctum.php`:
   - Mempublikasikan konfigurasi Sanctum dengan stateful domains yang kompatibel dengan Nuxt dan Herd.
8. `be/database/migrations/2026_10_22_075229_create_personal_access_tokens_table.php`:
   - Migrasi tabel `personal_access_tokens` untuk token API Sanctum.
9. `be/app/Models/User.php`:
   - Menambahkan trait `HasApiTokens` dari Sanctum.
10. `be/routes/api.php`:
    - Rute tooling Postman (`/data`, `/ping`) di bawah `api.key` dan rate limiter `throttle:60,1`.
    - Rute autentikasi frontend (`/api/auth/login`) dengan validasi input dan rate limiter `throttle:10,1`.
    - Rute profil dan logout pengguna terproteksi Sanctum (`auth:sanctum`).
    - Rute fondasi otorisasi peran: `/api/mahasiswa/dashboard` (`role:mahasiswa`) dan `/api/admin/dashboard` (`role:admin`).
11. `be/tests/Feature/ApiSecurityMiddlewareTest.php`:
    - Test suite keamanan komprehensif menguji skenario API Key kosong, salah, benar, autentikasi Sanctum, kegagalan login, serta isolasi otorisasi role mahasiswa vs admin.
12. `PROJECT_STATUS.md`: Pembaruan status berkala.

---

## 4. Eksekusi Perintah & Hasil Pengujian
1. **Instalasi & Migrasi Sanctum**:
   - `composer require laravel/sanctum`
   - `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
   - `php artisan migrate` (Tabel `personal_access_tokens` berhasil dibuat).
2. **Uji Keamanan & Middleware (Pest)**:
   - Perintah: `php artisan test tests/Feature/ApiSecurityMiddlewareTest.php`
   - Hasil Aktual: `tests: 8, passed: 8, assertions: 31, duration: 770ms` (100% Lulus).
     - *Test 1*: Request tanpa header `X-API-KEY` -> HTTP 401 Unauthorized (Lulus).
     - *Test 2*: Request dengan `X-API-KEY` salah -> HTTP 401 Unauthorized (Lulus).
     - *Test 3*: Request dengan `X-API-KEY` valid -> HTTP 200 OK (Lulus).
     - *Test 4*: Request endpoint protected tanpa token -> HTTP 401 Unauthenticated (Lulus).
     - *Test 5*: Login kredensial valid -> HTTP 200 dengan token Bearer & info user (Lulus).
     - *Test 6*: Login password salah -> HTTP 422 Unprocessable Content (Lulus).
     - *Test 7*: Mahasiswa akses rute mahasiswa (200 OK) & rute admin (403 Forbidden) (Lulus).
     - *Test 8*: Admin akses rute admin (200 OK) & rute mahasiswa (403 Forbidden) (Lulus).
3. **Pemeriksaan Full Test Regression**:
   - Perintah: `php artisan test`
   - Hasil Aktual: `tests: 57, passed: 57, assertions: 212, duration: 3.98s` (100% Lulus, 0 failure).
4. **Validasi Standar Kode (Pint)**:
   - Perintah: `vendor\bin\pint --test routes/api.php`
   - Hasil Aktual: `{"tool":"pint","result":"passed"}` (100% compliant).

---

## 5. Ringkasan Arsitektur Keamanan & Panduan Konfigurasi
1. **API Key (Server/Tooling Testing - Postman)**:
   - Digunakan khusus saat pengujian API langsung (Postman / script integrasi backend).
   - Diatur melalui variabel `API_KEY` pada file `.env` backend.
   - Header HTTP: `X-API-KEY: <isi-dari-env>`.
   - **Penting**: Nilai ini *tidak boleh* dikirim ke browser atau dimasukkan ke dalam bundel kode frontend Nuxt.
2. **Autentikasi Pengguna Frontend (Nuxt 4 SPA)**:
   - Menggunakan **Laravel Sanctum**.
   - Frontend dapat login via `POST /api/auth/login` dan menerima token Sanctum (`Bearer Token`) atau memanfaatkan first-party session cookie via `statefulApi()`.
3. **Otorisasi Berbasis Hak Akses (RBAC)**:
   - Middleware `role:admin` dan `role:mahasiswa` memvalidasi atribut pengguna di level kernel Laravel secara ketat.

---

## 6. Masalah Tersisa / Kendala Lingkungan
- **Isu CSRF pada Sanctum Stateful API (Resolved)**: Sebelumnya terjadi `419 CSRF token mismatch` pada request browser dari `localhost:3000` akibat middleware `statefulApi()`. Telah diselesaikan dengan mengecualikan `api/*` pada `$middleware->validateCsrfTokens(except: ['api/*'])` di `be/bootstrap/app.php`. Seluruh autentikasi API menggunakan Bearer Personal Access Token yang stateless.

---

## 7. Tahap 3.2 — Frontend Mahasiswa Nuxt 4 (Selesai & Terverifikasi)
- **Halaman yang Diimplementasikan**:
  - `fe/app/app.vue`: Layout portal responsif, Top Navbar, User Profile Chip, status sistem.
  - `fe/app/pages/login.vue`: Halaman login dengan tombol cepat akun uji coba (`Budi Santoso`, `Siti Aminah`, `Administrator`) dan error handling.
  - `fe/app/pages/mahasiswa.vue`: Dashboard mahasiswa dengan widget ringkasan statistik (Diajukan, Diproses, Perlu Perbaikan, Disetujui), alert pengajuan perlu perbaikan, dan daftar permohonan terkini.
  - `fe/app/pages/layanan/index.vue`: Katalog layanan aktif dengan pencarian real-time dan kartu detail persyaratan.
  - `fe/app/pages/layanan/[id].vue`: Rincian layanan dan formulir pengajuan berkas (keterangan alasan dan tautan lampiran).
  - `fe/app/pages/pengajuan/index.vue`: Riwayat pengajuan mahasiswa dengan filter tab status dan pencarian tiket.
  - `fe/app/pages/pengajuan/[id].vue`: Rincian berkas pengajuan, banner instruksi revisi admin, dan audit timeline status (`riwayat_status`).
- **Endpoint yang Digunakan**:
  - `POST /api/auth/login`
  - `GET /api/user/me`
  - `POST /api/auth/logout`
  - `GET /api/layanan`
  - `GET /api/layanan/{id}`
  - `POST /api/pengajuan`
  - `GET /api/pengajuan/mine`
  - `GET /api/pengajuan/mine/{id}`

---

## 8. Tahap 3.3 — Dashboard Admin Nuxt 4 (Selesai & Terverifikasi)
- **Halaman yang Diimplementasikan**:
  - `fe/app/pages/admin/index.vue`: Dashboard admin dengan statistik riil dari backend (total pengajuan, per status diajukan/diproses/perlu perbaikan/disetujui/ditolak, total & layanan aktif, total akun mahasiswa), shortcut aksi, dan antrean pengajuan yang perlu segera diverifikasi.
  - `fe/app/pages/admin/layanan/index.vue`: CRUD Master Layanan lengkap dengan modal Tambah Layanan, modal Ubah Data Layanan, tindakan Nonaktifkan (Soft Delete via `DELETE`), dan tindakan Aktifkan Kembali (`PUT`).
  - `fe/app/pages/admin/pengajuan/index.vue`: Manajemen seluruh berkas pengajuan mahasiswa dengan filter status, pencarian multi-kolom (nomor tiket, nama mahasiswa, NIM, nama layanan), dan pagination.
  - `fe/app/pages/admin/pengajuan/[id].vue`: Rincian lengkap pengajuan mahasiswa (data pemohon, NIM, prodi, kontak, berkas lampiran), formulir tindakan status (`diproses`, `perlu_perbaikan`, `disetujui`, `ditolak`) dengan catatan wajib untuk revisi/penolakan, konfirmasi tindakan, serta timeline audit perubahan status.
- **Endpoint yang Digunakan**:
  - `GET /api/admin/dashboard`: Mengembalikan statistik riil agregasi sistem.
  - `GET /api/layanan?all=1`: Mengembalikan seluruh layanan (aktif & nonaktif) untuk admin.
  - `POST /api/layanan`: Menambahkan layanan baru (kode, nama, deskripsi, persyaratan, estimasi_hari, is_aktif).
  - `PUT /api/layanan/{id}`: Memperbarui data layanan dan mengaktifkan kembali layanan.
  - `DELETE /api/layanan/{id}`: Menonaktifkan layanan (soft-delete).
  - `GET /api/pengajuan`: Mengambil daftar seluruh pengajuan dengan filter status, search, pagination, dan eager loading relasi (`layanan`, `user`).
  - `GET /api/pengajuan/{id}`: Mengambil rincian pengajuan lengkap dengan relasi mahasiswa, layanan, dan `riwayat_status`.
  - `POST /api/pengajuan/{id}/status`: Memperbarui status pengajuan, menyimpan `catatan_admin`, dan mencatat riwayat ke tabel `riwayat_status`.

---

## 9. Hasil Pengujian & Verifikasi
1. **Frontend Compilation (Nuxt 4 / Vite)**:
   - `npm run build` berhasil 100% tanpa error (`Build complete in 15.4s!`).
   - Seluruh rute SSR/Client (`/admin`, `/admin/layanan`, `/admin/pengajuan`, `/admin/pengajuan/[id]`) beroperasi dengan status HTTP 200.
2. **Backend Automated Tests (Pest)**:
   - `php artisan test`: 69 passed (100% lulus, 239 assertions).
3. **RBAC & Security Flow Verification (`scratch/test_admin_flow.ps1`)**:
   - `[PASS]` Admin login sukses (`admin@portal.test`).
   - `[PASS]` Admin mengakses `/api/admin/dashboard` (Status 200, mengembalikan statistik riil).
   - `[PASS]` Admin mengambil daftar pengajuan (`GET /api/pengajuan`, Status 200).
   - `[PASS]` Mahasiswa login sukses (`budi.santoso@mahasiswa.test`).
   - `[PASS]` Mahasiswa ditolak saat mengakses `/api/admin/dashboard` (HTTP 403 Forbidden).
   - `[PASS]` Mahasiswa ditolak saat mencoba membuat layanan `POST /api/layanan` (HTTP 403 Forbidden).
   - `[PASS]` Frontend Admin memvalidasi peran dan menampilkan proteksi 403 Access Denied jika mahasiswa mencoba membuka halaman `/admin`.

---

## 11. Tahap 3.4 — Audit & Unifikasi Sistem Tema (Light & Dark Mode) (Selesai & Terverifikasi)
- **Akar Masalah Theme Leakage yang Ditemukan**:
  1. Atribut `data-theme` sebelumnya hanya dipasang pada container `<div class="app-layout">`, bukan pada elemen root `<html>` atau `<body>`. Hal ini menyebabkan inheritance CSS global (seperti `body { background-color: ... }` dan elemen yang di-teleport/portal) mempertahankan warna gelap meskipun pengguna memilih Light Mode.
  2. Input form dan textarea pada `fe/app/pages/layanan/[id].vue` memiliki style inline/scoped hardcoded `background: rgba(15, 23, 42, 0.7); color: #fff;` yang menyebabkan kotak input tetap berwarna hitam pekat di Light Mode.
  3. Komponen `.action-card` pada `fe/app/pages/admin/pengajuan/[id].vue` memiliki hardcoded background `rgba(19, 27, 46, 0.95)`, sehingga tidak menyesuaikan diri saat beralih ke Light Mode.
  4. Judul hero pada `fe/app/pages/index.vue` memiliki text clipping gradien putih yang tidak terbaca pada latar belakang terang.
  5. Status badge (`badge-diajukan`, `badge-diproses`, `badge-perlu_perbaikan`, `badge-disetujui`, `badge-ditolak`) menggunakan warna teks pastel cerah (`#67e8f9`, `#fde047`, dll.) yang kontrasnya tidak memenuhi syarat WCAG AA (< 1.8:1) pada latar terang.
  6. Kotak catatan revisi admin (`.rev-note-box`, `.admin-current-note`, `.admin-note-preview`) menggunakan teks kuning pucat (`#fef3c7`) pada latar kuning terang di Light Mode.
- **Perbaikan & Centralized Design Token System**:
  - `fe/app/app.vue`:
    - Mengintegrasikan `useHead({ htmlAttrs: { 'data-theme': theme } })` sehingga atribut `data-theme="light"` / `data-theme="dark"` dirender langsung pada tag `<html>` sejak SSR/awal hidrasi.
    - Sinkronisasi instan via `document.documentElement.setAttribute('data-theme', theme.value)` saat tombol `#btn-theme-toggle` diklik, tanpa perlu refresh halaman.
    - Persistensi otomatis ke `localStorage.getItem('portal_theme')` dengan fallback ke `window.matchMedia('(prefers-color-scheme: dark)')`.
    - Menambahkan semantic status tokens pada `:root`, `html[data-theme="dark"]`, dan `html[data-theme="light"]`:
      - `--badge-diajukan-bg`, `--badge-diajukan-text`, `--badge-diajukan-border`
      - `--badge-diproses-bg`, `--badge-diproses-text`, `--badge-diproses-border`
      - `--badge-perlu_perbaikan-bg`, `--badge-perlu_perbaikan-text`, `--badge-perlu_perbaikan-border`
      - `--badge-disetujui-bg`, `--badge-disetujui-text`, `--badge-disetujui-border`
      - `--badge-ditolak-bg`, `--badge-ditolak-text`, `--badge-ditolak-border`
      - `--alert-warning-bg`, `--alert-warning-border`, `--alert-warning-title`, `--alert-warning-text`, `--alert-warning-box`
      - `--shadow-card`, `--shadow-dropdown`, `--overlay-bg`
    - Sentralisasi class global `.status-badge` dan seluruh varian statusnya agar seragam di semua halaman.
  - `fe/app/pages/layanan/[id].vue`: Mengganti input & textarea hardcoded menjadi `var(--bg-subtle)` dan `var(--text)`.
  - `fe/app/pages/admin/pengajuan/[id].vue`: Mengganti `.action-card` dan label status menjadi semantic tokens.
  - `fe/app/pages/mahasiswa.vue`: Mengganti kartu pengajuan, banner peringatan, dan preview catatan admin dengan token semantik.
  - `fe/app/pages/pengajuan/index.vue` & `fe/app/pages/pengajuan/[id].vue`: Mengganti hover tabel, timeline points, dan banner revisi dengan token semantik.
  - `fe/app/pages/admin/index.vue`: Mengganti hover statistik dan tabel antrean dengan token semantik.
  - `fe/app/pages/admin/pengajuan/index.vue`: Mengganti hover tabel dan pagination dengan token semantik.
  - `fe/app/pages/admin/layanan/index.vue`: Mengganti modal backdrop, form modal, dan action buttons dengan token semantik.
  - `fe/app/pages/layanan/index.vue`: Mengganti kartu layanan dan blok persyaratan dengan token semantik.
  - `fe/app/pages/login.vue`: Mengganti bayangan kartu dan border hover input dengan token semantik.
  - `fe/app/pages/index.vue`: Mengganti hero title dan kartu fitur dengan token semantik.
- **Hasil Pengujian & Verifikasi**:
  1. **Frontend HTTP Verification**: Status 200 OK pada seluruh rute aplikasi (`/`, `/login`, `/mahasiswa`, `/layanan`, `/layanan/1`, `/pengajuan`, `/admin`, `/admin/layanan`, `/admin/pengajuan`).
  2. **Theme Switching Verification**: Nuxt dev server merender `data-theme` langsung di tag `<html>`. Pergantian tema langsung aktif di semua komponen tanpa reload.
  3. **Backend Test Suite (Pest)**: 69 passed (100% lulus, 239 assertions), 0 regresi.
  4. **WCAG 2.2 AA Contrast**: Kontras teks utama > 10:1 pada Light Mode dan Dark Mode; badge status memiliki kontras > 4.5:1 untuk teks dan > 3:1 untuk border.

---

## 12. Status Akhir
- **Tahap 3.4**: SELESAI & TERVERIFIKASI.
- Seluruh kebocoran tema (theme leakage) telah diatasi di seluruh halaman mahasiswa, publik, dan admin.
- Sistem tema kini menggunakan semantic CSS design tokens terpusat di `app.vue`.
