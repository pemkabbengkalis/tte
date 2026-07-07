<?php

namespace Tests\Feature\Models;

use App\Enums\RoleUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithUsers;
use Tests\TestCase;

/**
 * Pengujian fitur untuk model User.
 *
 * Memverifikasi metode helper peran dan status aktif/nonaktif
 * pengguna, serta relasi antar model.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness, Functional Completeness
 * Level: Feature (menggunakan database)
 */
class UserModelTest extends TestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-MODEL-USER-001: Metode pengecekan peran (role helpers)
    // ======================================================================

    public function test_isPemohon_mengembalikan_true_untuk_pemohon(): void
    {
        $pemohon = $this->buatPemohon();

        $this->assertTrue($pemohon->isPemohon());
        $this->assertFalse($pemohon->isVerifikator());
        $this->assertFalse($pemohon->isAdmin());
    }

    public function test_isVerifikator_mengembalikan_true_untuk_verifikator(): void
    {
        $verifikator = $this->buatVerifikator();

        $this->assertTrue($verifikator->isVerifikator());
        $this->assertFalse($verifikator->isPemohon());
        $this->assertFalse($verifikator->isAdmin());
    }

    public function test_isAdmin_mengembalikan_true_untuk_admin(): void
    {
        $admin = $this->buatAdmin();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isPemohon());
        $this->assertFalse($admin->isVerifikator());
    }

    // ======================================================================
    // TC-MODEL-USER-002: Metode pengecekan status aktif
    // ======================================================================

    public function test_isAktif_mengembalikan_true_jika_email_verified_at_terisi(): void
    {
        $user = $this->buatPemohon(['email_verified_at' => now()]);

        $this->assertTrue($user->isAktif());
    }

    public function test_isAktif_mengembalikan_false_jika_email_verified_at_null(): void
    {
        $user = $this->buatPemohonNonaktif();

        $this->assertFalse($user->isAktif());
    }

    // ======================================================================
    // TC-MODEL-USER-003: Metode pengaktifan dan penonaktifan akun
    // ======================================================================

    public function test_aktifkan_mengisi_email_verified_at(): void
    {
        $user = $this->buatPemohonNonaktif();
        $this->assertNull($user->email_verified_at);

        $user->aktifkan();
        $user->refresh();

        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->isAktif());
    }

    public function test_nonaktifkan_mengosongkan_email_verified_at(): void
    {
        $user = $this->buatPemohon();
        $this->assertTrue($user->isAktif());

        $user->nonaktifkan();
        $user->refresh();

        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->isAktif());
    }

    // ======================================================================
    // TC-MODEL-USER-004: Kolom yang tersembunyi (security)
    // ======================================================================

    public function test_password_tidak_muncul_pada_serialisasi(): void
    {
        $user  = $this->buatPemohon();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    // ======================================================================
    // TC-MODEL-USER-005: Verifikasi penyimpanan peran ke database
    // ======================================================================

    public function test_peran_pemohon_tersimpan_sebagai_string_di_database(): void
    {
        $user = $this->buatPemohon();

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'role' => 'pemohon',
        ]);
    }

    public function test_peran_verifikator_tersimpan_sebagai_string_di_database(): void
    {
        $verifikator = $this->buatVerifikator();

        $this->assertDatabaseHas('users', [
            'id'   => $verifikator->id,
            'role' => 'verifikator',
        ]);
    }

    // ======================================================================
    // TC-MODEL-USER-006: Soft deletes
    // ======================================================================

    public function test_menghapus_user_menggunakan_soft_delete(): void
    {
        $user   = $this->buatPemohon();
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertDatabaseHas('users', ['id' => $userId]);
    }

    // ======================================================================
    // TC-MODEL-USER-007: UUID sebagai primary key
    // ======================================================================

    public function test_user_menggunakan_uuid_sebagai_primary_key(): void
    {
        $user = $this->buatPemohon();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $user->id
        );
    }
}
