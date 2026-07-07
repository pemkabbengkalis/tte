<?php

namespace Tests\Unit\Enums;

use App\Enums\JenisDokumen;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian unit untuk enum JenisDokumen.
 *
 * Memverifikasi jenis dokumen persyaratan yang diperlukan dalam
 * proses permohonan sertifikat elektronik.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness
 * Level: Unit
 */
class JenisDokumenEnumTest extends TestCase
{
    // ======================================================================
    // TC-ENUM-DOK-001: Verifikasi nilai string setiap jenis dokumen
    // ======================================================================

    public function test_surat_permohonan_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('surat_permohonan', JenisDokumen::SuratPermohonan->value);
    }

    public function test_sk_jabatan_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('sk_jabatan', JenisDokumen::SkJabatan->value);
    }

    public function test_sk_pangkat_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('sk_pangkat', JenisDokumen::SkPangkat->value);
    }

    public function test_ktp_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('ktp', JenisDokumen::Ktp->value);
    }

    public function test_email_dinas_memiliki_nilai_yang_benar(): void
    {
        $this->assertSame('email_dinas', JenisDokumen::EmailDinas->value);
    }

    // ======================================================================
    // TC-ENUM-DOK-002: Verifikasi label human-readable setiap dokumen
    // ======================================================================

    public function test_label_surat_permohonan_mengandung_teks_yang_benar(): void
    {
        $this->assertStringContainsString(
            'Surat Permohonan',
            JenisDokumen::SuratPermohonan->label()
        );
    }

    public function test_label_sk_jabatan_mengandung_teks_yang_benar(): void
    {
        $this->assertStringContainsString('SK Jabatan', JenisDokumen::SkJabatan->label());
    }

    public function test_label_sk_pangkat_mengandung_teks_yang_benar(): void
    {
        $this->assertStringContainsString('SK Pangkat', JenisDokumen::SkPangkat->label());
    }

    public function test_label_ktp_mengandung_teks_yang_benar(): void
    {
        $this->assertStringContainsString('KTP', JenisDokumen::Ktp->label());
    }

    public function test_label_email_dinas_mengandung_teks_yang_benar(): void
    {
        $this->assertStringContainsString('Email Dinas', JenisDokumen::EmailDinas->label());
    }

    // ======================================================================
    // TC-ENUM-DOK-003: Verifikasi jumlah dan pembacaan
    // ======================================================================

    public function test_enum_memiliki_tepat_lima_jenis_dokumen(): void
    {
        $this->assertCount(5, JenisDokumen::cases());
    }

    public function test_label_tidak_boleh_kosong(): void
    {
        foreach (JenisDokumen::cases() as $case) {
            $this->assertNotEmpty($case->label(), "Label untuk {$case->name} tidak boleh kosong.");
        }
    }

    public function test_tryFrom_mengembalikan_null_untuk_nilai_tidak_valid(): void
    {
        $this->assertNull(JenisDokumen::tryFrom('dokumen_tidak_ada'));
    }
}
