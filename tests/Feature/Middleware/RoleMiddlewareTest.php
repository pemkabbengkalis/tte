<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian fitur untuk middleware EnsureUserHasRole.
 *
 * Memverifikasi bahwa setiap peran hanya dapat mengakses route
 * yang sesuai dengan hak aksesnya, dan ditolak pada route peran lain.
 *
 * Standar: ISO/IEC 25010 – Security (Authorization), Access Control
 * ISTQB: Decision table testing pada role-based access control
 * Level: Feature (HTTP test)
 */
class RoleMiddlewareTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-MW-ROLE-001: Akses pemohon ke route pemohon
    // ======================================================================

    public function test_pemohon_dapat_mengakses_dashboard_pemohon(): void
    {
        $pemohon = $this->buatPemohon();

        $response = $this->actingAs($pemohon)->get('/pemohon');

        $response->assertStatus(200);
    }

    public function test_pemohon_dapat_mengakses_halaman_buat_permohonan(): void
    {
        $pemohon = $this->buatPemohon();

        $response = $this->actingAs($pemohon)->get('/pemohon/buat-permohonan');

        $response->assertStatus(200);
    }

    // ======================================================================
    // TC-MW-ROLE-002: Pemohon ditolak dari route verifikator
    // ======================================================================

    public function test_pemohon_tidak_dapat_mengakses_dashboard_verifikator(): void
    {
        $pemohon = $this->buatPemohon();

        $response = $this->actingAs($pemohon)->get('/verifikator');

        $response->assertStatus(403);
    }

    // ======================================================================
    // TC-MW-ROLE-003: Pemohon ditolak dari route admin
    // ======================================================================

    public function test_pemohon_tidak_dapat_mengakses_dashboard_admin(): void
    {
        $pemohon = $this->buatPemohon();

        $response = $this->actingAs($pemohon)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_pemohon_tidak_dapat_mengakses_halaman_manajemen_akun(): void
    {
        $pemohon = $this->buatPemohon();

        $response = $this->actingAs($pemohon)->get(route('akun.index'));

        $response->assertStatus(403);
    }

    // ======================================================================
    // TC-MW-ROLE-004: Akses verifikator ke route verifikator
    // ======================================================================

    public function test_verifikator_dapat_mengakses_dashboard_verifikator(): void
    {
        $verifikator = $this->buatVerifikator();

        $response = $this->actingAs($verifikator)->get('/verifikator');

        $response->assertStatus(200);
    }

    // ======================================================================
    // TC-MW-ROLE-005: Verifikator ditolak dari route pemohon
    // ======================================================================

    public function test_verifikator_tidak_dapat_mengakses_dashboard_pemohon(): void
    {
        $verifikator = $this->buatVerifikator();

        $response = $this->actingAs($verifikator)->get('/pemohon');

        $response->assertStatus(403);
    }

    public function test_verifikator_tidak_dapat_mengakses_halaman_buat_permohonan(): void
    {
        $verifikator = $this->buatVerifikator();

        $response = $this->actingAs($verifikator)->get('/pemohon/buat-permohonan');

        $response->assertStatus(403);
    }

    // ======================================================================
    // TC-MW-ROLE-006: Verifikator ditolak dari route admin
    // ======================================================================

    public function test_verifikator_tidak_dapat_mengakses_dashboard_admin(): void
    {
        $verifikator = $this->buatVerifikator();

        $response = $this->actingAs($verifikator)->get('/admin');

        $response->assertStatus(403);
    }

    // ======================================================================
    // TC-MW-ROLE-007: Akses admin ke route admin
    // ======================================================================

    public function test_admin_dapat_mengakses_dashboard_admin(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_admin_dapat_mengakses_halaman_manajemen_template(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->get(route('admin.template'));

        $response->assertStatus(200);
    }

    public function test_admin_dapat_mengakses_halaman_manajemen_akun(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->get(route('akun.index'));

        $response->assertStatus(200);
    }

    // ======================================================================
    // TC-MW-ROLE-008: Admin ditolak dari route pemohon
    // ======================================================================

    public function test_admin_tidak_dapat_mengakses_dashboard_pemohon(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->get('/pemohon');

        $response->assertStatus(403);
    }

    // ======================================================================
    // TC-MW-ROLE-009: Verifikator dapat mengakses halaman akun
    // ======================================================================

    public function test_verifikator_dapat_mengakses_halaman_manajemen_akun(): void
    {
        $verifikator = $this->buatVerifikator();

        $response = $this->actingAs($verifikator)->get(route('akun.index'));

        $response->assertStatus(200);
    }
}
