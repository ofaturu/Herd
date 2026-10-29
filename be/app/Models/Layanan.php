<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Layanan extends Model
{
    use HasFactory;

    protected $table = 'layanan';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'persyaratan',
        'estimasi_hari',
        'is_aktif',
    ];

    protected $casts = [
        'persyaratan' => 'array',
        'estimasi_hari' => 'integer',
        'is_aktif' => 'boolean',
    ];

    /**
     * Relasi ke seluruh pengajuan yang menggunakan layanan ini.
     */
    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'layanan_id');
    }

    /**
     * Scope untuk mengambil layanan aktif saja.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }
}
