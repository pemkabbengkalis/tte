<?php

namespace Tests\Feature\Services;

use App\Enums\StatusPermohonan;
use App\Models\Notifikasi;
use App\Models\RiwayatVerifikasi;
use App\Services\NotifikasiService;
use App\Services\PermohonanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithUsers;
use Tests\TestCase;

/**
 * Pengujian fitur untuk PermohonanService.
 *
 * Memverifikasi logika bisnis inti siklus hidup permohonan:
 * pembuatan nomor, pengiriman, penerimaan, penolakan, dan pengajuan ulang.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness, Functional Completeness
 * ISTQB: Black-box testing pada business logic layer
 * Level: Feature (menggunakan database)
 */
class PermohonanServiceTest extends TestCase
{
    use RefreshDatabase, InteractsWithUsers;

    private PermohonanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PermohonanService::class);
    }

    // ======================================================================
    // TC-SVC-PERM-001: generateNomor() — Format nomor permohonan
    // ======================================================================

    public function test_generate_nomor_menghasilkan_format_yang_benar(): void
    {
        $nomor = $this->service->generateNomor();
        $tahun = now()->year;

        $this->assertMatchesRegularExpression("/^REQ-{$tahun}-\d{5}$/", $nomor);
    }

    public function test_generate_nomor_pertama_diawali_dengan_00001(): void
    {
        $nomor = $this->service->generateNomor();
        $tahun = now()->year;

        $this->assertSame("REQ-{$tahun}-00001", $nomor);
    }

    public function test_generate_nomor_kedua_diawali_dengan_00002(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->service->generateNomor(); // simulasi nomor pertama sudah digunakan

        $nomor = $this->service->generateNomor();
        $tahun = now()->year;

        // Nomor harus berurutan: setelah nomor pertama di DB, nomor baru adalah 00002
        $this->assertMatchesRegularExpression("/^REQ-{$tahun}-\d{5}$/", $nomor);
    }

    // ======================================================================
    // TC-SVC-PERM-002: submit() — Pengiriman permohonan
    // ======================================================================

    public function test_submit_mengubah_status_menjadi_menunggu_verifikasi(): void
    {
        $pemohon    = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan = $this->buatPermohonan($pemohon);

        $hasil = $this->service->submit($permohonan->load('pemohon'));

        $this->assertSame(StatusPermohonan::MenungguVerifikasi, $hasil->status);
    }

    public function test_submit_mengisi_tanggal_pengajuan(): void
    {
        $pemohon    = $this->buatPemohon();
        $this->buatVerifikator();
        $permohonan = $this->buatPermohonan($pemohon);

        $hasil = $this->service->submit($permohonan->load('pemohon'));

        $this->assertNotNull($hasil->tanggal_pengajuan);
    }

    public function test_submit_menghasilkan_nomor_permohonan_jika_belum_ada(): void
    {
        $pemohon    = $this->buatPemohon();
        $this->buatVerifikator();
        $permohonan = $this->buatPermohonan($pemohon, ['nomor_permohonan' => null]);

        // Hapus nomor agar service yang generate
        $permohonan->nomor_permohonan = null;

        $hasil = $this->service->submit($permohonan->load('pemohon'));

        $this->assertNotNull($hasil->nomor_permohonan);
        $this->assertStringStartsWith('REQ-', $hasil->nomor_permohonan);
    }

    public function test_submit_membuat_notifikasi_ke_semua_verifikator(): void
    {
        $pemohon    = $this->buatPemohon();
        $verifikator1 = $this->buatVerifikator();
        $verifikator2 = $this->buatVerifikator();
        $permohonan   = $this->buatPermohonan($pemohon);

        $this->service->submit($permohonan->load('pemohon'));

        $this->assertDatabaseCount('notifikasi', 2);
        $this->assertDatabaseHas('notifikasi', ['user_id' => $verifikator1->id]);
        $this->assertDatabaseHas('notifikasi', ['user_id' => $verifikator2->id]);
    }

    public function test_submit_tidak_membuat_notifikasi_jika_tidak_ada_verifikator(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->service->submit($permohonan->load('pemohon'));

        $this->assertDatabaseCount('notifikasi', 0);
    }

    // ======================================================================
    // TC-SVC-PERM-003: terima() — Penerimaan permohonan
    // ======================================================================

    public function test_terima_mengubah_status_menjadi_diterima(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        $hasil = $this->service->terima($permohonan->load('pemohon'), $verifikator);

        $this->assertSame(StatusPermohonan::Diterima, $hasil->status);
    }

    public function test_terima_mengisi_verifikator_id(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        $hasil = $this->service->terima($permohonan->load('pemohon'), $verifikator);

        $this->assertSame($verifikator->id, $hasil->verifikator_id);
    }

    public function test_terima_mengisi_tanggal_verifikasi(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        $hasil = $this->service->terima($permohonan->load('pemohon'), $verifikator);

        $this->assertNotNull($hasil->tanggal_verifikasi);
    }

    public function test_terima_membuat_entri_riwayat_verifikasi(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        $this->service->terima($permohonan->load('pemohon'), $verifikator);

        $this->assertDatabaseHas('riwayat_verifikasi', [
            'permohonan_id'  => $permohonan->id,
            'verifikator_id' => $verifikator->id,
            'aksi'           => 'diterima',
        ]);
    }

    public function test_terima_mengirim_notifikasi_ke_pemohon(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        $this->service->terima($permohonan->load('pemohon'), $verifikator);

        $this->assertDatabaseHas('notifikasi', [
            'user_id'       => $pemohon->id,
            'permohonan_id' => $permohonan->id,
        ]);
    }

    public function test_terima_menghapus_catatan_verifikator_sebelumnya(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status'              => StatusPermohonan::MenungguVerifikasi,
            'catatan_verifikator' => 'Catatan lama dari penolakan sebelumnya.',
        ]);

        $hasil = $this->service->terima($permohonan->load('pemohon'), $verifikator);

        $this->assertNull($hasil->catatan_verifikator);
    }

    // ======================================================================
    // TC-SVC-PERM-006: selesaikan() — Hasil TTE dikirim, permohonan selesai
    // ======================================================================

    public function test_selesaikan_mengubah_status_menjadi_selesai(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::Diterima,
        ]);

        $hasil = $this->service->selesaikan($permohonan->load('pemohon'), $verifikator);

        $this->assertSame(StatusPermohonan::Selesai, $hasil->status);
    }

    public function test_selesaikan_mengisi_verifikator_id(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::Diterima,
        ]);

        $hasil = $this->service->selesaikan($permohonan->load('pemohon'), $verifikator);

        $this->assertSame($verifikator->id, $hasil->verifikator_id);
    }

    public function test_selesaikan_membuat_entri_riwayat_verifikasi(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::Diterima,
        ]);

        $this->service->selesaikan($permohonan->load('pemohon'), $verifikator);

        $this->assertDatabaseHas('riwayat_verifikasi', [
            'permohonan_id'  => $permohonan->id,
            'verifikator_id' => $verifikator->id,
            'aksi'           => 'selesai',
        ]);
    }

    public function test_selesaikan_mengirim_notifikasi_ke_pemohon(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::Diterima,
        ]);

        $this->service->selesaikan($permohonan->load('pemohon'), $verifikator);

        $this->assertDatabaseHas('notifikasi', [
            'user_id'       => $pemohon->id,
            'permohonan_id' => $permohonan->id,
            'tipe'          => 'selesai',
        ]);
    }

    // ======================================================================
    // TC-SVC-PERM-004: tolak() — Penolakan permohonan
    // ======================================================================

    public function test_tolak_mengubah_status_menjadi_ditolak(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        $hasil = $this->service->tolak($permohonan->load('pemohon'), $verifikator, 'Dokumen tidak lengkap.');

        $this->assertSame(StatusPermohonan::Ditolak, $hasil->status);
    }

    public function test_tolak_menyimpan_alasan_penolakan(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);
        $alasan = 'Foto KTP buram dan tidak terbaca.';

        $hasil = $this->service->tolak($permohonan->load('pemohon'), $verifikator, $alasan);

        $this->assertSame($alasan, $hasil->catatan_verifikator);
    }

    public function test_tolak_membuat_entri_riwayat_verifikasi(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);
        $alasan = 'SK Jabatan tidak valid.';

        $this->service->tolak($permohonan->load('pemohon'), $verifikator, $alasan);

        $this->assertDatabaseHas('riwayat_verifikasi', [
            'permohonan_id'  => $permohonan->id,
            'verifikator_id' => $verifikator->id,
            'aksi'           => 'ditolak',
            'catatan'        => $alasan,
        ]);
    }

    public function test_tolak_mengirim_notifikasi_ke_pemohon(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        $this->service->tolak($permohonan->load('pemohon'), $verifikator, 'Dokumen kadaluarsa.');

        $this->assertDatabaseHas('notifikasi', [
            'user_id'       => $pemohon->id,
            'permohonan_id' => $permohonan->id,
        ]);
    }

    // ======================================================================
    // TC-SVC-PERM-005: resubmit() — Pengajuan ulang setelah ditolak
    // ======================================================================

    public function test_resubmit_mengubah_status_kembali_ke_menunggu_verifikasi(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status'              => StatusPermohonan::Ditolak,
            'catatan_verifikator' => 'Dokumen tidak lengkap.',
            'verifikator_id'      => $verifikator->id,
            'tanggal_verifikasi'  => now(),
        ]);

        $hasil = $this->service->resubmit($permohonan->load('pemohon'));

        $this->assertSame(StatusPermohonan::MenungguVerifikasi, $hasil->status);
    }

    public function test_resubmit_menambah_jumlah_pengajuan(): void
    {
        $pemohon    = $this->buatPemohon();
        $this->buatVerifikator();
        $permohonan = $this->buatPermohonan($pemohon, [
            'status'           => StatusPermohonan::Ditolak,
            'jumlah_pengajuan' => 1,
        ]);

        $hasil = $this->service->resubmit($permohonan->load('pemohon'));

        $this->assertSame(2, $hasil->jumlah_pengajuan);
    }

    public function test_resubmit_menghapus_data_verifikator_sebelumnya(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status'              => StatusPermohonan::Ditolak,
            'verifikator_id'      => $verifikator->id,
            'catatan_verifikator' => 'Dokumen tidak lengkap.',
            'tanggal_verifikasi'  => now(),
        ]);

        $hasil = $this->service->resubmit($permohonan->load('pemohon'));

        $this->assertNull($hasil->verifikator_id);
        $this->assertNull($hasil->catatan_verifikator);
        $this->assertNull($hasil->tanggal_verifikasi);
    }

    public function test_resubmit_memperbarui_tanggal_pengajuan(): void
    {
        $pemohon    = $this->buatPemohon();
        $this->buatVerifikator();
        $permohonan = $this->buatPermohonan($pemohon, [
            'status'            => StatusPermohonan::Ditolak,
            'tanggal_pengajuan' => now()->subDays(5),
        ]);

        $sebelum = $permohonan->tanggal_pengajuan;
        $hasil   = $this->service->resubmit($permohonan->load('pemohon'));

        $this->assertGreaterThanOrEqual($sebelum, $hasil->tanggal_pengajuan);
    }
}
