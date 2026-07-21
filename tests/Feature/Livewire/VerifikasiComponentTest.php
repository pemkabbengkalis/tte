<?php

namespace Tests\Feature\Livewire;

use App\Enums\StatusPermohonan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian komponen Livewire halaman verifikasi permohonan.
 *
 * Memverifikasi fungsionalitas verifikator: penerimaan permohonan,
 * penolakan dengan alasan, validasi input alasan penolakan,
 * dan kontrol akses berbasis status permohonan.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness, Security (Authorization)
 * Level: Feature (Livewire component test)
 */
class VerifikasiComponentTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-LW-VER-001: Rendering dan mounting komponen
    // ======================================================================

    public function test_dashboard_verifikator_dapat_diakses(): void
    {
        $verifikator = $this->buatVerifikator();

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.dashboard')
            ->assertStatus(200);
    }

    public function test_detail_permohonan_verifikator_dapat_diakses_untuk_permohonan_menunggu(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
            'tanggal_pengajuan' => now(),
        ]);

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->assertStatus(200);
    }

    // ======================================================================
    // TC-LW-VER-002: Penerimaan permohonan
    // ======================================================================

    public function test_terima_berhasil_mengubah_status_menjadi_diterima(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status'            => StatusPermohonan::MenungguVerifikasi,
            'tanggal_pengajuan' => now(),
        ]);

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->call('terima')
            ->assertHasNoErrors()
            ->assertRedirect(route('verifikator.dashboard'));

        $this->assertDatabaseHas('permohonan', [
            'id'     => $permohonan->id,
            'status' => StatusPermohonan::Diterima->value,
        ]);
    }

    public function test_terima_membuat_entri_riwayat_verifikasi(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->call('terima');

        $this->assertDatabaseHas('riwayat_verifikasi', [
            'permohonan_id'  => $permohonan->id,
            'verifikator_id' => $verifikator->id,
            'aksi'           => 'diterima',
        ]);
    }

    // ======================================================================
    // TC-LW-VER-003: Penolakan permohonan
    // ======================================================================

    public function test_tolak_berhasil_mengubah_status_menjadi_ditolak(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status'            => StatusPermohonan::MenungguVerifikasi,
            'tanggal_pengajuan' => now(),
        ]);

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->set('alasanTolak', 'Dokumen KTP buram dan tidak terbaca oleh sistem.')
            ->call('tolak')
            ->assertHasNoErrors()
            ->assertRedirect(route('verifikator.dashboard'));

        $this->assertDatabaseHas('permohonan', [
            'id'     => $permohonan->id,
            'status' => StatusPermohonan::Ditolak->value,
        ]);
    }

    public function test_tolak_menyimpan_alasan_penolakan_di_database(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);
        $alasan = 'SK Jabatan tidak sesuai dengan jabatan saat ini.';

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->set('alasanTolak', $alasan)
            ->call('tolak');

        $this->assertDatabaseHas('permohonan', [
            'id'                  => $permohonan->id,
            'catatan_verifikator' => $alasan,
        ]);
    }

    // ======================================================================
    // TC-LW-VER-004: Validasi alasan penolakan
    // ======================================================================

    public function test_tolak_gagal_jika_alasan_kosong(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->set('alasanTolak', '')
            ->call('tolak')
            ->assertHasErrors(['alasanTolak']);
    }

    public function test_tolak_gagal_jika_alasan_kurang_dari_10_karakter(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->set('alasanTolak', 'Pendek')  // Kurang dari 10 karakter
            ->call('tolak')
            ->assertHasErrors(['alasanTolak']);
    }

    // ======================================================================
    // TC-LW-VER-005: Kontrol akses — verifikator tidak dapat verifikasi permohonan non-menunggu
    // ======================================================================

    public function test_terima_gagal_jika_permohonan_berstatus_draft(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::Draft,
        ]);

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->call('terima')
            ->assertForbidden();
    }

    // ======================================================================
    // TC-LW-VER-006: Kontrol modal tolak
    // ======================================================================

    public function test_buka_modal_tolak_mengubah_modal_menjadi_true(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->assertSet('modalTolak', false)
            ->call('bukaModalTolak')
            ->assertSet('modalTolak', true);
    }

    public function test_batal_tolak_menutup_modal(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, [
            'status' => StatusPermohonan::MenungguVerifikasi,
        ]);

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->call('bukaModalTolak')
            ->assertSet('modalTolak', true)
            ->call('batalTolak')
            ->assertSet('modalTolak', false);
    }

    // ======================================================================
    // TC-LW-VER-007: kirimTte() — Pengiriman hasil TTE
    // ======================================================================

    public function test_kirim_tte_berhasil_menyelesaikan_permohonan(): void
    {
        Storage::fake('local');

        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Diterima]);
        $file        = UploadedFile::fake()->createWithContent('hasil-tte.pdf', "%PDF-1.4\n%%EOF");

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->set('hasil_tte', $file)
            ->call('kirimTte')
            ->assertHasNoErrors()
            ->assertRedirect(route('verifikator.dashboard'));

        $this->assertDatabaseHas('permohonan', [
            'id'     => $permohonan->id,
            'status' => StatusPermohonan::Selesai->value,
        ]);

        $this->assertDatabaseHas('dokumen_permohonan', [
            'permohonan_id' => $permohonan->id,
            'jenis_dokumen' => 'hasil_tte',
        ]);

        $this->assertDatabaseHas('riwayat_verifikasi', [
            'permohonan_id'  => $permohonan->id,
            'verifikator_id' => $verifikator->id,
            'aksi'           => 'selesai',
        ]);
    }

    public function test_kirim_tte_gagal_jika_berkas_tidak_diunggah(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Diterima]);

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->call('kirimTte')
            ->assertHasErrors(['hasil_tte']);

        $this->assertDatabaseHas('permohonan', [
            'id'     => $permohonan->id,
            'status' => StatusPermohonan::Diterima->value,
        ]);
    }

    public function test_kirim_tte_gagal_jika_tipe_file_tidak_valid(): void
    {
        Storage::fake('local');

        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Diterima]);
        $file        = UploadedFile::fake()->create('hasil.exe', 100, 'application/x-msdownload');

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->set('hasil_tte', $file)
            ->call('kirimTte')
            ->assertHasErrors(['hasil_tte']);
    }

    public function test_kirim_tte_ditolak_untuk_permohonan_yang_belum_diterima(): void
    {
        Storage::fake('local');

        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $permohonan  = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Diproses]);
        $file        = UploadedFile::fake()->create('hasil-tte.pdf', 100, 'application/pdf');

        Livewire::actingAs($verifikator)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->set('hasil_tte', $file)
            ->call('kirimTte')
            ->assertForbidden();
    }

    public function test_kirim_tte_ditolak_untuk_pemohon(): void
    {
        Storage::fake('local');

        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, ['status' => StatusPermohonan::Diterima]);
        $file       = UploadedFile::fake()->create('hasil-tte.pdf', 100, 'application/pdf');

        Livewire::actingAs($pemohon)
            ->test('pages::verifikator.detail-permohonan', ['permohonan' => $permohonan])
            ->set('hasil_tte', $file)
            ->call('kirimTte')
            ->assertForbidden();
    }
}
