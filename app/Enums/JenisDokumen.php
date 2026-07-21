<?php

namespace App\Enums;

enum JenisDokumen: string
{
    case SuratPermohonan = 'surat_permohonan';
    case SkJabatan = 'sk_jabatan';
    case SkPangkat = 'sk_pangkat';
    case Ktp = 'ktp';
    case HasilTte = 'hasil_tte';

    public function label(): string
    {
        return match ($this) {
            self::SuratPermohonan => 'Surat Permohonan Penerbitan Sertifikat Elektronik',
            self::SkJabatan       => 'Fotokopi SK Jabatan Terakhir',
            self::SkPangkat       => 'Fotokopi SK Pangkat Terakhir',
            self::Ktp             => 'Fotokopi KTP',
            self::HasilTte        => 'Hasil TTE (Dokumen Bertanda Tangan Elektronik)',
        };
    }

    /**
     * Berkas persyaratan yang diunggah pemohon di awal pengajuan
     * (tidak termasuk hasil akhir yang diunggah verifikator).
     *
     * @return array<self>
     */
    public static function persyaratan(): array
    {
        return [self::SuratPermohonan, self::SkJabatan, self::SkPangkat, self::Ktp];
    }
}
