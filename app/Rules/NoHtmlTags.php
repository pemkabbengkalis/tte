<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Menolak input yang mengandung tag HTML/JavaScript, sebagai pertahanan berlapis
 * terhadap Stored XSS pada field teks bebas (jabatan, instansi, unit_kerja, dll).
 */
class NoHtmlTags implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match('/<[^>]*>/', $value) === 1) {
            $fail('Kolom :attribute tidak diterima.');
        }
    }
}