<?php

namespace Tests\Unit\Enums;

use App\Enums\TipeNotifikasi;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian unit untuk enum TipeNotifikasi.
 *
 * Memverifikasi tipe-tipe notifikasi yang dikirimkan sistem kepada
 * pengguna pada setiap perubahan status permohonan.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness
 * Level: Unit
 */
class TipeNotifikasiEnumTest extends TestCase
{
    // ======================================================================
    // TC-ENUM-NOTIF-001: Verifikasi nilai string setiap tipe notifikasi
    // ======================================================================

    public function test_permohonan_baru_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('permohonan_baru', TipeNotifikasi::PermohonanBaru->value);
    }

    public function test_pengajuan_ulang_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('pengajuan_ulang', TipeNotifikasi::PengajuanUlang->value);
    }

    public function test_diproses_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('diproses', TipeNotifikasi::Diproses->value);
    }

    public function test_diterima_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('diterima', TipeNotifikasi::Diterima->value);
    }

    public function test_ditolak_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('ditolak', TipeNotifikasi::Ditolak->value);
    }

    public function test_selesai_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('selesai', TipeNotifikasi::Selesai->value);
    }

    // ======================================================================
    // TC-ENUM-NOTIF-002: Verifikasi jumlah dan integritas
    // ======================================================================

    public function test_enum_memiliki_tepat_enam_tipe_notifikasi(): void
    {
        $this->assertCount(6, TipeNotifikasi::cases());
    }

    public function test_semua_nilai_unik(): void
    {
        $values = array_map(fn ($case) => $case->value, TipeNotifikasi::cases());
        $this->assertSame(count($values), count(array_unique($values)));
    }

    public function test_tryFrom_mengembalikan_null_untuk_nilai_tidak_valid(): void
    {
        $this->assertNull(TipeNotifikasi::tryFrom('tipe_tidak_ada'));
    }

    public function test_dapat_dibuat_dari_nilai_string(): void
    {
        $this->assertSame(TipeNotifikasi::Diterima, TipeNotifikasi::from('diterima'));
        $this->assertSame(TipeNotifikasi::Ditolak, TipeNotifikasi::from('ditolak'));
        $this->assertSame(TipeNotifikasi::PermohonanBaru, TipeNotifikasi::from('permohonan_baru'));
        $this->assertSame(TipeNotifikasi::PengajuanUlang, TipeNotifikasi::from('pengajuan_ulang'));
        $this->assertSame(TipeNotifikasi::Diproses, TipeNotifikasi::from('diproses'));
        $this->assertSame(TipeNotifikasi::Selesai, TipeNotifikasi::from('selesai'));
    }
}
