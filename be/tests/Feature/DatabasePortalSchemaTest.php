<?php

use App\Models\Layanan;
use App\Models\Pengajuan;
use App\Models\User;
use Database\Seeders\PortalLayananMahasiswaSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('tabel portal layanan mahasiswa memiliki kolom dan struktur yang tepat', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasColumns('users', ['id', 'name', 'email', 'password', 'role', 'nim', 'prodi', 'no_hp']))->toBeTrue();

    expect(Schema::hasTable('layanan'))->toBeTrue();
    expect(Schema::hasColumns('layanan', ['id', 'kode', 'nama', 'deskripsi', 'persyaratan', 'estimasi_hari', 'is_aktif']))->toBeTrue();

    expect(Schema::hasTable('pengajuan'))->toBeTrue();
    expect(Schema::hasColumns('pengajuan', ['id', 'nomor_pengajuan', 'user_id', 'layanan_id', 'tanggal_pengajuan', 'status', 'keterangan', 'catatan_admin', 'dokumen_pendukung']))->toBeTrue();

    expect(Schema::hasTable('riwayat_status'))->toBeTrue();
    expect(Schema::hasColumns('riwayat_status', ['id', 'pengajuan_id', 'user_id', 'status_sebelumnya', 'status_baru', 'catatan', 'waktu']))->toBeTrue();
});

test('relasi model portal mahasiswa berfungsi dengan benar menggunakan seeder', function () {
    $this->seed(PortalLayananMahasiswaSeeder::class);

    $pengajuan = Pengajuan::where('nomor_pengajuan', 'REQ-202610-0001')->first();

    expect($pengajuan)->not->toBeNull();
    expect($pengajuan->mahasiswa)->toBeInstanceOf(User::class);
    expect($pengajuan->layanan)->toBeInstanceOf(Layanan::class);
    expect($pengajuan->riwayatStatus)->not->toBeEmpty();
    expect($pengajuan->riwayatStatus->first()->pengajuan->id)->toBe($pengajuan->id);
});

test('nomor_pengajuan unik mencegah duplikasi', function () {
    $this->seed(PortalLayananMahasiswaSeeder::class);
    $existing = Pengajuan::first();

    expect(function () use ($existing) {
        Pengajuan::create([
            'nomor_pengajuan' => $existing->nomor_pengajuan,
            'user_id' => $existing->user_id,
            'layanan_id' => $existing->layanan_id,
            'tanggal_pengajuan' => now()->toDateString(),
            'status' => Pengajuan::STATUS_DIAJUKAN,
        ]);
    })->toThrow(QueryException::class);
});

test('status pengajuan mematuhi nilai yang diizinkan', function () {
    expect(Pengajuan::VALID_STATUSES)->toEqual([
        'diajukan',
        'diproses',
        'perlu_perbaikan',
        'disetujui',
        'ditolak',
    ]);
});
