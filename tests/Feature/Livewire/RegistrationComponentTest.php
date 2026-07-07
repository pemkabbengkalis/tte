<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian komponen Livewire halaman registrasi.
 *
 * Memverifikasi fungsionalitas form pendaftaran: validasi format NIP/NIK/email,
 * pencocokkan NIP dengan email dinas, keunikan data, dan pembuatan akun.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness, Data Integrity
 * Level: Feature (Livewire component test)
 */
class RegistrationComponentTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    private string $validNip = '198803122012011099';
    private string $validNik = '1403011203889900';

    // ======================================================================
    // TC-LW-REG-001: Rendering komponen
    // ======================================================================

    public function test_komponen_registrasi_dapat_dirender(): void
    {
        Livewire::test('pages::auth.register')
            ->assertStatus(200);
    }

    // ======================================================================
    // TC-LW-REG-002: Pendaftaran berhasil
    // ======================================================================

    public function test_registrasi_berhasil_membuat_akun_pemohon(): void
    {
        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Andi Wijaya Test')
            ->set('nip', $this->validNip)
            ->set('nik', $this->validNik)
            ->set('email', $this->validNip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'nip'  => $this->validNip,
            'role' => 'pemohon',
        ]);
    }

    public function test_akun_baru_belum_aktif_setelah_registrasi(): void
    {
        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Andi Wijaya Test')
            ->set('nip', $this->validNip)
            ->set('nik', $this->validNik)
            ->set('email', $this->validNip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('nip', $this->validNip)->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->isAktif());
    }

    public function test_registrasi_berhasil_mengalihkan_ke_halaman_login(): void
    {
        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Andi Wijaya Test')
            ->set('nip', $this->validNip)
            ->set('nik', $this->validNik)
            ->set('email', $this->validNip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('login'));
    }

    // ======================================================================
    // TC-LW-REG-003: Validasi NIP
    // ======================================================================

    public function test_registrasi_gagal_nip_tidak_tepat_18_digit(): void
    {
        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', '12345678901234567') // 17 digit
            ->set('nik', $this->validNik)
            ->set('email', '12345678901234567@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nip']);
    }

    public function test_registrasi_gagal_nip_mengandung_huruf(): void
    {
        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', 'ABCD56789012345678')
            ->set('nik', $this->validNik)
            ->set('email', 'test@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nip']);
    }

    // ======================================================================
    // TC-LW-REG-004: Validasi NIK
    // ======================================================================

    public function test_registrasi_gagal_nik_tidak_tepat_16_digit(): void
    {
        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', $this->validNip)
            ->set('nik', '123456789012345') // 15 digit
            ->set('email', $this->validNip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nik']);
    }

    // ======================================================================
    // TC-LW-REG-005: Validasi email dinas
    // ======================================================================

    public function test_registrasi_gagal_email_bukan_domain_bengkaliskab(): void
    {
        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', $this->validNip)
            ->set('nik', $this->validNik)
            ->set('email', 'test@gmail.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);
    }

    public function test_registrasi_gagal_jika_nip_di_email_berbeda_dengan_nip_isian(): void
    {
        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', $this->validNip)
            ->set('nik', $this->validNik)
            ->set('email', '999999999999999999@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);
    }

    // ======================================================================
    // TC-LW-REG-006: Validasi keunikan data
    // ======================================================================

    public function test_registrasi_gagal_jika_nip_sudah_terdaftar(): void
    {
        $this->buatPemohon(['nip' => $this->validNip]);

        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Duplikat NIP')
            ->set('nip', $this->validNip)
            ->set('nik', '1403011203889901')
            ->set('email', $this->validNip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nip']);
    }

    public function test_registrasi_gagal_jika_nik_sudah_terdaftar(): void
    {
        $nip2 = '197001011990031099';
        $this->buatPemohon(['nik' => $this->validNik]);

        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Duplikat NIK')
            ->set('nip', $nip2)
            ->set('nik', $this->validNik)
            ->set('email', $nip2 . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nik']);
    }

    // ======================================================================
    // TC-LW-REG-007: Validasi kata sandi
    // ======================================================================

    public function test_registrasi_gagal_password_kurang_dari_8_karakter(): void
    {
        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', $this->validNip)
            ->set('nik', $this->validNik)
            ->set('email', $this->validNip . '@bengkaliskab.go.id')
            ->set('password', '1234567') // 7 karakter
            ->set('password_confirmation', '1234567')
            ->call('register')
            ->assertHasErrors(['password']);
    }

    public function test_registrasi_gagal_konfirmasi_password_tidak_cocok(): void
    {
        Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', $this->validNip)
            ->set('nik', $this->validNik)
            ->set('email', $this->validNip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password456')
            ->call('register')
            ->assertHasErrors(['password']);
    }
}
