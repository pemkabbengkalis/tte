<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian fitur untuk middleware EnsureUserIsActive.
 *
 * Memverifikasi bahwa pengguna yang tidak aktif (email_verified_at = null)
 * dikeluarkan dari sistem dan diarahkan ke halaman login dengan pesan error.
 *
 * Standar: ISO/IEC 25010 – Security (Access Control)
 * Level: Feature (HTTP test)
 */
class ActiveUserMiddlewareTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-MW-AKTIF-001: Pengguna aktif dapat mengakses route terproteksi
    // ======================================================================

    public function test_pengguna_aktif_dapat_mengakses_halaman_yang_dilindungi(): void
    {
        $pemohon = $this->buatPemohon();

        $response = $this->actingAs($pemohon)->get('/pemohon');

        $response->assertStatus(200);
    }

    // ======================================================================
    // TC-MW-AKTIF-002: Pengguna nonaktif dikeluarkan dari sistem
    // ======================================================================

    public function test_pengguna_nonaktif_diarahkan_ke_login(): void
    {
        $pemohon = $this->buatPemohonNonaktif();

        $response = $this->actingAs($pemohon)->get('/pemohon');

        $response->assertRedirect(route('login'));
    }

    public function test_pengguna_nonaktif_dikeluarkan_dari_sesi(): void
    {
        $pemohon = $this->buatPemohonNonaktif();

        $this->actingAs($pemohon)->get('/pemohon');

        $this->assertGuest();
    }

    public function test_pengguna_nonaktif_mendapatkan_pesan_error_yang_sesuai(): void
    {
        $pemohon = $this->buatPemohonNonaktif();

        $response = $this->actingAs($pemohon)->get('/pemohon');

        $response->assertSessionHasErrors(['email']);
    }

    // ======================================================================
    // TC-MW-AKTIF-003: Nonaktifkan akun yang sebelumnya aktif
    // ======================================================================

    public function test_pengguna_yang_dinonaktifkan_tidak_dapat_mengakses_sistem(): void
    {
        $pemohon = $this->buatPemohon();

        // Simulasi admin menonaktifkan akun
        $pemohon->nonaktifkan();

        $response = $this->actingAs($pemohon)->get('/pemohon');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ======================================================================
    // TC-MW-AKTIF-004: Reaktivasi akun
    // ======================================================================

    public function test_pengguna_yang_diaktifkan_kembali_dapat_mengakses_sistem(): void
    {
        $pemohon = $this->buatPemohonNonaktif();

        // Simulasi admin mengaktifkan kembali akun
        $pemohon->aktifkan();

        $response = $this->actingAs($pemohon)->get('/pemohon');

        $response->assertStatus(200);
    }
}
