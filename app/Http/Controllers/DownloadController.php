<?php

namespace App\Http\Controllers;

use App\Models\DokumenPermohonan;
use App\Models\TemplateDokumen;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function template()
    {
        $template = TemplateDokumen::where('is_active', true)
            ->latest('created_at')
            ->first();

        abort_if(! $template, 404, 'Template surat belum tersedia. Hubungi admin.');
        abort_unless(Storage::disk('local')->exists($template->path_file), 404, 'Berkas template tidak ditemukan.');

        return Storage::disk('local')->download($template->path_file, $template->nama_file);
    }

    public function dokumenLihat(Request $request, DokumenPermohonan $dokumen)
    {
        Gate::authorize('view', $dokumen);

        abort_unless(Storage::disk('local')->exists($dokumen->path_file), 404);

        Log::info('Akses lihat dokumen', [
            'user_id'       => $request->user()->id,
            'dokumen_id'    => $dokumen->id,
            'permohonan_id' => $dokumen->permohonan_id,
        ]);

        $konten = $this->bacaKontenDokumen($dokumen);

        return response($konten, 200, [
            'Content-Type'           => $dokumen->mime_type,
            'Content-Length'         => strlen($konten),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition'    => 'inline; filename="' . addslashes($dokumen->nama_file) . '"',
            'Cache-Control'          => 'private, no-store, max-age=0',
        ]);
    }

    public function dokumenUnduh(Request $request, DokumenPermohonan $dokumen)
    {
        Gate::authorize('download', $dokumen);

        abort_unless(Storage::disk('local')->exists($dokumen->path_file), 404);

        Log::info('Akses unduh dokumen', [
            'user_id'       => $request->user()->id,
            'dokumen_id'    => $dokumen->id,
            'permohonan_id' => $dokumen->permohonan_id,
        ]);

        $konten = $this->bacaKontenDokumen($dokumen);

        return response($konten, 200, [
            'Content-Type'           => $dokumen->mime_type,
            'Content-Length'         => strlen($konten),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition'    => 'attachment; filename="' . addslashes($dokumen->nama_file) . '"',
            'Cache-Control'          => 'private, no-store, max-age=0',
        ]);
    }

    /**
     * Baca konten dokumen dari disk.
     * Mendukung dua format secara transparan:
     *   - File baru  : terenkripsi dengan encrypt() / AES-256-CBC Laravel
     *   - File lama  : plain, dikembalikan langsung (backward compatible)
     */
    private function bacaKontenDokumen(DokumenPermohonan $dokumen): string
    {
        $raw = Storage::disk('local')->get($dokumen->path_file);

        // Skenario 1: File Baru (Menggunakan Envelope Encryption dengan DEK)
        if ($dokumen->dek) {
            try {
                // Buka gembok filenya dulu untuk mendapatkan isi aslinya (plaintext)
                $plainText = \App\Services\EnvelopeEncryption::decrypt($raw, $dokumen->dek);

                // Verifikasi Checksum Database (File Integrity Monitoring) pada isi aslinya
                if ($dokumen->checksum && hash('sha256', $plainText) !== $dokumen->checksum) {
                    abort(403, 'Akses Ditolak: Integritas file rusak (Checksum file asli tidak cocok).');
                }

                return $plainText;
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Jika proses decrypt gagal (segel MAC rusak), berarti file telah diubah paksa
                abort(403, 'Akses Ditolak: Integritas file rusak (Segel enkripsi telah dimodifikasi secara ilegal).');
            }
        }

        // Skenario 2: File Lama (Tanpa DEK)
        try {
            // Coba dekripsi standar Laravel (backward compatible)
            return decrypt($raw);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // Jika gagal juga, asumsikan ini file super lama yang sama sekali tidak dienkripsi
            return $raw;
        }
    }
}