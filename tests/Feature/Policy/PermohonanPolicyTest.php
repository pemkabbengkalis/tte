<?php

namespace Tests\Feature\Policy;

use App\Enums\StatusPermohonan;
use App\Policies\PermohonanPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithUsers;
use Tests\TestCase;

/**
 * Pengujian fitur untuk PermohonanPolicy.
 *
 * Memverifikasi seluruh aturan otorisasi akses pada permohonan:
 * siapa yang dapat melihat, membuat, memperbarui, menghapus,
 * dan memverifikasi permohonan.
 *
 * Standar: ISO/IEC 25010 – Security (Authorization)
 * ISTQB: Decision table testing pada authorization rules
 * Level: Feature (menggunakan database)
 */
class PermohonanPolicyTest extends TestCase
{
    use RefreshDatabase, InteractsWithUsers;

    private PermohonanPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PermohonanPolicy();
    }

    // ======================================================================
    // TC-POLICY-PERM-001: before() — Hak istimewa admin
    // ======================================================================

    public function test_before_memberikan_akses_penuh_ke_admin_untuk_ability_view(): void
    {
        $admin      = $this->buatAdmin();
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $result = $this->policy->before($admin, 'view');

        $this->assertTrue($result);
    }

    public function test_before_tidak_memberikan_akses_admin_untuk_ability_create(): void
    {
        $admin = $this->buatAdmin();

        $result = $this->policy->before($admin, 'create');

        $this->assertNull($result);
    }

    public function test_before_mengembalikan_null_untuk_non_admin(): void
    {
        $pemohon = $this->buatPemohon();

        $result = $this->policy->before($pemohon, 'view');

        $this->assertNull($result);
    }

    // ======================================================================
    // TC-POLICY-PERM-002: viewAny() — Siapa yang boleh melihat daftar
    // ======================================================================

    public function test_viewAny_mengizinkan_semua_pengguna_terautentikasi(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $admin       = $this->buatAdmin();

        $permohonanDummy = $this->buatPermohonan($pemohon);

        $this->assertTrue($this->policy->viewAny($pemohon));
        $this->assertTrue($this->policy->viewAny($verifikator));
        $this->assertTrue($this->policy->viewAny($admin));
    }

    // ======================================================================
    // TC-POLICY-PERM-003: view() — Siapa yang boleh melihat detail
    // ======================================================================

    public function test_view_mengizinkan_verifikator_melihat_permohonan_apapun(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon);

        $this->assertTrue($this->policy->view($verifikator, $permohonan));
    }

    public function test_view_mengizinkan_pemohon_melihat_permohonannya_sendiri(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->assertTrue($this->policy->view($pemohon, $permohonan));
    }

    public function test_view_menolak_pemohon_melihat_permohonan_orang_lain(): void
    {
        $pemohon1   = $this->buatPemohon();
        $pemohon2   = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon1);

        $this->assertFalse($this->policy->view($pemohon2, $permohonan));
    }

    // ======================================================================
    // TC-POLICY-PERM-004: create() — Siapa yang boleh membuat/mengedit
    // ======================================================================

    public function test_create_mengizinkan_pemohon_pada_status_draft(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Draft]);

        $this->assertTrue($this->policy->create($pemohon, $permohonan));
    }

    public function test_create_mengizinkan_pemohon_pada_status_ditolak(): void
    {
        $pemohon    = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan = $this->buatPermohonan($pemohon, [
            'status'         => StatusPermohonan::Ditolak,
            'verifikator_id' => $verifikator->id,
        ]);

        $this->assertTrue($this->policy->create($pemohon, $permohonan));
    }

    public function test_create_menolak_pemohon_pada_status_menunggu_verifikasi(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::MenungguVerifikasi]);

        $this->assertFalse($this->policy->create($pemohon, $permohonan));
    }

    public function test_create_menolak_pemohon_pada_status_diterima(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Diterima]);

        $this->assertFalse($this->policy->create($pemohon, $permohonan));
    }

    public function test_create_menolak_verifikator(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Draft]);

        $this->assertFalse($this->policy->create($verifikator, $permohonan));
    }

    public function test_create_menolak_pemohon_lain_meskipun_status_draft(): void
    {
        $pemohon1   = $this->buatPemohon();
        $pemohon2   = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon1, ['status' => StatusPermohonan::Draft]);

        $this->assertFalse($this->policy->create($pemohon2, $permohonan));
    }

    // ======================================================================
    // TC-POLICY-PERM-005: update() — Siapa yang boleh memperbarui
    // ======================================================================

    public function test_update_mengizinkan_pemohon_pada_status_draft(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Draft]);

        $this->assertTrue($this->policy->update($pemohon, $permohonan));
    }

    public function test_update_mengizinkan_pemohon_pada_status_ditolak(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Ditolak]);

        $this->assertTrue($this->policy->update($pemohon, $permohonan));
    }

    public function test_update_menolak_pemohon_pada_status_menunggu_verifikasi(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::MenungguVerifikasi]);

        $this->assertFalse($this->policy->update($pemohon, $permohonan));
    }

    public function test_update_menolak_pemohon_pada_status_selesai(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Selesai]);

        $this->assertFalse($this->policy->update($pemohon, $permohonan));
    }

    // ======================================================================
    // TC-POLICY-PERM-006: delete() — Siapa yang boleh menghapus
    // ======================================================================

    public function test_delete_mengizinkan_pemohon_hanya_pada_status_draft(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Draft]);

        $this->assertTrue($this->policy->delete($pemohon, $permohonan));
    }

    public function test_delete_menolak_pemohon_pada_status_menunggu_verifikasi(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::MenungguVerifikasi]);

        $this->assertFalse($this->policy->delete($pemohon, $permohonan));
    }

    public function test_delete_menolak_pemohon_lain_meskipun_status_draft(): void
    {
        $pemohon1   = $this->buatPemohon();
        $pemohon2   = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon1, ['status' => StatusPermohonan::Draft]);

        $this->assertFalse($this->policy->delete($pemohon2, $permohonan));
    }

    // ======================================================================
    // TC-POLICY-PERM-007: verifikasi() — Siapa yang boleh memverifikasi
    // ======================================================================

    public function test_verifikasi_mengizinkan_verifikator_pada_status_menunggu(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::MenungguVerifikasi]);

        $this->assertTrue($this->policy->verifikasi($verifikator, $permohonan));
    }

    public function test_verifikasi_menolak_verifikator_pada_status_draft(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Draft]);

        $this->assertFalse($this->policy->verifikasi($verifikator, $permohonan));
    }

    public function test_verifikasi_menolak_verifikator_pada_status_diterima(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Diterima]);

        $this->assertFalse($this->policy->verifikasi($verifikator, $permohonan));
    }

    public function test_verifikasi_menolak_pemohon(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::MenungguVerifikasi]);

        $this->assertFalse($this->policy->verifikasi($pemohon, $permohonan));
    }

    // ======================================================================
    // TC-POLICY-PERM-008: uploadTte() — Siapa yang boleh mengunggah hasil TTE
    // ======================================================================

    public function test_uploadTte_mengizinkan_verifikator_pada_status_diterima(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Diterima]);

        $this->assertTrue($this->policy->uploadTte($verifikator, $permohonan));
    }

    public function test_uploadTte_menolak_verifikator_pada_status_diproses(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Diproses]);

        $this->assertFalse($this->policy->uploadTte($verifikator, $permohonan));
    }

    public function test_uploadTte_menolak_verifikator_pada_status_selesai(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Selesai]);

        $this->assertFalse($this->policy->uploadTte($verifikator, $permohonan));
    }

    public function test_uploadTte_menolak_pemohon(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Diterima]);

        $this->assertFalse($this->policy->uploadTte($pemohon, $permohonan));
    }
}
