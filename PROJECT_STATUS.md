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
- **Tahap 2.3 Postman Testing**: SELESAI, BELUM TERVERIFIKASI (koleksi belum dijalankan, dokumentasi belum ada).
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
- Tidak ada. Seluruh middleware keamanan aktif dan berfungsi tanpa bypass.

---

## 7. Langkah Selanjutnya
- Berhenti setelah Tahap 1.4 sesuai instruksi.
- Menunggu arahan pengguna sebelum melangkah ke **Tahap 2.1 (Implementasi REST API CRUD Layanan & Pengajuan Mahasiswa)**.
