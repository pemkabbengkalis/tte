<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * Memverifikasi token widget Google reCAPTCHA v2 ke endpoint siteverify.
 */
class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (!is_string($value) || $value === '') {
            $fail('Silakan selesaikan verifikasi reCAPTCHA.');
            return;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => config('services.recaptcha.secret_key'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (!$response->successful() || $response->json('success') !== true) {
            $fail('Verifikasi reCAPTCHA gagal. Silakan coba lagi.');
        }
    }
}
