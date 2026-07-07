<?php

namespace Tests\Unit\Models;

use App\Models\DokumenPermohonan;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian unit untuk logika murni pada model DokumenPermohonan.
 *
 * Hanya menguji metode yang tidak memerlukan koneksi database,
 * yaitu metode helper ukuranTerbaca().
 *
 * Standar: ISO/IEC 25010 – Functional Correctness
 * Level: Unit
 */
class DokumenPermohonanTest extends TestCase
{
    // ======================================================================
    // TC-MODEL-DOK-001: Konversi ukuran file ke format yang dapat dibaca
    // ======================================================================

    public function test_ukuran_dalam_byte_ditampilkan_tanpa_konversi(): void
    {
        $dokumen             = new DokumenPermohonan();
        $dokumen->ukuran_file = 500;

        $this->assertSame('500B', $dokumen->ukuranTerbaca());
    }

    public function test_ukuran_nol_byte_ditampilkan_dengan_benar(): void
    {
        $dokumen             = new DokumenPermohonan();
        $dokumen->ukuran_file = 0;

        $this->assertSame('0B', $dokumen->ukuranTerbaca());
    }

    public function test_ukuran_tepat_1023_byte_masih_ditampilkan_sebagai_byte(): void
    {
        $dokumen             = new DokumenPermohonan();
        $dokumen->ukuran_file = 1023;

        $this->assertSame('1023B', $dokumen->ukuranTerbaca());
    }

    public function test_ukuran_dalam_kilobyte_dikonversi_dengan_benar(): void
    {
        $dokumen             = new DokumenPermohonan();
        $dokumen->ukuran_file = 1024;

        $this->assertSame('1KB', $dokumen->ukuranTerbaca());
    }

    public function test_ukuran_kilobyte_dengan_desimal_dibulatkan_dua_angka(): void
    {
        $dokumen             = new DokumenPermohonan();
        $dokumen->ukuran_file = 1536; // 1.5 KB

        $this->assertSame('1.5KB', $dokumen->ukuranTerbaca());
    }

    public function test_ukuran_tepat_1_mb_dikonversi_dari_megabyte(): void
    {
        $dokumen             = new DokumenPermohonan();
        $dokumen->ukuran_file = 1_048_576; // 1 MB

        $this->assertSame('1MB', $dokumen->ukuranTerbaca());
    }

    public function test_ukuran_2_mb_dikonversi_dengan_benar(): void
    {
        $dokumen             = new DokumenPermohonan();
        $dokumen->ukuran_file = 2_097_152; // 2 MB

        $this->assertSame('2MB', $dokumen->ukuranTerbaca());
    }

    public function test_ukuran_1_5_mb_dibulatkan_dua_angka_desimal(): void
    {
        $dokumen             = new DokumenPermohonan();
        $dokumen->ukuran_file = 1_572_864; // 1.5 MB

        $this->assertSame('1.5MB', $dokumen->ukuranTerbaca());
    }

    public function test_ukuran_tepat_di_batas_1047_kb_masih_ditampilkan_sebagai_kb(): void
    {
        $dokumen             = new DokumenPermohonan();
        $dokumen->ukuran_file = 1_048_575; // 1 byte kurang dari 1 MB

        $result = $dokumen->ukuranTerbaca();
        $this->assertStringEndsWith('KB', $result);
    }
}
