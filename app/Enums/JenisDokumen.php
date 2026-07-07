<?php

namespace App\Enums;

enum JenisDokumen: string
{
    case SuratPermohonan = 'surat_permohonan';
    case SkJabatan = 'sk_jabatan';
    case SkPangkat = 'sk_pangkat';
    case Ktp = 'ktp';

    public function label(): string
    {
        return match ($this) {
            self::SuratPermohonan => 'Surat Permohonan Penerbitan Sertifikat Elektronik',
            self::SkJabatan       => 'Fotokopi SK Jabatan Terakhir',
            self::SkPangkat       => 'Fotokopi SK Pangkat Terakhir',
            self::Ktp             => 'Fotokopi KTP',
        };
    }
}
