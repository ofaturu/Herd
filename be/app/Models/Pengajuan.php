<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengajuan extends Model
{
    use HasFactory;

    protected $table = 'pengajuan';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_PERLU_PERBAIKAN = 'perlu_perbaikan';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    public const VALID_STATUSES = [
        self::STATUS_DIAJUKAN,
        self::STATUS_DIPROSES,
        self::STATUS_PERLU_PERBAIKAN,
        self::STATUS_DISETUJUI,
        self::STATUS_DITOLAK,
    ];

    protected $fillable = [
        'nomor_pengajuan',
        'user_id',
        'layanan_id',
        'tanggal_pengajuan',
        'status',
        'keterangan',
        'catatan_admin',
        'dokumen_pendukung',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'date',
            'dokumen_pendukung' => 'array',
        ];
    }

    /**
     * Relasi ke pemilik pengajuan (User/Mahasiswa).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias relasi ke mahasiswa untuk keterbacaan domain.
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke layanan yang diajukan.
     */
    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class, 'layanan_id');
    }

    /**
     * Relasi ke seluruh riwayat perubahan status pengajuan.
     */
    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatus::class, 'pengajuan_id')->orderBy('waktu', 'asc');
    }

    /**
     * Scope untuk memfilter berdasarkan status.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
