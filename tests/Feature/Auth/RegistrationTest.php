<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian fitur untuk proses registrasi akun pemohon.
 *
 * Memverifikasi validasi data input, pembuatan akun,
 * format email dinas, dan pencocokkan NIP dengan email.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness, Security
 * ISTQB: Equivalence partitioning & boundary value analysis pada input form
 * Level: Feature (Livewire component test)
 */
class RegistrationTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-AUTH-REG-001: Akses halaman registrasi
    // ======================================================================

    public function test_halaman_registrasi_dapat_diakses_oleh_tamu(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_pengguna_yang_sudah_login_diarahkan_dari_halaman_registrasi(): void
    {
        $user = $this->buatPemohon();

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect();
    }

    // ======================================================================
    // TC-AUTH-REG-002: Registrasi berhasil via Livewire component
    // ======================================================================

    public function test_registrasi_berhasil_membuat_akun_di_database(): void
    {
        $nip = '198803122012011004';

        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Andi Wijaya')
            ->set('nip', $nip)
            ->set('nik', '1403011203880004')
            ->set('email', $nip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'nip'   => $nip,
            'email' => $nip . '@bengkaliskab.go.id',
            'role'  => 'pemohon',
        ]);
    }

    public function test_registrasi_berhasil_membuat_akun_dengan_email_verified_at_null(): void
    {
        $nip = '198803122012011005';

        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Dewi Lestari')
            ->set('nip', $nip)
            ->set('nik', '1403016005910005')
            ->set('email', $nip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('nip', $nip)->first();
        $this->assertNull($user->email_verified_at);
    }

    public function test_registrasi_berhasil_mengalihkan_ke_halaman_login(): void
    {
        $nip = '198803122012011006';

        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Rudi Hartono')
            ->set('nip', $nip)
            ->set('nik', '1403011507800006')
            ->set('email', $nip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('login'));
    }

    // ======================================================================
    // TC-AUTH-REG-003: Validasi NIP (18 digit)
    // ======================================================================

    public function test_registrasi_gagal_jika_nip_kurang_dari_18_digit(): void
    {
        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', '12345') // Kurang dari 18 digit
            ->set('nik', '1234567890123456')
            ->set('email', '12345@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nip']);
    }

    public function test_registrasi_gagal_jika_nip_lebih_dari_18_digit(): void
    {
        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', '1234567890123456789') // Lebih dari 18 digit
            ->set('nik', '1234567890123456')
            ->set('email', '1234567890123456789@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nip']);
    }

    public function test_registrasi_gagal_jika_nip_mengandung_huruf(): void
    {
        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', 'ABCD56789012345678') // Mengandung huruf
            ->set('nik', '1234567890123456')
            ->set('email', 'test@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nip']);
    }

    // ======================================================================
    // TC-AUTH-REG-004: Validasi NIK (16 digit)
    // ======================================================================

    public function test_registrasi_gagal_jika_nik_tidak_16_digit(): void
    {
        $nip = '198803122012011008';

        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', $nip)
            ->set('nik', '12345') // Kurang dari 16 digit
            ->set('email', $nip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nik']);
    }

    // ======================================================================
    // TC-AUTH-REG-005: Validasi format email dinas
    // ======================================================================

    public function test_registrasi_gagal_jika_email_bukan_format_bengkaliskab(): void
    {
        $nip = '198803122012011009';

        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', $nip)
            ->set('nik', '1403011203880009')
            ->set('email', 'test@gmail.com') // Email bukan @bengkaliskab.go.id
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);
    }

    public function test_registrasi_gagal_jika_nip_email_tidak_cocok_dengan_nip_isian(): void
    {
        $nip = '198803122012011010';

        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', $nip)
            ->set('nik', '1403011203880010')
            ->set('email', '999999999999999999@bengkaliskab.go.id') // NIP berbeda
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);
    }

    // ======================================================================
    // TC-AUTH-REG-006: Validasi keunikan data
    // ======================================================================

    public function test_registrasi_gagal_jika_nip_sudah_terdaftar(): void
    {
        $nip = '198803122012011011';
        $this->buatPemohon(['nip' => $nip]);

        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Pengguna Duplikat')
            ->set('nip', $nip)
            ->set('nik', '1403011203880099')
            ->set('email', $nip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nip']);
    }

    // ======================================================================
    // TC-AUTH-REG-007: Validasi kata sandi
    // ======================================================================

    public function test_registrasi_gagal_jika_password_kurang_dari_8_karakter(): void
    {
        $nip = '198803122012011012';

        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', $nip)
            ->set('nik', '1403011203880012')
            ->set('email', $nip . '@bengkaliskab.go.id')
            ->set('password', 'abc1234') // 7 karakter
            ->set('password_confirmation', 'abc1234')
            ->call('register')
            ->assertHasErrors(['password']);
    }

    public function test_registrasi_gagal_jika_konfirmasi_password_tidak_cocok(): void
    {
        $nip = '198803122012011013';

        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'Test User')
            ->set('nip', $nip)
            ->set('nik', '1403011203880013')
            ->set('email', $nip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'berbeda456') // Tidak cocok
            ->call('register')
            ->assertHasErrors(['password']);
    }

    // ======================================================================
    // TC-AUTH-REG-008: Validasi nama lengkap
    // ======================================================================

    public function test_registrasi_gagal_jika_nama_lengkap_kurang_dari_3_karakter(): void
    {
        $nip = '198803122012011014';

        \Livewire\Livewire::test('pages::auth.register')
            ->set('nama_lengkap', 'AB') // Kurang dari 3 karakter
            ->set('nip', $nip)
            ->set('nik', '1403011203880014')
            ->set('email', $nip . '@bengkaliskab.go.id')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['nama_lengkap']);
    }
}
