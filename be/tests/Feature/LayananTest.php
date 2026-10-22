<?php

use App\Models\Layanan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\actingAs;

function adminUser()
{
    return User::factory()->create(['role' => 'admin']);
}

function mahasiswaUser()
{
    return User::factory()->create(['role' => 'mahasiswa']);
}

it('guest cannot create layanan', function () {
    $payload = [
        'kode' => 'L001',
        'nama' => 'Layanan Test',
        'estimasi_hari' => 5,
    ];
    postJson('/api/layanan', $payload)->assertStatus(401);
});

it('non-admin cannot create layanan', function () {
    $user = mahasiswaUser();
    Sanctum::actingAs($user, ['*']);
    $payload = [
        'kode' => 'L002',
        'nama' => 'Layanan Test 2',
        'estimasi_hari' => 3,
    ];
    postJson('/api/layanan', $payload)->assertStatus(403);
});

it('admin can create layanan', function () {
    $admin = adminUser();
    Sanctum::actingAs($admin, ['*']);
    $payload = [
        'kode' => 'L003',
        'nama' => 'Layanan Admin',
        'estimasi_hari' => 7,
        'is_aktif' => true,
    ];
    postJson('/api/layanan', $payload)
        ->assertStatus(201)
        ->assertJsonPath('data.kode', 'L003');
    expect(Layanan::where('kode', 'L003')->exists())->toBeTrue();
});

it('index returns only active layanan', function () {
    Layanan::factory()->create(['is_aktif' => true, 'kode' => 'ACTIVE1']);
    Layanan::factory()->create(['is_aktif' => false, 'kode' => 'INACTIVE1']);
    $response = getJson('/api/layanan')->assertStatus(200);
    $codes = collect($response->json('data'))->pluck('kode');
    expect($codes)->toContain('ACTIVE1');
    expect($codes)->not->toContain('INACTIVE1');
});

it('show returns 404 for inactive layanan', function () {
    $layanan = Layanan::factory()->create(['is_aktif' => false]);
    getJson('/api/layanan/'.$layanan->id)->assertStatus(404);
});

it('admin can update layanan', function () {
    $admin = adminUser();
    Sanctum::actingAs($admin, ['*']);
    $layanan = Layanan::factory()->create(['nama' => 'Old Name']);
    $payload = ['nama' => 'New Name'];
    putJson('/api/layanan/'.$layanan->id, $payload)
        ->assertStatus(200)
        ->assertJsonPath('data.nama', 'New Name');
    expect($layanan->fresh()->nama)->toBe('New Name');
});

it('admin can deactivate layanan', function () {
    $admin = adminUser();
    Sanctum::actingAs($admin, ['*']);
    $layanan = Layanan::factory()->create(['is_aktif' => true]);
    deleteJson('/api/layanan/'.$layanan->id)->assertStatus(200);
    expect($layanan->fresh()->is_aktif)->toBeFalse();
});
