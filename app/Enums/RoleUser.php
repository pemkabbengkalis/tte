<?php

namespace App\Enums;

enum RoleUser: string
{
    case Pemohon = 'pemohon';
    case Verifikator = 'verifikator';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Pemohon => 'Pemohon',
            self::Verifikator => 'Verifikator',
            self::Admin => 'Administrator',
        };
    }
}