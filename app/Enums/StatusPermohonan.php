<?php

namespace App\Enums;

enum StatusPermohonan: string
{
    case Draft = 'draft';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Diproses = 'diproses';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::Diproses => 'Diproses',
            self::Diterima => 'Diterima',
            self::Ditolak => 'Ditolak',
            self::Selesai => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::MenungguVerifikasi => 'yellow',
            self::Diproses => 'indigo',
            self::Diterima => 'green',
            self::Ditolak => 'red',
            self::Selesai => 'blue',
        };
    }
}