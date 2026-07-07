<?php

namespace App\Http\Controllers;

use App\Models\DokumenPermohonan;
use App\Models\TemplateDokumen;
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
            'user_id' => $request->user()->id,
            'dokumen_id' => $dokumen->id,
            'permohonan_id' => $dokumen->permohonan_id,
        ]);

        return response()->file(
            Storage::disk('local')->path($dokumen->path_file),
            [
                'Content-Type'              => $dokumen->mime_type,
                'X-Content-Type-Options'    => 'nosniff',
                'Content-Disposition'       => 'inline; filename="' . addslashes($dokumen->nama_file) . '"',
                'Cache-Control'             => 'private, no-store, max-age=0',
            ]
        );
    }

    public function dokumenUnduh(Request $request, DokumenPermohonan $dokumen)
    {
        Gate::authorize('download', $dokumen);

        abort_unless(Storage::disk('local')->exists($dokumen->path_file), 404);

        Log::info('Akses unduh dokumen', [
            'user_id' => $request->user()->id,
            'dokumen_id' => $dokumen->id,
            'permohonan_id' => $dokumen->permohonan_id,
        ]);

        return Storage::disk('local')->download(
            $dokumen->path_file,
            $dokumen->nama_file,
            [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control'          => 'private, no-store, max-age=0',
            ]
        );
    }
}