<?php

namespace Tests\Feature\Models;

use App\Enums\JenisDokumen;
use App\Enums\StatusPermohonan;
use App\Models\DokumenPermohonan;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\RiwayatVerifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithUsers;
use Tests\TestCase;

/**
 * Pengujian fitur untuk model Permohonan.
 *
 * Memverifikasi relasi antar model, metode helper,
 * dan integritas data permohonan sertifikat elektronik.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness
 * Level: Feature (menggunakan database)
 */
class PermohonanModelTest extends TestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-MODEL-PERM-001: Relasi dengan model User (pemohon dan verifikator)
    // ======================================================================

    public function test_relasi_pemohon_mengembalikan_user_yang_benar(): void
    {
        $pemohon     = $this->buatPemohon();
        $permohonan  = $this->buatPermohonan($pemohon);

        $this->assertNotNull($permohonan->pemohon);
        $this->assertSame($pemohon->id, $permohonan->pemohon->id);
    }

    public function test_relasi_verifikator_mengembalikan_null_jika_belum_diverifikasi(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->assertNull($permohonan->verifikator);
    }

    public function test_relasi_verifikator_mengembalikan_user_setelah_verifikasi(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'verifikator_id'    => $verifikator->id,
            'status'            => StatusPermohonan::Diterima,
            'tanggal_verifikasi' => now(),
        ]);

        $this->assertSame($verifikator->id, $permohonan->verifikator->id);
    }

    // ======================================================================
    // TC-MODEL-PERM-002: Relasi dengan DokumenPermohonan
    // ======================================================================

    public function test_relasi_dokumen_mengembalikan_collection(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        DokumenPermohonan::create([
            'permohonan_id' => $permohonan->id,
            'jenis_dokumen' => JenisDokumen::SuratPermohonan,
            'nama_file'     => 'surat.pdf',
            'path_file'     => 'dokumen/surat.pdf',
            'ukuran_file'   => 102400,
            'mime_type'     => 'application/pdf',
            'versi'         => 1,
        ]);

        $this->assertCount(1, $permohonan->dokumen);
    }

    // ======================================================================
    // TC-MODEL-PERM-003: Metode dokumenTerbaru()
    // ======================================================================

    public function test_dokumenTerbaru_mengembalikan_dokumen_unik_per_jenis(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        // Versi 1 dan versi 2 dari dokumen yang sama
        foreach ([1, 2] as $versi) {
            DokumenPermohonan::create([
                'permohonan_id' => $permohonan->id,
                'jenis_dokumen' => JenisDokumen::Ktp,
                'nama_file'     => "ktp_v{$versi}.pdf",
                'path_file'     => "dokumen/ktp_v{$versi}.pdf",
                'ukuran_file'   => 102400,
                'mime_type'     => 'application/pdf',
                'versi'         => $versi,
            ]);
        }

        $terbaru = $permohonan->dokumenTerbaru();

        $this->assertCount(1, $terbaru);
    }

    public function test_dokumenTerbaru_mengembalikan_versi_tertinggi(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        foreach ([1, 2, 3] as $versi) {
            DokumenPermohonan::create([
                'permohonan_id' => $permohonan->id,
                'jenis_dokumen' => JenisDokumen::SkJabatan,
                'nama_file'     => "sk_jabatan_v{$versi}.pdf",
                'path_file'     => "dokumen/sk_jabatan_v{$versi}.pdf",
                'ukuran_file'   => 102400,
                'mime_type'     => 'application/pdf',
                'versi'         => $versi,
            ]);
        }

        $terbaru = $permohonan->dokumenTerbaru();
        $dokumen = $terbaru->first();

        $this->assertEquals(3, $dokumen->versi);
    }

    public function test_dokumenTerbaru_mengembalikan_dokumen_berbeda_jenis(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $jenisList = [JenisDokumen::Ktp, JenisDokumen::SkJabatan, JenisDokumen::SuratPermohonan];

        foreach ($jenisList as $jenis) {
            DokumenPermohonan::create([
                'permohonan_id' => $permohonan->id,
                'jenis_dokumen' => $jenis,
                'nama_file'     => $jenis->value . '.pdf',
                'path_file'     => 'dokumen/' . $jenis->value . '.pdf',
                'ukuran_file'   => 102400,
                'mime_type'     => 'application/pdf',
                'versi'         => 1,
            ]);
        }

        $terbaru = $permohonan->dokumenTerbaru();

        $this->assertCount(3, $terbaru);
    }

    // ======================================================================
    // TC-MODEL-PERM-004: UUID sebagai primary key
    // ======================================================================

    public function test_permohonan_menggunakan_uuid_sebagai_primary_key(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $permohonan->id
        );
    }

    // ======================================================================
    // TC-MODEL-PERM-005: Soft deletes
    // ======================================================================

    public function test_menghapus_permohonan_menggunakan_soft_delete(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);
        $id         = $permohonan->id;

        $permohonan->delete();

        $this->assertSoftDeleted('permohonan', ['id' => $id]);
    }

    // ======================================================================
    // TC-MODEL-PERM-006: Relasi RiwayatVerifikasi dan Notifikasi
    // ======================================================================

    public function test_relasi_riwayat_verifikasi_dikembalikan_sebagai_collection(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::MenungguVerifikasi]);

        RiwayatVerifikasi::create([
            'permohonan_id'  => $permohonan->id,
            'verifikator_id' => $verifikator->id,
            'aksi'           => 'diterima',
            'catatan'        => null,
        ]);

        $this->assertCount(1, $permohonan->riwayatVerifikasi);
    }
}
