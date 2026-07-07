<?php

namespace Tests\Feature\Services;

use App\Enums\TipeNotifikasi;
use App\Services\NotifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithUsers;
use Tests\TestCase;

/**
 * Pengujian fitur untuk NotifikasiService.
 *
 * Memverifikasi pengiriman notifikasi ke penerima yang tepat
 * pada setiap perubahan status permohonan sertifikat elektronik.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness
 * ISTQB: Boundary value analysis pada penerima notifikasi
 * Level: Feature (menggunakan database)
 */
class NotifikasiServiceTest extends TestCase
{
    use RefreshDatabase, InteractsWithUsers;

    private NotifikasiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotifikasiService::class);
    }

    // ======================================================================
    // TC-SVC-NOTIF-001: permohonanBaru() — Notifikasi ke semua verifikator
    // ======================================================================

    public function test_permohonan_baru_mengirim_notifikasi_ke_semua_verifikator(): void
    {
        $pemohon      = $this->buatPemohon();
        $verifikator1 = $this->buatVerifikator();
        $verifikator2 = $this->buatVerifikator();
        $permohonan   = $this->buatPermohonan($pemohon);

        $this->service->permohonanBaru($permohonan->load('pemohon'));

        $this->assertDatabaseHas('notifikasi', ['user_id' => $verifikator1->id]);
        $this->assertDatabaseHas('notifikasi', ['user_id' => $verifikator2->id]);
    }

    public function test_permohonan_baru_tidak_mengirim_ke_pemohon(): void
    {
        $pemohon     = $this->buatPemohon();
        $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon);

        $this->service->permohonanBaru($permohonan->load('pemohon'));

        $this->assertDatabaseMissing('notifikasi', ['user_id' => $pemohon->id]);
    }

    public function test_permohonan_baru_tipe_notifikasi_adalah_permohonan_baru(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon);

        $this->service->permohonanBaru($permohonan->load('pemohon'));

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $verifikator->id,
            'tipe'    => TipeNotifikasi::PermohonanBaru->value,
        ]);
    }

    public function test_permohonan_baru_tidak_membuat_notifikasi_jika_tidak_ada_verifikator(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->service->permohonanBaru($permohonan->load('pemohon'));

        $this->assertDatabaseCount('notifikasi', 0);
    }

    public function test_permohonan_baru_tidak_mengirim_ke_verifikator_nonaktif(): void
    {
        $pemohon             = $this->buatPemohon();
        $verifikatorAktif    = $this->buatVerifikator();
        $verifikatorNonaktif = $this->buatVerifikator(['email_verified_at' => null]);
        $permohonan          = $this->buatPermohonan($pemohon);

        $this->service->permohonanBaru($permohonan->load('pemohon'));

        $this->assertDatabaseHas('notifikasi', ['user_id' => $verifikatorAktif->id]);
        $this->assertDatabaseMissing('notifikasi', ['user_id' => $verifikatorNonaktif->id]);
    }

    public function test_permohonan_baru_pesan_mengandung_nama_pemohon(): void
    {
        $pemohon     = $this->buatPemohon(['nama_lengkap' => 'Budi Santoso']);
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon);

        $this->service->permohonanBaru($permohonan->load('pemohon'));

        $notifikasi = \App\Models\Notifikasi::where('user_id', $verifikator->id)->first();
        $this->assertStringContainsString('Budi Santoso', $notifikasi->pesan);
    }

    // ======================================================================
    // TC-SVC-NOTIF-002: pengajuanUlang() — Notifikasi pengajuan ulang
    // ======================================================================

    public function test_pengajuan_ulang_mengirim_notifikasi_ke_semua_verifikator(): void
    {
        $pemohon      = $this->buatPemohon();
        $verifikator1 = $this->buatVerifikator();
        $verifikator2 = $this->buatVerifikator();
        $permohonan   = $this->buatPermohonan($pemohon, ['jumlah_pengajuan' => 2]);

        $this->service->pengajuanUlang($permohonan->load('pemohon'));

        $this->assertDatabaseHas('notifikasi', ['user_id' => $verifikator1->id]);
        $this->assertDatabaseHas('notifikasi', ['user_id' => $verifikator2->id]);
    }

    public function test_pengajuan_ulang_tipe_notifikasi_adalah_pengajuan_ulang(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['jumlah_pengajuan' => 2]);

        $this->service->pengajuanUlang($permohonan->load('pemohon'));

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $verifikator->id,
            'tipe'    => TipeNotifikasi::PengajuanUlang->value,
        ]);
    }

    // ======================================================================
    // TC-SVC-NOTIF-003: diterima() — Notifikasi penerimaan ke pemohon
    // ======================================================================

    public function test_diterima_mengirim_notifikasi_hanya_ke_pemohon(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon);

        $this->service->diterima($permohonan->load('pemohon'));

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $pemohon->id,
            'tipe'    => TipeNotifikasi::Diterima->value,
        ]);
        $this->assertDatabaseMissing('notifikasi', ['user_id' => $verifikator->id]);
    }

    public function test_diterima_notifikasi_belum_dibaca_secara_default(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->service->diterima($permohonan->load('pemohon'));

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $pemohon->id,
            'is_read' => false,
        ]);
    }

    public function test_diterima_judul_notifikasi_benar(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->service->diterima($permohonan->load('pemohon'));

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $pemohon->id,
            'judul'   => 'Permohonan diterima',
        ]);
    }

    // ======================================================================
    // TC-SVC-NOTIF-004: ditolak() — Notifikasi penolakan dengan alasan
    // ======================================================================

    public function test_ditolak_mengirim_notifikasi_hanya_ke_pemohon(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon);
        $alasan      = 'Berkas tidak jelas.';

        $this->service->ditolak($permohonan->load('pemohon'), $alasan);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $pemohon->id,
            'tipe'    => TipeNotifikasi::Ditolak->value,
        ]);
        $this->assertDatabaseMissing('notifikasi', ['user_id' => $verifikator->id]);
    }

    public function test_ditolak_pesan_mengandung_alasan_penolakan(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);
        $alasan     = 'SK Jabatan sudah kadaluarsa sejak 2 tahun lalu.';

        $this->service->ditolak($permohonan->load('pemohon'), $alasan);

        $notifikasi = \App\Models\Notifikasi::where('user_id', $pemohon->id)->first();
        $this->assertStringContainsString($alasan, $notifikasi->pesan);
    }

    public function test_ditolak_notifikasi_belum_dibaca_secara_default(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        $this->service->ditolak($permohonan->load('pemohon'), 'Alasan penolakan.');

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $pemohon->id,
            'is_read' => false,
        ]);
    }
}
