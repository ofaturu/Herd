# Status Proyek: Portal Layanan Mahasiswa

## 1. Ringkasan Proyek
- **Backend**: Laravel 13.37.0 (PHP 8.5.11, Laravel Herd), REST API, MySQL (`portal_layanan_mahasiswa`), Pest
- **Frontend**: Nuxt 4 (Vue 3.5.44 + Vite), Node v26.12.0, npm 11.20.0
- **Testing**: Pest (Backend: 44 passed, 153 assertions), API key curl test passed

---

## 2. Status Saat Ini
- **Tahap**: 1.2 DATABASE LARAVEL (Selesai)
- **Status**: Database MySQL `portal_layanan_mahasiswa` telah dikonfigurasi dan diuji. Seluruh migrasi (`users`, `layanan`, `pengajuan`, `riwayat_status`), model Eloquent berelasi, data seeder simulasi, dan suite pengujian integrasi skema (Pest) telah 100% lulus.

---

## 3. Log Perubahan File
1. `be/.env`: Dialihkan ke driver MySQL (`DB_CONNECTION=mysql`, `DB_DATABASE=portal_layanan_mahasiswa`, host `127.0.0.1:3306`).
2. `be/app/Models/User.php`: Penambahan field `role`, `nim`, `prodi`, `no_hp` ke `$fillable`, serta relasi `hasMany` ke `Pengajuan`.
3. `be/app/Models/Layanan.php`: Model Eloquent untuk master layanan dengan relasi `hasMany` ke `Pengajuan` dan casts array untuk persyaratan.
4. `be/app/Models/Pengajuan.php`: Model pengajuan dengan konstanta status valid, relasi ke `User` (mahasiswa), `Layanan`, dan `RiwayatStatus`.
5. `be/app/Models/RiwayatStatus.php`: Model riwayat perubahan status pengajuan dengan relasi ke `Pengajuan` dan `User` (pelaku).
6. `be/database/migrations/2026_10_22_073258_add_portal_columns_to_users_table.php`: Migrasi kolom `role`, `nim`, `prodi`, `no_hp` pada tabel `users`.
7. `be/database/migrations/2026_10_22_073303_create_layanan_table.php`: Migrasi tabel master `layanan`.
8. `be/database/migrations/2026_10_22_073304_create_pengajuan_table.php`: Migrasi tabel `pengajuan` dengan `nomor_pengajuan` unik, foreign keys, status enum, dan indeks.
9. `be/database/migrations/2026_10_22_073305_create_riwayat_status_table.php`: Migrasi tabel `riwayat_status` dengan foreign keys dan timestamp index.
10. `be/database/seeders/PortalLayananMahasiswaSeeder.php`: Seeder untuk 1 admin, 2 mahasiswa, 4 katalog layanan, serta 2 skenario pengajuan lengkap beserta riwayat statusnya (password di-hash dengan `Hash::make`).
11. `be/tests/Feature/DatabasePortalSchemaTest.php`: Feature test pengujian keutuhan skema tabel, keunikan nomor pengajuan, enum status, dan relasi model.
12. `PROJECT_STATUS.md`: Pembaruan status berkala.

---

## 4. Eksekusi Perintah & Hasil Pengujian
1. **Konfigurasi Database MySQL**:
   - Pembuatan schema MySQL `portal_layanan_mahasiswa` (utf8mb4).
   - `.env` diperbarui ke `DB_CONNECTION=mysql` dan port `3306`.
2. **Migrasi**:
   - Perintah: `php artisan migrate`
   - Hasil: Baseline migrations + 4 migration portal baru sukses dieksekusi tanpa error.
   - Status per batch tercatat rapi pada tabel `migrations`.
3. **Seeding Data Simulasi**:
   - Perintah: `php artisan db:seed --class=PortalLayananMahasiswaSeeder`
   - Hasil: 3 users (1 admin, 2 mahasiswa), 4 layanan, 2 pengajuan, dan 4 log riwayat status terisi.
4. **Pest Feature & Unit Tests**:
   - Perintah: `php artisan test`
   - Hasil Aktual: `tests: 44, passed: 44, assertions: 153, duration: 2.91s` (100% Lulus, 0 failure).

---

## 5. Ringkasan Skema & Relasi Database
- **Tabel `users`**:
  - Kolom tambahan: `role` (enum/string: admin, mahasiswa), `nim` (unique), `prodi`, `no_hp`.
- **Tabel `layanan`**:
  - Kolom: `id`, `kode` (unique), `nama`, `deskripsi`, `persyaratan` (json), `estimasi_hari`, `is_aktif` (indexed), `timestamps`.
- **Tabel `pengajuan`**:
  - Kolom: `id`, `nomor_pengajuan` (unique), `user_id` (FK `users`, cascade), `layanan_id` (FK `layanan`, restrict), `tanggal_pengajuan` (indexed), `status` (enum: diajukan, diproses, perlu_perbaikan, disetujui, ditolak), `keterangan`, `catatan_admin`, `dokumen_pendukung` (json), `timestamps`.
  - Indeks komposit: `(user_id, status)`.
- **Tabel `riwayat_status`**:
  - Kolom: `id`, `pengajuan_id` (FK `pengajuan`, cascade), `user_id` (FK `users`, nullOnDelete - pencatat/pelaku), `status_sebelumnya`, `status_baru` (enum), `catatan`, `waktu` (timestamp indexed), `timestamps`.
  - Indeks komposit: `(pengajuan_id, waktu)`.

---

## 6. Masalah Tersisa / Kendala Lingkungan
- Tidak ada. Semua pengujian database dan Laravel berjalan stabil.

---

## 7. Langkah Selanjutnya
- Menunggu instruksi untuk tahap berikutnya (misal: Tahap 1.3 Pembuatan REST API Controller, Resource, Validasi Request, dan Endpoint Postman/Curl).
