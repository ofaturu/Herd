<?php

use App\Models\Layanan;
use App\Models\Pengajuan;
use App\Models\RiwayatStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Seeders\PortalLayananMahasiswaSeeder;

beforeEach(function () {
    $this->seed(PortalLayananMahasiswaSeeder::class);
});

test('user memiliki relasi hasMany ke pengajuan dan riwayatStatus', function () {
    $mahasiswa = User::where('email', 'budi.santoso@mahasiswa.test')->first();
    $admin = User::where('email', 'admin@portal.test')->first();

    expect($mahasiswa->pengajuan)->not->toBeEmpty();
    expect($mahasiswa->pengajuan->first())->toBeInstanceOf(Pengajuan::class);

    // Admin mencatat beberapa aksi riwayat status
    expect($admin->riwayatStatus)->not->toBeEmpty();
    expect($admin->riwayatStatus->first())->toBeInstanceOf(RiwayatStatus::class);
    expect($admin->isAdmin())->toBeTrue();
    expect($mahasiswa->isMahasiswa())->toBeTrue();
});

test('layanan memiliki relasi hasMany ke pengajuan', function () {
    $layanan = Layanan::where('kode', 'SRT-AKTIF')->first();

    expect($layanan->pengajuan)->not->toBeEmpty();
    expect($layanan->pengajuan->first())->toBeInstanceOf(Pengajuan::class);
    expect($layanan->persyaratan)->toBeArray();
    expect($layanan->is_aktif)->toBeTrue();
});

test('pengajuan memiliki relasi belongsTo ke user/mahasiswa dan layanan, serta hasMany ke riwayatStatus', function () {
    $pengajuan = Pengajuan::where('nomor_pengajuan', 'REQ-202610-0001')->first();

    // belongsTo User & Mahasiswa
    expect($pengajuan->user)->toBeInstanceOf(User::class);
    expect($pengajuan->user->id)->toBe($pengajuan->user_id);
    expect($pengajuan->mahasiswa)->toBeInstanceOf(User::class);
    expect($pengajuan->mahasiswa->email)->toBe('budi.santoso@mahasiswa.test');

    // belongsTo Layanan
    expect($pengajuan->layanan)->toBeInstanceOf(Layanan::class);
    expect($pengajuan->layanan->id)->toBe($pengajuan->layanan_id);

    // hasMany RiwayatStatus
    expect($pengajuan->riwayatStatus)->toHaveCount(3);
    expect($pengajuan->riwayatStatus->first())->toBeInstanceOf(RiwayatStatus::class);
    expect($pengajuan->dokumen_pendukung)->toBeArray();
    expect($pengajuan->tanggal_pengajuan)->toBeInstanceOf(CarbonInterface::class);
});

test('riwayat_status memiliki relasi belongsTo ke pengajuan dan pelaku/user', function () {
    $riwayat = RiwayatStatus::first();

    expect($riwayat->pengajuan)->toBeInstanceOf(Pengajuan::class);
    expect($riwayat->user)->toBeInstanceOf(User::class);
    expect($riwayat->pelaku)->toBeInstanceOf(User::class);
    expect($riwayat->waktu)->toBeInstanceOf(CarbonInterface::class);
});

test('field sensitif user disembunyikan saat serialisasi model ke array atau json', function () {
    $user = User::first();
    $serializedArray = $user->toArray();
    $serializedJson = $user->toJson();

    expect(array_key_exists('password', $serializedArray))->toBeFalse();
    expect(array_key_exists('remember_token', $serializedArray))->toBeFalse();
    expect(array_key_exists('two_factor_secret', $serializedArray))->toBeFalse();
    expect(str_contains($serializedJson, 'password'))->toBeFalse();
});
