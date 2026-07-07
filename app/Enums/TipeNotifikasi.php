<?php

namespace App\Enums;

enum TipeNotifikasi: string
{
    case PermohonanBaru = 'permohonan_baru';
    case PengajuanUlang = 'pengajuan_ulang';
    case Diproses = 'diproses';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';
}