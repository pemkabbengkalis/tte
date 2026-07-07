<?php

namespace Tests\Unit\Enums;

use App\Enums\JenisPermohonan;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian unit untuk enum JenisPermohonan.
 *
 * Memverifikasi jenis layanan sertifikat elektronik yang tersedia
 * beserta format options() untuk komponen dropdown antarmuka.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness
 * Level: Unit
 */
class JenisPermohonanEnumTest extends TestCase
{
    // ======================================================================
    // TC-ENUM-JENIS-001: Verifikasi nilai setiap jenis permohonan
    // ======================================================================

    public function test_tte_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('TTE', JenisPermohonan::SertifikatElektronik->value);
    }

    // ======================================================================
    // TC-ENUM-JENIS-002: Verifikasi label() mengembalikan nilai yang sama
    // ======================================================================

    public function test_label_tte_sama_dengan_nilainya(): void
    {
        $this->assertSame(JenisPermohonan::SertifikatElektronik->value, JenisPermohonan::SertifikatElektronik->label());
    }

    // ======================================================================
    // TC-ENUM-JENIS-003: Verifikasi options() untuk dropdown antarmuka
    // ======================================================================

    public function test_options_mengembalikan_array(): void
    {
        $options = JenisPermohonan::options();

        $this->assertIsArray($options);
    }

    public function test_options_memiliki_jumlah_elemen_yang_sesuai(): void
    {
        $options = JenisPermohonan::options();

        $this->assertCount(count(JenisPermohonan::cases()), $options);
    }

    public function test_setiap_option_memiliki_kunci_value_dan_label(): void
    {
        foreach (JenisPermohonan::options() as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }

    public function test_options_mengandung_semua_nilai_enum(): void
    {
        $options   = JenisPermohonan::options();
        $nilaiList = array_column($options, 'value');

        foreach (JenisPermohonan::cases() as $case) {
            $this->assertContains($case->value, $nilaiList);
        }
    }

    // ======================================================================
    // TC-ENUM-JENIS-004: Verifikasi jumlah case
    // ======================================================================

    public function test_enum_memiliki_tepat_empat_jenis(): void
    {
        $this->assertCount(4, JenisPermohonan::cases());
    }
}
