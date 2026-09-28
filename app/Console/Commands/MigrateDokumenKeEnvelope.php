<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:migrate-dokumen-ke-envelope')]
#[Description('Melakukan enkripsi ulang dokumen lama dengan menggunakan sistem Envelope Encryption (KEK + DEK + Checksum)')]
class MigrateDokumenKeEnvelope extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Cari semua dokumen yang belum di-enkripsi pakai sistem baru (DEK-nya masih kosong)
        $dokumenLama = \App\Models\DokumenPermohonan::whereNull('dek')->get();
        
        if ($dokumenLama->isEmpty()) {
            $this->info('Tidak ada dokumen lama yang perlu dimigrasi. Semua sudah pakai Envelope Encryption!');
            return;
        }

        $this->info('Memulai migrasi ' . $dokumenLama->count() . ' dokumen lama ke Envelope Encryption...');

        $sukses = 0;
        $gagal = 0;

        foreach ($dokumenLama as $dok) {
            try {
                // 1. Baca file enkripsi lama dari disk
                $raw = \Illuminate\Support\Facades\Storage::disk('local')->get($dok->path_file);
                
                if (!$raw) {
                    $this->error("File tidak ditemukan di storage: {$dok->path_file}");
                    $gagal++;
                    continue;
                }

                // 2. Bongkar pakai cara lama untuk mendapatkan gambar aslinya (plaintext)
                try {
                    $plainText = decrypt($raw);
                } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                    // Jika decrypt biasa gagal, mungkin dulunya tidak dienkripsi sama sekali,
                    // jadi kita anggap raw-nya adalah plaintext asli
                    $plainText = $raw;
                }

                // 3. Kunci kembali gambar asli tersebut pakai sistem DEK + KEK baru
                $envelope = \App\Services\EnvelopeEncryption::encrypt($plainText);

                // 4. Timpa file lama di storage dengan file baru yang sudah terkunci Envelope
                \Illuminate\Support\Facades\Storage::disk('local')->put($dok->path_file, $envelope['ciphertext']);

                // 5. Simpan DEK dan Checksum ke tabel database
                $dok->dek = $envelope['encrypted_dek'];
                $dok->checksum = hash('sha256', $plainText);
                $dok->save();

                $sukses++;
                $this->line("Berhasil memigrasi: {$dok->nama_file} (ID: {$dok->id})");
            } catch (\Exception $e) {
                $gagal++;
                $this->error("Gagal memigrasi dokumen ID {$dok->id}: " . $e->getMessage());
            }
        }

        $this->info("Migrasi Selesai! Sukses: {$sukses}, Gagal: {$gagal}");
    }
}
