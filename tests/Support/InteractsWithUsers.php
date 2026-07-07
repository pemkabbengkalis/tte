<?php

namespace Tests\Support;

use App\Enums\JenisPermohonan;
use App\Enums\RoleUser;
use App\Enums\StatusPermohonan;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Helper trait untuk membuat data pengguna dan permohonan dalam pengujian.
 *
 * Menggunakan penanda urutan statis agar NIP, NIK, dan email tetap
 * unik di seluruh sesi pengujian dalam satu proses PHP yang sama.
 */
trait InteractsWithUsers
{
    private static int $_urutUser = 0;
    private static int $_urutPermohonan = 0;

    /**
     * Buat instance User dengan data yang valid.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function buatUser(array $overrides = []): User
    {
        $n = ++self::$_urutUser;

        return User::create(array_merge([
            'nama_lengkap'      => "User Test {$n}",
            'nip'               => str_pad((string) $n, 18, '0', STR_PAD_LEFT),
            'nik'               => str_pad((string) ($n + 5_000_000), 16, '0', STR_PAD_LEFT),
            'email'             => str_pad((string) $n, 18, '0', STR_PAD_LEFT) . '@bengkaliskab.go.id',
            'password'          => Hash::make('password'),
            'no_hp'             => '08120000' . str_pad((string) ($n % 10_000), 4, '0', STR_PAD_LEFT),
            'pangkat_gol'       => 'Penata / III-c',
            'jabatan'           => 'Staf Test',
            'instansi'          => 'Dinas Kominfotik',
            'unit_kerja'        => 'Bidang Persandian',
            'role'              => RoleUser::Pemohon,
            'email_verified_at' => now(),
        ], $overrides));
    }

    /**
     * Buat pemohon (applicant) yang sudah aktif.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function buatPemohon(array $overrides = []): User
    {
        return $this->buatUser(array_merge(['role' => RoleUser::Pemohon], $overrides));
    }

    /**
     * Buat verifikator yang sudah aktif.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function buatVerifikator(array $overrides = []): User
    {
        return $this->buatUser(array_merge(['role' => RoleUser::Verifikator], $overrides));
    }

    /**
     * Buat admin yang sudah aktif.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function buatAdmin(array $overrides = []): User
    {
        return $this->buatUser(array_merge(['role' => RoleUser::Admin], $overrides));
    }

    /**
     * Buat pemohon yang tidak aktif (belum diverifikasi email).
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function buatPemohonNonaktif(array $overrides = []): User
    {
        return $this->buatUser(array_merge([
            'role'              => RoleUser::Pemohon,
            'email_verified_at' => null,
        ], $overrides));
    }

    /**
     * Buat permohonan dengan pemohon tertentu.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function buatPermohonan(User $pemohon, array $overrides = []): Permohonan
    {
        $n = ++self::$_urutPermohonan;
        $tahun = now()->year;
        $nomor = 'REQ-' . $tahun . '-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);

        return Permohonan::create(array_merge([
            'nomor_permohonan'    => $nomor,
            'pemohon_id'          => $pemohon->id,
            'jenis_permohonan'    => JenisPermohonan::TTE,
            'status'              => StatusPermohonan::Draft,
            'jumlah_pengajuan'    => 1,
        ], $overrides));
    }
}
