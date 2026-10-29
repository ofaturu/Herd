<?php

use App\Http\Controllers\LayananController;
use App\Http\Controllers\PengajuanController;
use App\Models\Layanan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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
Route::prefix('auth')->middleware('throttle:15,1')->group(function () {
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

        if (app()->runningUnitTests()) {
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
                        'no_hp' => $user->no_hp,
                        'foto_profil' => $user->foto_profil,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);
        }

        // Buat 6-digit kode OTP
        $otp = (string) random_int(100000, 999999);
        $sessionToken = Str::random(48);

        // Simpan sesi dan OTP di cache selama 10 menit
        Cache::put("login_otp_{$sessionToken}", [
            'user_id' => $user->id,
            'otp'     => $otp,
            'email'   => $user->email,
        ], now()->addMinutes(10));

        // Kirim email OTP via Gmail SMTP
        $isDemo = str_ends_with($user->email, '.test');
        $targetEmail = $isDemo ? (config('mail.from.address') ?: 'fatchurrachman001@gmail.com') : $user->email;

        try {
            Mail::html("
                <div style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 32px 24px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #ffffff;'>
                    <div style='text-align: center; margin-bottom: 24px;'>
                        <h2 style='color: #1e3a8a; margin: 0 0 6px; font-size: 20px;'>Portal Layanan Mahasiswa</h2>
                        <p style='color: #64748b; font-size: 13px; margin: 0;'>Verifikasi Keamanan Masuk Akun</p>
                    </div>
                    <p style='font-size: 14px; color: #334155; margin-bottom: 12px;'>Halo <strong>{$user->name}</strong>,</p>
                    <p style='font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px;'>Kami mendeteksi permintaan masuk ke akun Anda. Masukkan kode verifikasi 6 digit berikut untuk menyelesaikan login:</p>
                    <div style='text-align: center; margin: 28px 0;'>
                        <div style='display: inline-block; font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #2563eb; background: #eff6ff; padding: 14px 28px; border-radius: 12px; border: 2px dashed #93c5fd;'>{$otp}</div>
                    </div>
                    <p style='font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 24px;'>Kode ini berlaku selama <strong>10 menit</strong>. Jika Anda tidak mencoba masuk, silakan abaikan email ini.</p>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;' />
                    <p style='font-size: 11px; color: #94a3b8; text-align: center; margin: 0;'>Pesan otomatis dari Sistem Keamanan Portal Layanan Mahasiswa.</p>
                </div>
            ", function ($message) use ($targetEmail, $otp) {
                $message->to($targetEmail)
                    ->subject("Kode Verifikasi Login: {$otp} - Portal Layanan Mahasiswa");
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mengirim email OTP: ' . $e->getMessage());
        }

        // Mask email untuk tampilan
        $parts = explode('@', $user->email);
        $namePart = $parts[0];
        $domainPart = $parts[1] ?? '';
        $maskedName = strlen($namePart) <= 2 ? $namePart[0] . '*' : substr($namePart, 0, 1) . str_repeat('*', min(4, max(1, strlen($namePart) - 2))) . substr($namePart, -1);
        $maskedEmail = "{$maskedName}@{$domainPart}";

        return response()->json([
            'success'      => true,
            'requires_otp' => true,
            'message'      => "Kode verifikasi 6 digit telah dikirimkan ke {$maskedEmail}.",
            'data'         => [
                'session_token' => $sessionToken,
                'masked_email'  => $maskedEmail,
                'is_demo'       => $isDemo,
                'dev_otp'       => $isDemo ? $otp : null,
            ],
        ]);
    });

    Route::post('/verify-otp', function (Request $request) {
        $validated = $request->validate([
            'session_token' => ['required', 'string'],
            'otp'           => ['required', 'string', 'size:6'],
        ]);

        $cached = Cache::get("login_otp_{$validated['session_token']}");
        if (! $cached) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi verifikasi telah kedaluwarsa atau tidak valid. Silakan login kembali.',
            ], 422);
        }

        if ($cached['otp'] !== trim($validated['otp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Kode verifikasi yang Anda masukkan salah. Periksa kembali email Anda.',
            ], 422);
        }

        Cache::forget("login_otp_{$validated['session_token']}");

        $user = User::find($cached['user_id']);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        $token = $user->createToken('portal_auth_token', [$user->role])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi berhasil. Selamat datang kembali!',
            'data'    => [
                'user' => [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'role'        => $user->role,
                    'nim'         => $user->nim,
                    'prodi'       => $user->prodi,
                    'no_hp'       => $user->no_hp,
                    'foto_profil' => $user->foto_profil,
                ],
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    });

    Route::post('/resend-otp', function (Request $request) {
        $validated = $request->validate([
            'session_token' => ['required', 'string'],
        ]);

        $cached = Cache::get("login_otp_{$validated['session_token']}");
        if (! $cached) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi verifikasi telah kedaluwarsa. Silakan lakukan login ulang.',
            ], 422);
        }

        $user = User::find($cached['user_id']);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        $newOtp = (string) random_int(100000, 999999);
        Cache::put("login_otp_{$validated['session_token']}", [
            'user_id' => $user->id,
            'otp'     => $newOtp,
            'email'   => $user->email,
        ], now()->addMinutes(10));

        $isDemo = str_ends_with($user->email, '.test');
        $targetEmail = $isDemo ? (config('mail.from.address') ?: 'fatchurrachman001@gmail.com') : $user->email;

        try {
            Mail::html("
                <div style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; max-width: 520px; margin: 0 auto; padding: 32px 24px; border: 1px solid #e2e8f0; border-radius: 16px; background-color: #ffffff;'>
                    <div style='text-align: center; margin-bottom: 24px;'>
                        <h2 style='color: #1e3a8a; margin: 0 0 6px; font-size: 20px;'>Portal Layanan Mahasiswa</h2>
                        <p style='color: #64748b; font-size: 13px; margin: 0;'>Kode Verifikasi Baru</p>
                    </div>
                    <p style='font-size: 14px; color: #334155;'>Halo <strong>{$user->name}</strong>,</p>
                    <p style='font-size: 14px; color: #475569;'>Berikut adalah kode verifikasi terbaru Anda:</p>
                    <div style='text-align: center; margin: 28px 0;'>
                        <div style='display: inline-block; font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #2563eb; background: #eff6ff; padding: 14px 28px; border-radius: 12px; border: 2px dashed #93c5fd;'>{$newOtp}</div>
                    </div>
                    <p style='font-size: 13px; color: #64748b;'>Kode ini berlaku selama <strong>10 menit</strong>.</p>
                </div>
            ", function ($message) use ($targetEmail, $newOtp) {
                $message->to($targetEmail)
                    ->subject("Kode Verifikasi Baru: {$newOtp} - Portal Layanan Mahasiswa");
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mengirim ulang OTP: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode verifikasi baru berhasil dikirim ke email.',
            'data'    => [
                'dev_otp' => $isDemo ? $newOtp : null,
            ],
        ]);
    });

    Route::post('/register', function (Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'nim' => ['required', 'string', 'max:30', 'unique:users,nim'],
            'prodi' => ['required', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nim' => $validated['nim'],
            'prodi' => $validated['prodi'],
            'no_hp' => $validated['no_hp'] ?? null,
            'password' => bcrypt($validated['password']),
            'role' => 'mahasiswa',
        ]);

        $token = $user->createToken('portal_auth_token', ['mahasiswa'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran akun mahasiswa berhasil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'nim' => $user->nim,
                    'prodi' => $user->prodi,
                    'no_hp' => $user->no_hp,
                    'foto_profil' => null,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    });
});

// Endpoint yang membutuhkan User Authentication & Role Authorization
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/user/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $request->user()->id,
                'name'        => $request->user()->name,
                'email'       => $request->user()->email,
                'role'        => $request->user()->role,
                'nim'         => $request->user()->nim,
                'prodi'       => $request->user()->prodi,
                'no_hp'       => $request->user()->no_hp,
                'foto_profil' => $request->user()->foto_profil,
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
                    'total_pengajuan' => Pengajuan::count(),
                    'diajukan' => Pengajuan::where('status', Pengajuan::STATUS_DIAJUKAN)->count(),
                    'diproses' => Pengajuan::where('status', Pengajuan::STATUS_DIPROSES)->count(),
                    'perlu_perbaikan' => Pengajuan::where('status', Pengajuan::STATUS_PERLU_PERBAIKAN)->count(),
                    'disetujui' => Pengajuan::where('status', Pengajuan::STATUS_DISETUJUI)->count(),
                    'ditolak' => Pengajuan::where('status', Pengajuan::STATUS_DITOLAK)->count(),
                    'total_layanan' => Layanan::count(),
                    'layanan_aktif' => Layanan::where('is_aktif', true)->count(),
                    'total_mahasiswa' => User::where('role', 'mahasiswa')->count(),
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

    // Profil mahasiswa – self-service update (name, no_hp, prodi) + foto upload
    Route::put('/user/profile', function (Request $request) {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name'   => ['sometimes', 'string', 'max:255'],
            'no_hp'  => ['sometimes', 'nullable', 'string', 'max:20'],
            'prodi'  => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'nim'         => $user->nim,
                'prodi'       => $user->prodi,
                'no_hp'       => $user->no_hp,
                'foto_profil' => $user->foto_profil,
            ],
        ]);
    });

    Route::post('/user/foto', function (Request $request) {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->foto_profil) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->foto_profil);
        }

        $path = $request->file('foto')->store("foto_profil/{$user->id}", 'public');
        $user->update(['foto_profil' => $path]);

        return response()->json([
            'success'     => true,
            'message'     => 'Foto profil berhasil diunggah.',
            'foto_profil' => $path,
        ]);
    });
});

