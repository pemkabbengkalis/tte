<?php

namespace Database\Factories;

use App\Enums\TipeNotifikasi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notifikasi>
 */
class NotifikasiFactory extends Factory
{
    protected $model = Notifikasi::class;

    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'permohonan_id' => Permohonan::factory(),
            'judul'         => $this->faker->sentence(4),
            'pesan'         => $this->faker->sentence(10),
            'tipe'          => $this->faker->randomElement(TipeNotifikasi::cases()),
            'is_read'       => false,
        ];
    }

    public function belumDibaca(): static
    {
        return $this->state(['is_read' => false]);
    }

    public function sudahDibaca(): static
    {
        return $this->state(['is_read' => true]);
    }

    public function untukUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
