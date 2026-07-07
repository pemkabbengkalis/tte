<?php

namespace Tests\Feature\Controllers;

use App\Enums\JenisDokumen;
use App\Models\DokumenPermohonan;
use App\Models\TemplateDokumen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian fitur untuk DownloadController.
 *
 * Memverifikasi bahwa pengunduhan template surat dan dokumen permohonan
 * hanya dapat dilakukan oleh pengguna yang berwenang,
 * serta penanganan error file tidak ditemukan.
 *
 * Standar: ISO/IEC 25010 – Security (Authorization), Functional Correctness
 * Level: Feature (HTTP test)
 */
class DownloadControllerTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-CTRL-DL-001: Template download — Akses autentikasi
    // ======================================================================

    public function test_tamu_tidak_dapat_mengakses_endpoint_template(): void
    {
        $response = $this->get(route('template.download'));

        $response->assertRedirect(route('login'));
    }

    public function test_template_mengembalikan_404_jika_tidak_ada_template_aktif(): void
    {
        $pemohon = $this->buatPemohon();

        $response = $this->actingAs($pemohon)->get(route('template.download'));

        $response->assertStatus(404);
    }

    public function test_template_mengembalikan_404_jika_file_tidak_ada_di_storage(): void
    {
        $pemohon = $this->buatPemohon();
        $admin   = $this->buatAdmin();

        Storage::fake('local');

        // Buat template di DB tapi file tidak diupload ke storage
        TemplateDokumen::create([
            'nama_template' => 'Template v1',
            'deskripsi'     => 'Template untuk pengujian',
            'nama_file'     => 'template.docx',
            'path_file'     => 'templates/template.docx',
            'versi'         => 1,
            'is_active'     => true,
            'uploaded_by'   => $admin->id,
        ]);

        $response = $this->actingAs($pemohon)->get(route('template.download'));

        $response->assertStatus(404);
    }

    public function test_template_berhasil_diunduh_jika_ada_dan_file_tersedia(): void
    {
        $pemohon = $this->buatPemohon();
        $admin   = $this->buatAdmin();

        Storage::fake('local');
        Storage::disk('local')->put('templates/template.docx', 'Konten template untuk pengujian');

        TemplateDokumen::create([
            'nama_template' => 'Template v1',
            'deskripsi'     => 'Template pengujian',
            'nama_file'     => 'template_surat.docx',
            'path_file'     => 'templates/template.docx',
            'versi'         => 1,
            'is_active'     => true,
            'uploaded_by'   => $admin->id,
        ]);

        $response = $this->actingAs($pemohon)->get(route('template.download'));

        $response->assertStatus(200);
    }

    // ======================================================================
    // TC-CTRL-DL-002: Dokumen lihat — Otorisasi berdasarkan policy
    // ======================================================================

    public function test_tamu_tidak_dapat_mengakses_lihat_dokumen(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);
        $dokumen    = $this->buatDokumenPermohonan($permohonan);

        $response = $this->get(route('dokumen.lihat', $dokumen));

        $response->assertRedirect(route('login'));
    }

    public function test_pemohon_tidak_dapat_melihat_dokumen_milik_orang_lain(): void
    {
        $pemohon1   = $this->buatPemohon();
        $pemohon2   = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon1);
        $dokumen    = $this->buatDokumenPermohonan($permohonan);

        $response = $this->actingAs($pemohon2)->get(route('dokumen.lihat', $dokumen));

        $response->assertStatus(403);
    }

    public function test_lihat_dokumen_mengembalikan_404_jika_file_tidak_ada(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        Storage::fake('local');

        $dokumen = $this->buatDokumenPermohonan($permohonan, 'dokumen/tidak_ada.pdf');

        $response = $this->actingAs($pemohon)->get(route('dokumen.lihat', $dokumen));

        $response->assertStatus(404);
    }

    // ======================================================================
    // TC-CTRL-DL-003: Dokumen unduh — Otorisasi berdasarkan policy
    // ======================================================================

    public function test_tamu_tidak_dapat_mengakses_unduh_dokumen(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);
        $dokumen    = $this->buatDokumenPermohonan($permohonan);

        $response = $this->get(route('dokumen.unduh', $dokumen));

        $response->assertRedirect(route('login'));
    }

    public function test_pemohon_tidak_dapat_mengunduh_dokumen_milik_orang_lain(): void
    {
        $pemohon1   = $this->buatPemohon();
        $pemohon2   = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon1);
        $dokumen    = $this->buatDokumenPermohonan($permohonan);

        $response = $this->actingAs($pemohon2)->get(route('dokumen.unduh', $dokumen));

        $response->assertStatus(403);
    }

    public function test_unduh_dokumen_mengembalikan_404_jika_file_tidak_ada(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        Storage::fake('local');

        $dokumen = $this->buatDokumenPermohonan($permohonan, 'dokumen/tidak_ada.pdf');

        $response = $this->actingAs($pemohon)->get(route('dokumen.unduh', $dokumen));

        $response->assertStatus(404);
    }

    public function test_verifikator_dapat_mengunduh_dokumen_permohonan(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon);

        Storage::fake('local');
        Storage::disk('local')->put('dokumen/ktp.pdf', 'Konten file PDF untuk pengujian');

        $dokumen = $this->buatDokumenPermohonan($permohonan, 'dokumen/ktp.pdf');

        $response = $this->actingAs($verifikator)->get(route('dokumen.unduh', $dokumen));

        $response->assertStatus(200);
    }

    // ======================================================================
    // Helper methods
    // ======================================================================

    private function buatDokumenPermohonan($permohonan, string $pathFile = 'dokumen/test.pdf'): DokumenPermohonan
    {
        return DokumenPermohonan::create([
            'permohonan_id' => $permohonan->id,
            'jenis_dokumen' => JenisDokumen::Ktp,
            'nama_file'     => 'ktp.pdf',
            'path_file'     => $pathFile,
            'ukuran_file'   => 102400,
            'mime_type'     => 'application/pdf',
            'versi'         => 1,
        ]);
    }
}
