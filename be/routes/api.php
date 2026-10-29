<?php

use App\Models\User;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\PengajuanController;
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
                'stats' => [
                    'total_pengajuan' => \App\Models\Pengajuan::count(),
                    'diajukan' => \App\Models\Pengajuan::where('status', \App\Models\Pengajuan::STATUS_DIAJUKAN)->count(),
                    'diproses' => \App\Models\Pengajuan::where('status', \App\Models\Pengajuan::STATUS_DIPROSES)->count(),
                    'perlu_perbaikan' => \App\Models\Pengajuan::where('status', \App\Models\Pengajuan::STATUS_PERLU_PERBAIKAN)->count(),
                    'disetujui' => \App\Models\Pengajuan::where('status', \App\Models\Pengajuan::STATUS_DISETUJUI)->count(),
                    'ditolak' => \App\Models\Pengajuan::where('status', \App\Models\Pengajuan::STATUS_DITOLAK)->count(),
                    'total_layanan' => \App\Models\Layanan::count(),
                    'layanan_aktif' => \App\Models\Layanan::where('is_aktif', true)->count(),
                    'total_mahasiswa' => \App\Models\User::where('role', 'mahasiswa')->count(),
                ],
            ]);
        });
    });
});
// Layanan CRUD routes
// Public endpoints (list and detail) – show only active services
Route::get('/layanan', [LayananController::class, 'index']);
Route::get('/layanan/{layanan}', [LayananController::class, 'show']);

// Admin‑protected endpoints – require Sanctum auth and admin role
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/layanan', [LayananController::class, 'store']);
    Route::match(['put', 'patch'], '/layanan/{layanan}', [LayananController::class, 'update']);
    Route::delete('/layanan/{layanan}', [LayananController::class, 'destroy']);

    // Pengajuan routes – mahasiswa own & admin CRUD
    // Mahasiswa (own) – list, detail, create
    Route::post('/pengajuan', [PengajuanController::class, 'store']);
    Route::get('/pengajuan/mine', [PengajuanController::class, 'myIndex']);
    Route::get('/pengajuan/mine/{pengajuan}', [PengajuanController::class, 'myShow']);

    // Admin – full CRUD & status change
    Route::get('/pengajuan', [PengajuanController::class, 'index']);
    Route::get('/pengajuan/{pengajuan}', [PengajuanController::class, 'show']);
    Route::match(['put', 'patch'], '/pengajuan/{pengajuan}', [PengajuanController::class, 'update']);
    Route::delete('/pengajuan/{pengajuan}', [PengajuanController::class, 'destroy']);
    Route::post('/pengajuan/{pengajuan}/status', [PengajuanController::class, 'changeStatus']);
});

Route::middleware(['auth:sanctum', 'role:mahasiswa'])->group(function () {
    Route::post('/pengajuan', [PengajuanController::class, 'store']);
    Route::get('/pengajuan/mine', [PengajuanController::class, 'myIndex']);
    Route::get('/pengajuan/mine/{pengajuan}', [PengajuanController::class, 'myShow']);
});
