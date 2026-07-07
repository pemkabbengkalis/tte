<?php

namespace Tests\Feature\Policy;

use App\Enums\JenisDokumen;
use App\Models\DokumenPermohonan;
use App\Policies\DokumenPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithUsers;
use Tests\TestCase;

/**
 * Pengujian fitur untuk DokumenPolicy.
 *
 * Memverifikasi aturan akses lihat dan unduh dokumen permohonan
 * berdasarkan peran dan kepemilikan permohonan.
 *
 * Standar: ISO/IEC 25010 – Security (Authorization, Confidentiality)
 * Level: Feature (menggunakan database)
 */
class DokumenPolicyTest extends TestCase
{
    use RefreshDatabase, InteractsWithUsers;

    private DokumenPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DokumenPolicy();
    }

    private function buatDokumenUntukPemohon($pemohon): DokumenPermohonan
    {
        $permohonan = $this->buatPermohonan($pemohon);

        return DokumenPermohonan::create([
            'permohonan_id' => $permohonan->id,
            'jenis_dokumen' => JenisDokumen::Ktp,
            'nama_file'     => 'ktp.pdf',
            'path_file'     => 'dokumen/ktp.pdf',
            'ukuran_file'   => 102400,
            'mime_type'     => 'application/pdf',
            'versi'         => 1,
        ]);
    }

    // ======================================================================
    // TC-POLICY-DOK-001: before() — Hak istimewa admin
    // ======================================================================

    public function test_before_memberikan_akses_penuh_ke_admin(): void
    {
        $admin  = $this->buatAdmin();
        $result = $this->policy->before($admin, 'view');

        $this->assertTrue($result);
    }

    public function test_before_mengembalikan_null_untuk_non_admin(): void
    {
        $pemohon = $this->buatPemohon();

        $result = $this->policy->before($pemohon, 'view');

        $this->assertNull($result);
    }

    // ======================================================================
    // TC-POLICY-DOK-002: view() — Akses lihat dokumen
    // ======================================================================

    public function test_view_mengizinkan_verifikator_melihat_dokumen_apapun(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $dokumen     = $this->buatDokumenUntukPemohon($pemohon);

        $this->assertTrue($this->policy->view($verifikator, $dokumen));
    }

    public function test_view_mengizinkan_pemohon_melihat_dokumennya_sendiri(): void
    {
        $pemohon = $this->buatPemohon();
        $dokumen = $this->buatDokumenUntukPemohon($pemohon);

        $this->assertTrue($this->policy->view($pemohon, $dokumen->load('permohonan')));
    }

    public function test_view_menolak_pemohon_melihat_dokumen_orang_lain(): void
    {
        $pemohon1 = $this->buatPemohon();
        $pemohon2 = $this->buatPemohon();
        $dokumen  = $this->buatDokumenUntukPemohon($pemohon1);

        $this->assertFalse($this->policy->view($pemohon2, $dokumen->load('permohonan')));
    }

    // ======================================================================
    // TC-POLICY-DOK-003: download() — Akses unduh dokumen (mirror dari view)
    // ======================================================================

    public function test_download_mengizinkan_verifikator_mengunduh_dokumen(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $dokumen     = $this->buatDokumenUntukPemohon($pemohon);

        $this->assertTrue($this->policy->download($verifikator, $dokumen));
    }

    public function test_download_mengizinkan_pemohon_mengunduh_dokumennya_sendiri(): void
    {
        $pemohon = $this->buatPemohon();
        $dokumen = $this->buatDokumenUntukPemohon($pemohon);

        $this->assertTrue($this->policy->download($pemohon, $dokumen->load('permohonan')));
    }

    public function test_download_menolak_pemohon_mengunduh_dokumen_orang_lain(): void
    {
        $pemohon1 = $this->buatPemohon();
        $pemohon2 = $this->buatPemohon();
        $dokumen  = $this->buatDokumenUntukPemohon($pemohon1);

        $this->assertFalse($this->policy->download($pemohon2, $dokumen->load('permohonan')));
    }
}
