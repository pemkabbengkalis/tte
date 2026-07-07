<?php

namespace Database\Factories;

use App\Models\TemplateDokumen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateDokumen>
 */
class TemplateDokumenFactory extends Factory
{
    protected $model = TemplateDokumen::class;

    public function definition(): array
    {
        return [
            'nama_template' => 'Template Surat Permohonan v' . $this->faker->numberBetween(1, 10),
            'deskripsi'     => $this->faker->sentence(),
            'nama_file'     => 'template_surat_permohonan.docx',
            'path_file'     => 'templates/' . $this->faker->uuid() . '.docx',
            'versi'         => $this->faker->numberBetween(1, 5),
            'is_active'     => true,
            'uploaded_by'   => User::factory(),
        ];
    }

    public function aktif(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function nonaktif(): static
    {
        return $this->state(['is_active' => false]);
    }
}
