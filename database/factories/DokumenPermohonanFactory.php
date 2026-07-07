<?php

namespace Database\Factories;

use App\Enums\JenisDokumen;
use App\Models\DokumenPermohonan;
use App\Models\Permohonan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DokumenPermohonan>
 */
class DokumenPermohonanFactory extends Factory
{
    protected $model = DokumenPermohonan::class;

    public function definition(): array
    {
        $jenis   = $this->faker->randomElement(JenisDokumen::cases());
        $ekstensi = $this->faker->randomElement(['pdf', 'jpg', 'png']);

        return [
            'permohonan_id' => Permohonan::factory(),
            'jenis_dokumen' => $jenis,
            'nama_file'     => $jenis->value . '.' . $ekstensi,
            'path_file'     => 'dokumen/' . $this->faker->uuid() . '.' . $ekstensi,
            'ukuran_file'   => $this->faker->numberBetween(10240, 2097152),
            'mime_type'     => $ekstensi === 'pdf' ? 'application/pdf' : 'image/' . $ekstensi,
            'versi'         => 1,
        ];
    }

    public function jenisPermohonan(JenisDokumen $jenis): static
    {
        return $this->state(['jenis_dokumen' => $jenis]);
    }

    public function untukPermohonan(Permohonan $permohonan): static
    {
        return $this->state(['permohonan_id' => $permohonan->id]);
    }
}
