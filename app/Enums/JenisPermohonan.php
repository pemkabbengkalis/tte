<?php

namespace App\Enums;

enum JenisPermohonan: string
{
    case SertifikatElektronik = 'Penerbitan Sertifikat Elektronik';

    public function label(): string
    {
        return $this->value;
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label'=> $case->label()],
            self::cases()
        );
    }
}