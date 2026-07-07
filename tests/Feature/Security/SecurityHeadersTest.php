<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian keamanan untuk SecurityHeaders middleware.
 *
 * Memverifikasi bahwa header keamanan HTTP yang diperlukan
 * dikirimkan pada setiap respons, sesuai dengan standar keamanan web.
 *
 * Standar: OWASP Security Headers, ISO/IEC 27001 (Web Security)
 * Referensi: OWASP Secure Headers Project
 * Level: Feature (HTTP test)
 */
class SecurityHeadersTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-SEC-HEADER-001: Header wajib ada di semua environment
    // ======================================================================

    public function test_header_x_content_type_options_ada_pada_respons(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_header_x_frame_options_ada_dan_bernilai_deny(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_header_x_xss_protection_ada_pada_respons(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_header_referrer_policy_ada_pada_respons(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_header_permissions_policy_ada_pada_respons(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );
    }

    public function test_header_cross_origin_opener_policy_ada_pada_respons(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    }

    public function test_header_cross_origin_resource_policy_ada_pada_respons(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
    }

    // ======================================================================
    // TC-SEC-HEADER-002: Header yang TIDAK boleh ada di lingkungan testing
    // ======================================================================

    public function test_header_hsts_tidak_ada_di_lingkungan_testing(): void
    {
        // HSTS hanya boleh aktif di production untuk menghindari masalah development
        $response = $this->get(route('login'));

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_header_csp_tidak_ada_di_lingkungan_testing(): void
    {
        // CSP hanya aktif di production
        $response = $this->get(route('login'));

        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    // ======================================================================
    // TC-SEC-HEADER-003: Header ada pada route yang dilindungi autentikasi
    // ======================================================================

    public function test_header_keamanan_ada_pada_halaman_yang_memerlukan_login(): void
    {
        $pemohon = $this->buatPemohon();

        $response = $this->actingAs($pemohon)->get('/pemohon');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_header_keamanan_ada_pada_respons_redirect(): void
    {
        // Akses dashboard sebagai tamu (redirect ke login)
        $response = $this->get(route('dashboard'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
