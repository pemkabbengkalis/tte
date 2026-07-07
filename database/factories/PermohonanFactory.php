<?php

namespace Database\Factories;

use App\Enums\JenisPermohonan;
use App\Enums\RoleUser;
use App\Enums\StatusPermohonan;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Permohonan>
 */
class PermohonanFactory extends Factory
{
    protected $model = Permohonan::class;

    private static int $nomorUrut = 0;
    private static int $pemohonUrut = 0;

    public function definition(): array
    {
        self::$nomorUrut++;
        $tahun = now()->year;
        $nomor = 'REQ-' . $tahun . '-' . str_pad((string) self::$nomorUrut, 5, '0', STR_PAD_LEFT);

        return [
            'nomor_permohonan'    => $nomor,
            'pemohon_id'          => $this->buatPemohon(),
            'jenis_permohonan'    => $this->faker->randomElement(JenisPermohonan::cases()),
            'status'              => StatusPermohonan::Draft,
            'tanggal_pengajuan'   => null,
            'tanggal_verifikasi'  => null,
            'verifikator_id'      => null,
            'catatan_verifikator' => null,
            'jumlah_pengajuan'    => 1,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => StatusPermohonan::Draft]);
    }

    public function menungguVerifikasi(): static
    {
        return $this->state(fn () => [
            'status'            => StatusPermohonan::MenungguVerifikasi,
            'tanggal_pengajuan' => now(),
        ]);
    }

    public function diterima(): static
    {
        return $this->state(fn () => [
            'status'             => StatusPermohonan::Diterima,
            'tanggal_pengajuan'  => now()->subDays(3),
            'tanggal_verifikasi' => now(),
        ]);
    }

    public function ditolak(string $catatan = 'Dokumen tidak lengkap.'): static
    {
        return $this->state(fn () => [
            'status'              => StatusPermohonan::Ditolak,
            'tanggal_pengajuan'   => now()->subDays(3),
            'tanggal_verifikasi'  => now(),
            'catatan_verifikator' => $catatan,
        ]);
    }

    public function untukPemohon(User $pemohon): static
    {
        return $this->state(['pemohon_id' => $pemohon->id]);
    }

    private function buatPemohon(): string
    {
        self::$pemohonUrut++;
        $n = self::$pemohonUrut + 100000;
        return User::create([
            'nama_lengkap'      => "Pemohon Factory {$n}",
            'nip'               => str_pad((string) $n, 18, '0', STR_PAD_LEFT),
            'nik'               => str_pad((string) ($n + 5000000), 16, '0', STR_PAD_LEFT),
            'email'             => str_pad((string) $n, 18, '0', STR_PAD_LEFT) . '@bengkaliskab.go.id',
            'password'          => Hash::make('password'),
            'no_hp'             => '08120000' . str_pad((string) ($n % 10000), 4, '0', STR_PAD_LEFT),
            'pangkat_gol'       => 'Penata / III-c',
            'jabatan'           => 'Staf',
            'instansi'          => 'Dinas Test',
            'unit_kerja'        => 'Bidang Test',
            'role'              => RoleUser::Pemohon,
            'email_verified_at' => now(),
        ])->id;
    }
}
