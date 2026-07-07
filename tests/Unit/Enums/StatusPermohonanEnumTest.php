<?php

namespace Tests\Unit\Enums;

use App\Enums\StatusPermohonan;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian unit untuk enum StatusPermohonan.
 *
 * Memverifikasi siklus hidup status permohonan, label tampilan,
 * dan warna indikator yang digunakan pada antarmuka pengguna.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness
 * Level: Unit
 */
class StatusPermohonanEnumTest extends TestCase
{
    // ======================================================================
    // TC-ENUM-STATUS-001: Verifikasi nilai string setiap status
    // ======================================================================

    public function test_draft_memiliki_nilai_string_yang_benar(): void
    {
        $this->assertSame('draft', StatusPermohonan::Draft->value);
    }

    public function test_menunggu_verifikasi_memiliki_nilai_string_yang_benar(): void
    {
        $this->assertSame('menunggu_verifikasi', StatusPermohonan::MenungguVerifikasi->value);
    }

    public function test_diterima_memiliki_nilai_string_yang_benar(): void
    {
        $this->assertSame('diterima', StatusPermohonan::Diterima->value);
    }

    public function test_ditolak_memiliki_nilai_string_yang_benar(): void
    {
        $this->assertSame('ditolak', StatusPermohonan::Ditolak->value);
    }

    public function test_selesai_memiliki_nilai_string_yang_benar(): void
    {
        $this->assertSame('selesai', StatusPermohonan::Selesai->value);
    }

    // ======================================================================
    // TC-ENUM-STATUS-002: Verifikasi label setiap status
    // ======================================================================

    public function test_label_draft_dikembalikan_dengan_benar(): void
    {
        $this->assertSame('Draft', StatusPermohonan::Draft->label());
    }

    public function test_label_menunggu_verifikasi_dikembalikan_dengan_benar(): void
    {
        $this->assertSame('Menunggu Verifikasi', StatusPermohonan::MenungguVerifikasi->label());
    }

    public function test_label_diterima_dikembalikan_dengan_benar(): void
    {
        $this->assertSame('Diterima', StatusPermohonan::Diterima->label());
    }

    public function test_label_ditolak_dikembalikan_dengan_benar(): void
    {
        $this->assertSame('Ditolak', StatusPermohonan::Ditolak->label());
    }

    public function test_label_selesai_dikembalikan_dengan_benar(): void
    {
        $this->assertSame('Selesai', StatusPermohonan::Selesai->label());
    }

    // ======================================================================
    // TC-ENUM-STATUS-003: Verifikasi warna indikator UI
    // ======================================================================

    public function test_warna_draft_adalah_gray(): void
    {
        $this->assertSame('gray', StatusPermohonan::Draft->color());
    }

    public function test_warna_menunggu_verifikasi_adalah_yellow(): void
    {
        $this->assertSame('yellow', StatusPermohonan::MenungguVerifikasi->color());
    }

    public function test_warna_diterima_adalah_green(): void
    {
        $this->assertSame('green', StatusPermohonan::Diterima->color());
    }

    public function test_warna_ditolak_adalah_red(): void
    {
        $this->assertSame('red', StatusPermohonan::Ditolak->color());
    }

    public function test_warna_selesai_adalah_blue(): void
    {
        $this->assertSame('blue', StatusPermohonan::Selesai->color());
    }

    // ======================================================================
    // TC-ENUM-STATUS-004: Verifikasi jumlah dan pembuatan
    // ======================================================================

    public function test_enum_memiliki_tepat_lima_status(): void
    {
        $this->assertCount(5, StatusPermohonan::cases());
    }

    public function test_tryFrom_mengembalikan_null_untuk_nilai_tidak_valid(): void
    {
        $this->assertNull(StatusPermohonan::tryFrom('tidak_valid'));
    }
}
