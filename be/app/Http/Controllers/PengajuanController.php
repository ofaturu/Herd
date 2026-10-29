<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\Layanan;
use App\Models\RiwayatStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PengajuanController extends Controller
{
    /**
     * Admin: List all pengajuan with optional status filter & pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $query = Pengajuan::with(['layanan', 'user']);
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nomor_pengajuan', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('nim', 'like', "%{$search}%");
                  })
                  ->orWhereHas('layanan', function ($lq) use ($search) {
                      $lq->where('nama', 'like', "%{$search}%");
                  });
            });
        }
        $pengajuan = $query->latest()->paginate($perPage);
        return response()->json([
            'success' => true,
            'data' => $pengajuan->items(),
            'meta' => [
                'current_page' => $pengajuan->currentPage(),
                'per_page' => $pengajuan->perPage(),
                'total' => $pengajuan->total(),
                'last_page' => $pengajuan->lastPage(),
            ],
        ]);
    }

    /**
     * Mahasiswa: List own pengajuan.
     */
    public function myIndex(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $pengajuan = Pengajuan::with(['layanan', 'riwayatStatus'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($perPage);
        return response()->json([
            'success' => true,
            'data' => $pengajuan->items(),
            'meta' => [
                'current_page' => $pengajuan->currentPage(),
                'per_page' => $pengajuan->perPage(),
                'total' => $pengajuan->total(),
                'last_page' => $pengajuan->lastPage(),
            ],
        ]);
    }

    /**
     * Admin: Show any pengajuan; Mahasiswa: show own via route myShow.
     */
    public function show(Pengajuan $pengajuan): JsonResponse
    {
        $pengajuan->load(['layanan', 'user', 'riwayatStatus.user']);
        return response()->json([
            'success' => true,
            'data' => $pengajuan,
        ]);
    }

    /**
     * Mahasiswa: Show own pengajuan.
     */
    public function myShow(Request $request, Pengajuan $pengajuan): JsonResponse
    {
        if ($pengajuan->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan tidak ditemukan.',
            ], 404);
        }
        $pengajuan->load(['layanan', 'riwayatStatus.user']);
        return response()->json([
            'success' => true,
            'data' => $pengajuan,
        ]);
    }

    /**
     * Mahasiswa: Create new pengajuan.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'layanan_id' => 'required|exists:layanan,id',
            'keterangan' => 'nullable|string',
            'dokumen_pendukung' => 'nullable|array',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }
        // Verify layanan aktif
        $layanan = Layanan::aktif()->find($request->layanan_id);
        if (! $layanan) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan tidak aktif atau tidak ditemukan.',
            ], 422);
        }
        $pengajuan = DB::transaction(function () use ($request) {
            return Pengajuan::create([
                'nomor_pengajuan' => 'PNJ-'.time(),
                'user_id' => $request->user()->id,
                'layanan_id' => $request->layanan_id,
                'tanggal_pengajuan' => now()->toDateString(),
                'status' => Pengajuan::STATUS_DIAJUKAN,
                'keterangan' => $request->input('keterangan'),
                'dokumen_pendukung' => $request->input('dokumen_pendukung'),
            ]);
        });
        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dibuat.',
            'data' => $pengajuan,
        ], 201);
    }

    /**
     * Admin: Update pengajuan (except status – use changeStatus).
     */
    public function update(Request $request, Pengajuan $pengajuan): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'layanan_id' => 'sometimes|exists:layanan,id',
            'keterangan' => 'sometimes|nullable|string',
            'dokumen_pendukung' => 'sometimes|nullable|array',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }
        // If layanan_id is being changed, ensure it is aktif
        if ($request->has('layanan_id')) {
            $layanan = Layanan::aktif()->find($request->layanan_id);
            if (! $layanan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan tidak aktif atau tidak ditemukan.',
                ], 422);
            }
        }
        $pengajuan->update($validator->validated());
        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil diperbarui.',
            'data' => $pengajuan,
        ]);
    }

    /**
     * Admin: Change status & add catatan admin. Creates riwayat_status entry.
     */
    public function changeStatus(Request $request, Pengajuan $pengajuan): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status_baru' => 'required|string|in:' . implode(',', Pengajuan::VALID_STATUSES),
            'catatan' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }
        DB::transaction(function () use ($request, $pengajuan, $validator) {
            $statusSebelumnya = $pengajuan->status;
            $data = $validator->validated();
            $pengajuan->status = $data['status_baru'];
            if (array_key_exists('catatan', $data)) {
                $pengajuan->catatan_admin = $data['catatan'];
            }
            $pengajuan->save();
            RiwayatStatus::create([
                'pengajuan_id' => $pengajuan->id,
                'user_id' => $request->user()->id,
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => $pengajuan->status,
                'catatan' => $data['catatan'] ?? null,
                'waktu' => now(),
            ]);
        });
        return response()->json([
            'success' => true,
            'message' => 'Status pengajuan berhasil diubah.',
        ]);
    }

    /**
     * Admin: Delete pengajuan (hard delete).
     */
    public function destroy(Pengajuan $pengajuan): JsonResponse
    {
        $pengajuan->delete();
        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dihapus.',
        ]);
    }
}
?>
