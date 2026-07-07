<?php

namespace Tests\Unit\Enums;

use App\Enums\RoleUser;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian unit untuk enum RoleUser.
 *
 * Memverifikasi bahwa nilai dan label setiap peran pengguna
 * sesuai dengan spesifikasi sistem.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness
 * Level: Unit
 */
class RoleUserEnumTest extends TestCase
{
    // ======================================================================
    // TC-ENUM-ROLE-001: Verifikasi nilai string setiap case
    // ======================================================================

    public function test_pemohon_memiliki_nilai_string_yang_benar(): void
    {
        $this->assertSame('pemohon', RoleUser::Pemohon->value);
    }

    public function test_verifikator_memiliki_nilai_string_yang_benar(): void
    {
        $this->assertSame('verifikator', RoleUser::Verifikator->value);
    }

    public function test_admin_memiliki_nilai_string_yang_benar(): void
    {
        $this->assertSame('admin', RoleUser::Admin->value);
    }

    // ======================================================================
    // TC-ENUM-ROLE-002: Verifikasi label setiap case
    // ======================================================================

    public function test_label_pemohon_dikembalikan_dengan_benar(): void
    {
        $this->assertSame('Pemohon', RoleUser::Pemohon->label());
    }

    public function test_label_verifikator_dikembalikan_dengan_benar(): void
    {
        $this->assertSame('Verifikator', RoleUser::Verifikator->label());
    }

    public function test_label_admin_dikembalikan_dengan_benar(): void
    {
        $this->assertSame('Administrator', RoleUser::Admin->label());
    }

    // ======================================================================
    // TC-ENUM-ROLE-003: Verifikasi jumlah case
    // ======================================================================

    public function test_enum_memiliki_tepat_tiga_peran(): void
    {
        $this->assertCount(3, RoleUser::cases());
    }

    // ======================================================================
    // TC-ENUM-ROLE-004: Pembuatan dari nilai string (from)
    // ======================================================================

    public function test_dapat_dibuat_dari_nilai_string_pemohon(): void
    {
        $this->assertSame(RoleUser::Pemohon, RoleUser::from('pemohon'));
    }

    public function test_dapat_dibuat_dari_nilai_string_verifikator(): void
    {
        $this->assertSame(RoleUser::Verifikator, RoleUser::from('verifikator'));
    }

    public function test_dapat_dibuat_dari_nilai_string_admin(): void
    {
        $this->assertSame(RoleUser::Admin, RoleUser::from('admin'));
    }

    public function test_tryFrom_mengembalikan_null_untuk_nilai_tidak_valid(): void
    {
        $this->assertNull(RoleUser::tryFrom('tidak_ada'));
    }
}
