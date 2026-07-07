<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian fitur untuk proses logout.
 *
 * Memverifikasi bahwa pengguna dapat keluar dari sistem dengan benar,
 * sesi dibatalkan, dan pengguna diarahkan ke halaman login.
 *
 * Standar: ISO/IEC 25010 – Security (Session Management)
 * Level: Feature (HTTP test)
 */
class LogoutTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-AUTH-LOGOUT-001: Proses logout
    // ======================================================================

    public function test_pengguna_yang_sudah_login_dapat_melakukan_logout(): void
    {
        $user = $this->buatPemohon();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_setelah_logout_pengguna_tidak_lagi_terautentikasi(): void
    {
        $user = $this->buatPemohon();

        $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
    }

    // ======================================================================
    // TC-AUTH-LOGOUT-002: Akses setelah logout
    // ======================================================================

    public function test_setelah_logout_akses_ke_dashboard_diarahkan_ke_login(): void
    {
        $user = $this->buatPemohon();
        $this->actingAs($user)->post(route('logout'));

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    // ======================================================================
    // TC-AUTH-LOGOUT-003: Validasi CSRF (logout membutuhkan POST)
    // ======================================================================

    public function test_logout_hanya_dapat_dilakukan_via_post(): void
    {
        $response = $this->get(route('logout'));

        // Route logout hanya menerima POST, GET harus 405 Method Not Allowed
        $response->assertStatus(405);
    }
}
