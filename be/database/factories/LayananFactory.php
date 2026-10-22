<?php

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Layanan>
 */
namespace Database\Factories;

use App\Models\Layanan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LayananFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Layanan::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'kode' => strtoupper(Str::random(5)),
            'nama' => $this->faker->words(3, true),
            'deskripsi' => $this->faker->sentence(),
            'persyaratan' => [],
            'estimasi_hari' => $this->faker->numberBetween(0, 30),
            'is_aktif' => true,
        ];
    }
}
