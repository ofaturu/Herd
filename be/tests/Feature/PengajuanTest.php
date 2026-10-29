<?php

use App\Models\Pengajuan;
use App\Models\Layanan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;
if (!function_exists('adminUser')) {
    function adminUser()
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }
}

if (!function_exists('mahasiswaUser')) {
    function mahasiswaUser()
    {
        return User::factory()->create(['role' => User::ROLE_MAHASISWA]);
    }
}



it('mahasiswa can create pengajuan for active layanan', function () {
    $mahasiswa = mahasiswaUser();
    actingAs($mahasiswa);
    $layanan = Layanan::factory()->create(['is_aktif' => true]);
    $payload = [
        'layanan_id' => $layanan->id,
        'keterangan' => 'Butuh bantuan',
        'dokumen_pendukung' => [],
    ];
    postJson('/api/pengajuan', $payload)
        ->assertStatus(201)
        ->assertJsonPath('data.layanan_id', $layanan->id)
        ->assertJsonPath('data.user_id', $mahasiswa->id);
    expect(Pengajuan::where('layanan_id', $layanan->id)->exists())->toBeTrue();
});

it('mahasiswa cannot create pengajuan for inactive layanan', function () {
    $mahasiswa = mahasiswaUser();
    actingAs($mahasiswa);
    $layanan = Layanan::factory()->create(['is_aktif' => false]);
    $payload = ['layanan_id' => $layanan->id];
    postJson('/api/pengajuan', $payload)->assertStatus(422);
});

it('admin can list all pengajuan with status filter', function () {
    $admin = adminUser();
    actingAs($admin);
    $layanan = Layanan::factory()->create(['is_aktif' => true]);
    // create two pengajuan with different status
    Pengajuan::factory()->create(['layanan_id' => $layanan->id, 'status' => Pengajuan::STATUS_DIAJUKAN]);
    Pengajuan::factory()->create(['layanan_id' => $layanan->id, 'status' => Pengajuan::STATUS_DIPROSES]);
    // filter by status
    getJson('/api/pengajuan?status=' . Pengajuan::STATUS_DIPROSES)
        ->assertStatus(200)
        ->assertJsonPath('data.0.status', Pengajuan::STATUS_DIPROSES);
});

it('mahasiswa can view own pengajuan but not others', function () {
    $mahasiswa1 = mahasiswaUser();
    $mahasiswa2 = mahasiswaUser();
    actingAs($mahasiswa1);
    $layanan = Layanan::factory()->create(['is_aktif' => true]);
    $pengajuan = Pengajuan::factory()->create(['layanan_id' => $layanan->id, 'user_id' => $mahasiswa1->id]);
    // own view
    getJson('/api/pengajuan/mine/' . $pengajuan->id)->assertStatus(200);
    // other user
    actingAs($mahasiswa2);
    getJson('/api/pengajuan/mine/' . $pengajuan->id)->assertStatus(404);
});

it('admin can change status and creates riwayat_status', function () {
    $admin = adminUser();
    actingAs($admin);
    $layanan = Layanan::factory()->create(['is_aktif' => true]);
    $pengajuan = Pengajuan::factory()->create(['layanan_id' => $layanan->id, 'status' => Pengajuan::STATUS_DIAJUKAN]);
    $payload = ['status_baru' => Pengajuan::STATUS_DIPROSES, 'catatan' => 'Sedang diproses'];
    postJson('/api/pengajuan/' . $pengajuan->id . '/status', $payload)
        ->assertStatus(200)
        ->assertJsonPath('message', 'Status pengajuan berhasil diubah.');
    $pengajuan->refresh();
    expect($pengajuan->status)->toBe(Pengajuan::STATUS_DIPROSES);
    expect($pengajuan->riwayatStatus()->count())->toBe(1);
});
?>
