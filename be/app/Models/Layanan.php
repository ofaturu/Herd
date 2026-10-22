<?php

namespace App\Models;

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

    protected function casts(): array
    {
        return [
            'persyaratan' => 'array',
            'estimasi_hari' => 'integer',
            'is_aktif' => 'boolean',
        ];
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'layanan_id');
    }
}
