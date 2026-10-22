<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatus extends Model
{
    use HasFactory;

    protected $table = 'riwayat_status';

    protected $fillable = [
        'pengajuan_id',
        'user_id',
        'status_sebelumnya',
        'status_baru',
        'catatan',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    /**
     * Relasi ke pengajuan terkait.
     */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }

    /**
     * Relasi ke pengguna (admin / mahasiswa) yang melakukan aksi / perubahan status.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias relasi pelaku perubahan status untuk kejelasan konteks.
     */
    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
