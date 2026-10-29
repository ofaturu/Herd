<?php

namespace Database\Factories;

use App\Models\Pengajuan;
use App\Models\User;
use App\Models\Layanan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PengajuanFactory extends Factory
{
    protected $model = Pengajuan::class;

    public function definition()
    {
        return [
            'nomor_pengajuan' => 'PNJ-' . $this->faker->unique()->numberBetween(1000, 9999),
            'user_id' => User::factory(),
            'layanan_id' => Layanan::factory(),
            'tanggal_pengajuan' => now()->toDateString(),
            'status' => Pengajuan::STATUS_DIAJUKAN,
            'keterangan' => $this->faker->sentence,
            'catatan_admin' => null,
            'dokumen_pendukung' => [],
        ];
    }
}
?>
