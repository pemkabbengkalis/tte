<?php

namespace App\Services;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class EnvelopeEncryption
{
    /**
     * Mengunci konten file dengan metode Envelope Encryption.
     * 1. Membuat DEK acak.
     * 2. Mengunci file menggunakan DEK.
     * 3. Mengunci DEK menggunakan KEK (APP_KEY) dari Laravel.
     *
     * @param string $plainText Konten file asli.
     * @return array [ 'ciphertext' => konten_file_terkunci, 'encrypted_dek' => dek_terkunci_oleh_kek ]
     */
    public static function encrypt(string $plainText): array
    {
        // 1. Buat DEK (Data Encryption Key) acak (32 bytes untuk AES-256-CBC)
        $dek = random_bytes(32);

        // 2. Kunci konten file pakai DEK
        $encrypter = new Encrypter($dek, config('app.cipher', 'AES-256-CBC'));
        $cipherText = $encrypter->encrypt($plainText);

        // 3. Kunci DEK pakai KEK (APP_KEY).
        // Crypt::encryptString akan menggunakan KEK standar Laravel.
        $encryptedDek = Crypt::encryptString(base64_encode($dek));

        return [
            'ciphertext' => $cipherText,
            'encrypted_dek' => $encryptedDek,
        ];
    }

    /**
     * Membuka kunci file dengan metode Envelope Encryption.
     * 1. Membuka kunci DEK pakai KEK.
     * 2. Membuka kunci file pakai DEK.
     *
     * @param string $cipherText Konten file yang terkunci (dari disk)
     * @param string $encryptedDek DEK yang terkunci (dari database)
     * @return string Konten file asli
     */
    public static function decrypt(string $cipherText, string $encryptedDek): string
    {
        // 1. Buka DEK pakai KEK (APP_KEY)
        $dekBase64 = Crypt::decryptString($encryptedDek);
        $dek = base64_decode($dekBase64);

        // 2. Buka file pakai DEK
        $encrypter = new Encrypter($dek, config('app.cipher', 'AES-256-CBC'));
        return $encrypter->decrypt($cipherText);
    }
}
