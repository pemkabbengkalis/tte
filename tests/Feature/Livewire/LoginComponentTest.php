<?php

namespace Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian komponen Livewire halaman login.
 *
 * Memverifikasi fungsionalitas form login: validasi input,
 * pengecekan kredensial, pengecekan status aktif akun,
 * dan rate limiting percobaan login.
 *
 * Standar: ISO/IEC 25010 – Security (Authentication), Functional Correctness
 * ISTQB: Equivalence partitioning & boundary value analysis
 * Level: Feature (Livewire component test)
 */
class LoginComponentTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-LW-LOGIN-001: Rendering komponen
    // ======================================================================

    public function test_komponen_login_dapat_dirender(): void
    {
        Livewire::test('pages::auth.login')
            ->assertStatus(200);
    }

    // ======================================================================
    // TC-LW-LOGIN-002: Login berhasil
    // ======================================================================

    public function test_login_berhasil_dengan_kredensial_yang_valid(): void
    {
        $n   = ++self::$_urutUser;
        $nip = str_pad((string) $n, 18, '0', STR_PAD_LEFT);

        $pemohon = $this->buatPemohon([
            'nip'   => $nip,
            'email' => $nip . '@bengkaliskab.go.id',
        ]);

        Livewire::test('pages::auth.login')
            ->set('email', $pemohon->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_berhasil_mengautentikasi_pengguna(): void
    {
        $n   = ++self::$_urutUser;
        $nip = str_pad((string) $n, 18, '0', STR_PAD_LEFT);

        $pemohon = $this->buatPemohon([
            'nip'   => $nip,
            'email' => $nip . '@bengkaliskab.go.id',
        ]);

        Livewire::test('pages::auth.login')
            ->set('email', $pemohon->email)
            ->set('password', 'password')
            ->call('login');

        $this->assertAuthenticated();
    }

    // ======================================================================
    // TC-LW-LOGIN-003: Login gagal — kredensial tidak valid
    // ======================================================================

    public function test_login_gagal_dengan_password_salah(): void
    {
        $pemohon = $this->buatPemohon();

        Livewire::test('pages::auth.login')
            ->set('email', $pemohon->email)
            ->set('password', 'password_salah')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    public function test_login_gagal_dengan_email_tidak_terdaftar(): void
    {
        Livewire::test('pages::auth.login')
            ->set('email', 'tidakterdaftar@bengkaliskab.go.id')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    // ======================================================================
    // TC-LW-LOGIN-004: Validasi field wajib
    // ======================================================================

    public function test_login_gagal_jika_email_kosong(): void
    {
        Livewire::test('pages::auth.login')
            ->set('email', '')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    public function test_login_gagal_jika_password_kosong(): void
    {
        $pemohon = $this->buatPemohon();

        Livewire::test('pages::auth.login')
            ->set('email', $pemohon->email)
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['password']);
    }

    public function test_login_gagal_jika_email_tidak_valid(): void
    {
        Livewire::test('pages::auth.login')
            ->set('email', 'bukan-email')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    // ======================================================================
    // TC-LW-LOGIN-005: Login gagal — akun tidak aktif
    // ======================================================================

    public function test_login_gagal_jika_akun_belum_diaktifkan(): void
    {
        $pemohon = $this->buatPemohonNonaktif();

        Livewire::test('pages::auth.login')
            ->set('email', $pemohon->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    // ======================================================================
    // TC-LW-LOGIN-006: Status komponen
    // ======================================================================

    public function test_nilai_awal_field_email_adalah_string_kosong(): void
    {
        Livewire::test('pages::auth.login')
            ->assertSet('email', '');
    }

    public function test_nilai_awal_field_password_adalah_string_kosong(): void
    {
        Livewire::test('pages::auth.login')
            ->assertSet('password', '');
    }

    public function test_nilai_awal_remember_adalah_false(): void
    {
        Livewire::test('pages::auth.login')
            ->assertSet('remember', false);
    }
}
