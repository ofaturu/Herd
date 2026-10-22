<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 1. Endpoint Tooling / Postman: dilindungi middleware 'api.key' (header X-API-KEY).
| 2. Endpoint Autentikasi Pengguna: menggunakan Sanctum token / session.
| 3. Endpoint Hak Akses Role: dilindungi middleware 'auth:sanctum' + 'role:admin,mahasiswa'.
|
*/

// Group API Key untuk pengujian Postman / automated tooling
Route::middleware(['api.key', 'throttle:60,1'])->group(function () {
    Route::get('/data', function () {
        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengakses data backend dengan API Key!',
            'data' => [
                'item1' => 'Laptop',
                'item2' => 'Mouse',
            ],
        ]);
    });

    // Uji proteksi API Key fondasi
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'API Key valid. Layanan backend siap.',
            'timestamp' => now()->toIso8601String(),
        ]);
    });
});

// Endpoint Autentikasi Frontend (Nuxt SPA / API Client)
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! auth()->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 422);
        }

        /** @var User $user */
        $user = auth()->user();
        $token = $user->createToken('portal_auth_token', [$user->role])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'nim' => $user->nim,
                    'prodi' => $user->prodi,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    });
});

// Endpoint yang membutuhkan User Authentication & Role Authorization
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/user/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
                'nim' => $request->user()->nim,
                'prodi' => $request->user()->prodi,
            ],
        ]);
    });

    Route::post('/auth/logout', function (Request $request) {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout.',
        ]);
    });

    // Fondasi Otorisasi Mahasiswa
    Route::middleware(['role:mahasiswa'])->prefix('mahasiswa')->group(function () {
        Route::get('/dashboard', function (Request $request) {
            return response()->json([
                'success' => true,
                'message' => 'Akses dashboard mahasiswa diizinkan.',
                'user' => $request->user()->only(['id', 'name', 'nim', 'role']),
            ]);
        });
    });

    // Fondasi Otorisasi Admin
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', function (Request $request) {
            return response()->json([
                'success' => true,
                'message' => 'Akses dashboard admin diizinkan.',
                'user' => $request->user()->only(['id', 'name', 'role']),
            ]);
        });
    });
});
