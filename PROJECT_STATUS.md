# Status Proyek: Portal Layanan Mahasiswa

## 1. Ringkasan Proyek
- **Backend**: Laravel 13.37.0 (PHP 8.5.11, Laravel Herd), REST API, MySQL (`portal_layanan_mahasiswa`), Pest, Laravel Pint
- **Frontend**: Nuxt 4 (Vue 3.5.44 + Vite), Node v26.12.0, npm 11.20.0
- **Testing**: Pest (Backend: 49 passed, 181 assertions), Pint test (Models clean)

---

## 2. Status Saat Ini
- **Tahap**: 1.3 MODEL DAN RELASI ELOQUENT (Selesai)
- **Status**: Seluruh model Eloquent (`User`, `Layanan`, `Pengajuan`, `RiwayatStatus`) telah diimplementasikan dan diaudit sesuai struktur database MySQL aktual. Relasi bidirectional, keamanan atribut sensitif (`$hidden`), casting JSON/Date, dan 5 skenario test relasi model telah 100% lulus.

---

## 3. Log Perubahan File
1. `be/app/Models/User.php`:
   - Menambahkan relasi `pengajuan(): HasMany` dan `riwayatStatus(): HasMany`.
   - Menambahkan konstanta role (`ROLE_ADMIN`, `ROLE_MAHASISWA`) dan helper method `isAdmin()` / `isMahasiswa()`.
   - Mengamankan atribut sensitif (`password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes`) pada array `$hidden`.
   - Menyesuaikan formatting standar Pint.
2. `be/app/Models/Layanan.php`:
   - Menambahkan relasi `pengajuan(): HasMany`.
   - Menambahkan query scope `scopeAktif(Builder $query)`.
   - Casts: `persyaratan` (array), `estimasi_hari` (integer), `is_aktif` (boolean).
3. `be/app/Models/Pengajuan.php`:
   - Menambahkan relasi `user(): BelongsTo`, alias `mahasiswa(): BelongsTo`.
   - Menambahkan relasi `layanan(): BelongsTo`.
   - Menambahkan relasi `riwayatStatus(): HasMany` (diurutkan berdasarkan `waktu asc`).
   - Menambahkan konstanta status valid dan helper `scopeStatus(Builder $query, string $status)`.
   - Casts: `tanggal_pengajuan` (date), `dokumen_pendukung` (array).
   - Menyesuaikan formatting standar Pint.
4. `be/app/Models/RiwayatStatus.php`:
   - Menambahkan relasi `pengajuan(): BelongsTo`.
   - Menambahkan relasi `user(): BelongsTo`, alias `pelaku(): BelongsTo`.
   - Casts: `waktu` (datetime).
5. `be/tests/Feature/EloquentModelRelationshipTest.php`:
   - Test suite baru untuk memvalidasi:
     1. Relasi `User` `hasMany` `Pengajuan` dan `RiwayatStatus`, serta role helpers.
     2. Relasi `Layanan` `hasMany` `Pengajuan` dan cast JSON `persyaratan`.
     3. Relasi `Pengajuan` `belongsTo` `User`/`Mahasiswa`, `belongsTo` `Layanan`, dan `hasMany` `RiwayatStatus`.
     4. Relasi `RiwayatStatus` `belongsTo` `Pengajuan` dan `belongsTo` `User`/`Pelaku`.
     5. Keamanan atribut sensitif user saat diserialisasi ke array atau JSON (password tidak bocor).
6. `PROJECT_STATUS.md`: Pembaruan status berkala.

---

## 4. Eksekusi Perintah & Hasil Pengujian
1. **Analisis Format Kode**:
   - Perintah: `vendor\bin\pint app/Models` & `vendor\bin\pint --test app/Models`
   - Hasil Aktual: `{"tool":"pint","result":"passed"}` (100% compliant).
2. **Pengujian Relasi Model Eloquent**:
   - Perintah: `php artisan test tests/Feature/EloquentModelRelationshipTest.php`
   - Hasil Aktual: `tests: 5, passed: 5, assertions: 28, duration: 408ms` (100% Lulus).
3. **Pengujian Keseluruhan Backend Suite**:
   - Perintah: `php artisan test`
   - Hasil Aktual: `tests: 49, passed: 49, assertions: 181, duration: 2.97s` (100% Lulus, 0 failure).

---

## 5. Matriks Model & Relasi yang Terverifikasi

| Model | Relasi Method | Tipe Relasi | Target Model | Foreign Key | Keterangan |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `User` | `pengajuan()` | `HasMany` | `Pengajuan` | `user_id` | Daftar pengajuan yang dimiliki mahasiswa |
| `User` | `riwayatStatus()` | `HasMany` | `RiwayatStatus` | `user_id` | Log aksi/perubahan status yang dilakukan user/admin |
| `Layanan` | `pengajuan()` | `HasMany` | `Pengajuan` | `layanan_id` | Daftar pengajuan untuk jenis layanan ini |
| `Pengajuan` | `user()` / `mahasiswa()` | `BelongsTo` | `User` | `user_id` | Mahasiswa pemilik pengajuan |
| `Pengajuan` | `layanan()` | `BelongsTo` | `Layanan` | `layanan_id` | Layanan akademik/kemahasiswaan terkait |
| `Pengajuan` | `riwayatStatus()` | `HasMany` | `RiwayatStatus` | `pengajuan_id` | Seluruh catatan riwayat status pengajuan |
| `RiwayatStatus` | `pengajuan()` | `BelongsTo` | `Pengajuan` | `pengajuan_id` | Pengajuan yang statusnya berubah |
| `RiwayatStatus` | `user()` / `pelaku()` | `BelongsTo` | `User` | `user_id` | User/Admin yang mengubah status |

---

## 6. Masalah Tersisa / Kendala Lingkungan
- Tidak ada. Database tidak diubah/dihapus, dan semua relasi terverifikasi lulus uji.

---

## 7. Langkah Selanjutnya
- Berhenti setelah Tahap 1.3 sesuai instruksi.
- Menunggu instruksi berikutnya dari pengguna (misal: Tahap pembuatan REST API Controller, Resource, Request Validation, atau rute API).
