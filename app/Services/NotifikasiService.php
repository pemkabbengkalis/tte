<?php

namespace App\Services;

use App\Enums\RoleUser;
use App\Enums\TipeNotifikasi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotifikasiService
{
    public function permohonanBaru(Permohonan $permohonan): void
    {
        $pemohon = $permohonan->pemohon;

        $this->kirimKeSemuaVerifikator(
            $permohonan,
            TipeNotifikasi::PermohonanBaru,
            judul: 'Permohonan baru masuk',
            pesan: "Permohonan baru dari {$pemohon->nama_lengkap} — {$pemohon->nip}.",
        );
    }

    public function pengajuanUlang(Permohonan $permohonan): void
    {
        $pemohon = $permohonan->pemohon;

        $this->kirimKeSemuaVerifikator(
            $permohonan,
            TipeNotifikasi::PengajuanUlang,
            judul: 'Pengajuan ulang',
            pesan: "Pengajuan ulang dari {$pemohon->nama_lengkap} — {$pemohon->nip} (pengajuan ke-{$permohonan->jumlah_pengajuan}).",
        );
    }

    public function diproses(Permohonan $permohonan): void
    {
        Notifikasi::create([
            'user_id'       => $permohonan->pemohon_id,
            'permohonan_id' => $permohonan->id,
            'judul'         => 'Permohonan sedang diproses',
            'pesan'         => "Berkas permohonan atas nama {$permohonan->pemohon->nama_lengkap} telah diverifikasi lengkap dan sedang diproses untuk penerbitan sertifikat elektronik.",
            'tipe'          => TipeNotifikasi::Diproses,
            'is_read'       => false,
        ]);
    }

    public function diterima(Permohonan $permohonan): void
    {
        Notifikasi::create([
            'user_id'       => $permohonan->pemohon_id,
            'permohonan_id' => $permohonan->id,
            'judul'         => 'Permohonan diterima',
            'pesan'         => "Permohonan sertifikat elektronik atas nama {$permohonan->pemohon->nama_lengkap} telah diterima. Silakan menunggu proses penerbitan sertifikat elektronik.",
            'tipe'          => TipeNotifikasi::Diterima,
            'is_read'       => false,
        ]);
    }

    public function ditolak(Permohonan $permohonan, string $alasan): void
    {
        Notifikasi::create([
            'user_id'       => $permohonan->pemohon_id,
            'permohonan_id' => $permohonan->id,
            'judul'         => 'Permohonan ditolak',
            'pesan'         => "Permohonan Anda ditolak. Alasan: {$alasan}. Silakan perbaiki dan ajukan kembali.",
            'tipe'          => TipeNotifikasi::Ditolak,
            'is_read'       => false,
        ]);
    }

    private function kirimKeSemuaVerifikator(
        Permohonan $permohonan,
        TipeNotifikasi $tipe,
        string $judul,
        string $pesan,
    ): void {
        $verifikatorIds = User::where('role', RoleUser::Verifikator)
            ->whereNotNull('email_verified_at')
            ->pluck('id');

        if ($verifikatorIds->isEmpty()) {
            return;
        }

        $rows = $verifikatorIds->map(fn ($id) => [
            'id'            => (string) \Illuminate\Support\Str::uuid(),
            'user_id'       => $id,
            'permohonan_id' => $permohonan->id,
            'judul'         => $judul,
            'pesan'         => $pesan,
            'tipe'          => $tipe->value,
            'is_read'       => false,
            'created_at'    => now(),
        ])->all();

        DB::table('notifikasi')->insert($rows);
    }
}