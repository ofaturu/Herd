<?php

use App\Models\User;
use Database\Seeders\PortalLayananMahasiswaSeeder;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(PortalLayananMahasiswaSeeder::class);
    Config::set('services.api_key', 'TEST_SECRET_API_KEY_999');
});

test('api key middleware menolak request jika header X-API-KEY tidak disertakan', function () {
    $response = $this->getJson('/api/data');

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthorized: API Key tidak valid atau tidak disertakan.',
        ]);
});

test('api key middleware menolak request jika header X-API-KEY salah', function () {
    $response = $this->withHeader('X-API-KEY', 'WRONG_INVALID_KEY')
        ->getJson('/api/data');

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthorized: API Key tidak valid atau tidak disertakan.',
        ]);
});

test('api key middleware meloloskan request jika header X-API-KEY benar', function () {
    $response = $this->withHeader('X-API-KEY', 'TEST_SECRET_API_KEY_999')
        ->getJson('/api/data');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

test('pengguna belum login ditolak saat mengakses endpoint terproteksi sanctum', function () {
    $response = $this->getJson('/api/user/me');

    $response->assertStatus(401);
});

test('login dengan kredensial valid menghasilkan token sanctum dan informasi profil', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'budi.santoso@mahasiswa.test',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'role', 'nim', 'prodi'],
                'token',
                'token_type',
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'user' => [
                    'email' => 'budi.santoso@mahasiswa.test',
                    'role' => 'mahasiswa',
                ],
                'token_type' => 'Bearer',
            ],
        ]);
});

test('login dengan password salah ditolak dengan status 422', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'budi.santoso@mahasiswa.test',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Email atau password salah.',
        ]);
});

test('mahasiswa diizinkan mengakses rute mahasiswa namun dilarang mengakses rute admin', function () {
    $mahasiswa = User::where('email', 'budi.santoso@mahasiswa.test')->first();
    Sanctum::actingAs($mahasiswa, ['mahasiswa']);

    // Akses rute mahasiswa -> 200 OK
    $resMahasiswa = $this->getJson('/api/mahasiswa/dashboard');
    $resMahasiswa->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Akses dashboard mahasiswa diizinkan.',
        ]);

    // Akses rute admin -> 403 Forbidden
    $resAdmin = $this->getJson('/api/admin/dashboard');
    $resAdmin->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Forbidden: Anda tidak memiliki hak akses untuk tindakan ini.',
        ]);
});

test('admin diizinkan mengakses rute admin namun dilarang mengakses rute khusus mahasiswa', function () {
    $admin = User::where('email', 'admin@portal.test')->first();
    Sanctum::actingAs($admin, ['admin']);

    // Akses rute admin -> 200 OK
    $resAdmin = $this->getJson('/api/admin/dashboard');
    $resAdmin->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Akses dashboard admin diizinkan.',
        ]);

    // Akses rute mahasiswa -> 403 Forbidden
    $resMahasiswa = $this->getJson('/api/mahasiswa/dashboard');
    $resMahasiswa->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Forbidden: Anda tidak memiliki hak akses untuk tindakan ini.',
        ]);
});
