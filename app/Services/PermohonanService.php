<?php

namespace App\Services;

use App\Enums\StatusPermohonan;
use App\Models\Permohonan;
use App\Models\RiwayatVerifikasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PermohonanService
{
    public function __construct(private NotifikasiService $notifikasi)
    {

    }

    public function generateNomor(): string
    {
        $tahun = now()->year;
        $prefix = "REQ-{$tahun}-";

        return DB::transaction(function () use ($prefix) {
            $terakhir = Permohonan::where('nomor_permohonan', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('nomor_permohonan')
                ->value('nomor_permohonan');

            $urut = $terakhir ? ((int) substr($terakhir, -5)) + 1 : 1;

            return $prefix . str_pad((string) $urut, 5, '0', STR_PAD_LEFT);
        });
    }

    public function submit(Permohonan $permohonan): Permohonan
    {
        $permohonan = DB::transaction(function () use ($permohonan) {
            if (empty($permohonan->nomor_permohonan)) {
                $permohonan->nomor_permohonan = $this->generateNomor();
            }

            $permohonan->status = StatusPermohonan::MenungguVerifikasi;
            $permohonan->tanggal_pengajuan = now();
            $permohonan->save();

            return $permohonan;
        });

        $this->notifikasi->permohonanBaru($permohonan->load('pemohon'));

        return $permohonan;
    }

    public function resubmit(Permohonan $permohonan): Permohonan
    {
        $permohonan = DB::transaction(function () use ($permohonan) {
            $permohonan->status = StatusPermohonan::MenungguVerifikasi;
            $permohonan->tanggal_pengajuan = now();
            $permohonan->tanggal_verifikasi = null;
            $permohonan->verifikator_id = null;
            $permohonan->catatan_verifikator = null;
            $permohonan->jumlah_pengajuan = $permohonan->jumlah_pengajuan + 1;
            $permohonan->save();

            return $permohonan->fresh();
        });

        $this->notifikasi->pengajuanUlang($permohonan->load('pemohon'));

        return $permohonan;
    }

    /**
     * Berkas lengkap & sesuai: Menunggu Verifikasi -> Diproses.
     */
    public function proses(Permohonan $permohonan, User $verifikator): Permohonan
    {
        $permohonan = DB::transaction(function () use ($permohonan, $verifikator) {
            $permohonan->status = StatusPermohonan::Diproses;
            $permohonan->verifikator_id = $verifikator->id;
            $permohonan->tanggal_verifikasi = now();
            $permohonan->catatan_verifikator = null;
            $permohonan->save();

            RiwayatVerifikasi::create([
                'permohonan_id'  => $permohonan->id,
                'verifikator_id' => $verifikator->id,
                'aksi'           => 'diproses',
                'catatan'        => null,
            ]);

            return $permohonan->fresh();
        });

        $this->notifikasi->diproses($permohonan->load('pemohon'));

        return $permohonan;
    }

    /**
     * Proses selesai: Diproses -> Diterima.
     */
    public function terima(Permohonan $permohonan, User $verifikator): Permohonan
    {
        $permohonan = DB::transaction(function () use ($permohonan, $verifikator) {
            $permohonan->status = StatusPermohonan::Diterima;
            $permohonan->verifikator_id = $verifikator->id;
            $permohonan->tanggal_verifikasi = now();
            $permohonan->catatan_verifikator = null;
            $permohonan->save();

            RiwayatVerifikasi::create([
                'permohonan_id'  => $permohonan->id,
                'verifikator_id' => $verifikator->id,
                'aksi'           => 'diterima',
                'catatan'        => null,
            ]);

            return $permohonan->fresh();
        });

        $this->notifikasi->diterima($permohonan->load('pemohon'));

        return $permohonan;
    }

    /**
     * Hasil TTE telah diunggah verifikator: Diterima -> Selesai.
     */
    public function selesaikan(Permohonan $permohonan, User $verifikator): Permohonan
    {
        $permohonan = DB::transaction(function () use ($permohonan, $verifikator) {
            $permohonan->status = StatusPermohonan::Selesai;
            $permohonan->verifikator_id = $verifikator->id;
            $permohonan->tanggal_verifikasi = now();
            $permohonan->save();

            RiwayatVerifikasi::create([
                'permohonan_id'  => $permohonan->id,
                'verifikator_id' => $verifikator->id,
                'aksi'           => 'selesai',
                'catatan'        => null,
            ]);

            return $permohonan->fresh();
        });

        $this->notifikasi->selesai($permohonan->load('pemohon'));

        return $permohonan;
    }

    public function tolak(Permohonan $permohonan, User $verifikator, string $alasan): Permohonan
    {
        $permohonan = DB::transaction(function () use ($permohonan, $verifikator, $alasan) {
            $permohonan->status = StatusPermohonan::Ditolak;
            $permohonan->verifikator_id = $verifikator->id;
            $permohonan->tanggal_verifikasi = now();
            $permohonan->catatan_verifikator = $alasan;
            $permohonan->save();

            RiwayatVerifikasi::create([
                'permohonan_id'  => $permohonan->id,
                'verifikator_id' => $verifikator->id,
                'aksi'           => 'ditolak',
                'catatan'        => $alasan,
            ]);

            return $permohonan->fresh();
        });

        $this->notifikasi->ditolak($permohonan->load('pemohon'), $alasan);

        return $permohonan;
    }
}