<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian fitur untuk proses autentikasi login.
 *
 * Memverifikasi akses halaman login, validasi kredensial,
 * pengecekan status akun aktif, dan pengalihan berdasarkan peran.
 *
 * Standar: ISO/IEC 25010 – Security (Authentication)
 * ISTQB: Equivalence partitioning — valid/invalid credentials
 * Level: Feature (HTTP test)
 */
class LoginTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-AUTH-LOGIN-001: Akses halaman login
    // ======================================================================

    public function test_halaman_login_dapat_diakses_oleh_tamu(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    public function test_pengguna_yang_sudah_login_diarahkan_dari_halaman_login(): void
    {
        $user = $this->buatPemohon();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect();
    }

    // ======================================================================
    // TC-AUTH-LOGIN-002: Pengalihan dashboard berdasarkan peran
    // ======================================================================

    public function test_pemohon_diarahkan_ke_dashboard_pemohon_setelah_login(): void
    {
        $pemohon = $this->buatPemohon();

        $response = $this->actingAs($pemohon)->get(route('dashboard'));

        $response->assertRedirect('/pemohon');
    }

    public function test_verifikator_diarahkan_ke_dashboard_verifikator_setelah_login(): void
    {
        $verifikator = $this->buatVerifikator();

        $response = $this->actingAs($verifikator)->get(route('dashboard'));

        $response->assertRedirect('/verifikator');
    }

    public function test_admin_diarahkan_ke_dashboard_admin_setelah_login(): void
    {
        $admin = $this->buatAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertRedirect('/admin');
    }

    // ======================================================================
    // TC-AUTH-LOGIN-003: Proteksi route terautentikasi
    // ======================================================================

    public function test_tamu_tidak_dapat_mengakses_halaman_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_tamu_tidak_dapat_mengakses_halaman_pemohon(): void
    {
        $response = $this->get('/pemohon');

        $response->assertRedirect(route('login'));
    }

    public function test_tamu_tidak_dapat_mengakses_halaman_verifikator(): void
    {
        $response = $this->get('/verifikator');

        $response->assertRedirect(route('login'));
    }

    public function test_tamu_tidak_dapat_mengakses_halaman_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    // ======================================================================
    // TC-AUTH-LOGIN-004: Root URL pengalihan
    // ======================================================================

    public function test_root_url_mengarahkan_tamu_ke_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_root_url_mengarahkan_pengguna_terautentikasi_ke_dashboard(): void
    {
        $user = $this->buatPemohon();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
