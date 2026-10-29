<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LayananController extends Controller
{
    /**
     * Display a paginated listing of active layanan.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $query = Layanan::query();
        if (! ($request->query('all') && $request->user('sanctum')?->isAdmin())) {
            $query->aktif();
        }
        if ($request->filled('is_aktif')) {
            $query->where('is_aktif', filter_var($request->query('is_aktif'), FILTER_VALIDATE_BOOLEAN));
        }
        $layanan = $query->paginate($perPage);
        return response()->json([
            'success' => true,
            'data' => $layanan->items(),
            'meta' => [
                'current_page' => $layanan->currentPage(),
                'per_page' => $layanan->perPage(),
                'total' => $layanan->total(),
                'last_page' => $layanan->lastPage(),
            ],
        ]);
    }

    /**
     * Show a specific layanan if it is active or if requested by admin.
     */
    public function show(Request $request, Layanan $layanan): JsonResponse
    {
        if (! $layanan->is_aktif && ! $request->user('sanctum')?->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan tidak ditemukan.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $layanan,
        ]);
    }

    /**
     * Store a newly created layanan. Only admin can access.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|max:50|unique:layanan,kode',
            'nama' => 'required|string|max:150',
            'deskripsi' => 'nullable|string',
            'persyaratan' => 'nullable|array',
            'estimasi_hari' => 'required|integer|min:0',
            'is_aktif' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $layanan = Layanan::create($validator->validated());
        return response()->json([
            'success' => true,
            'message' => 'Layanan berhasil dibuat.',
            'data' => $layanan,
        ], 201);
    }

    /**
     * Update an existing layanan. Only admin can access.
     */
    public function update(Request $request, Layanan $layanan): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "kode" => "sometimes|required|string|max:50|unique:layanan,kode,{$layanan->id}",
            "nama" => "sometimes|required|string|max:150",
            "deskripsi" => "nullable|string",
            "persyaratan" => "nullable|array",
            "estimasi_hari" => "sometimes|required|integer|min:0",
            "is_aktif" => "boolean",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $layanan->update($validator->validated());
        return response()->json([
            'success' => true,
            'message' => 'Layanan berhasil diperbarui.',
            'data' => $layanan,
        ]);
    }

    /**
     * Soft‑delete (deactivate) a layanan. Only admin can access.
     */
    public function destroy(Layanan $layanan): JsonResponse
    {
        $layanan->is_aktif = false;
        $layanan->save();
        return response()->json([
            'success' => true,
            'message' => 'Layanan berhasil dinonaktifkan.',
        ]);
    }
}
