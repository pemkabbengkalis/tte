<?php

namespace Tests\Feature\Livewire;

use App\Enums\JenisPermohonan;
use App\Enums\StatusPermohonan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;
use Tests\Support\InteractsWithUsers;

/**
 * Pengujian komponen Livewire halaman pembuatan permohonan.
 *
 * Memverifikasi fungsionalitas formulir permohonan sertifikat elektronik:
 * penyimpanan draft, pengiriman permohonan, validasi dokumen wajib,
 * dan upload berkas.
 *
 * Standar: ISO/IEC 25010 – Functional Correctness, Data Integrity
 * Level: Feature (Livewire component test)
 */
class PermohonanComponentTest extends FeatureTestCase
{
    use RefreshDatabase, InteractsWithUsers;

    // ======================================================================
    // TC-LW-PERM-001: Rendering dan mounting komponen
    // ======================================================================

    public function test_komponen_buat_permohonan_dapat_dirender_oleh_pemohon(): void
    {
        $pemohon = $this->buatPemohon();

        Livewire::actingAs($pemohon)
            ->test('pages::pemohon.buat-permohonan')
            ->assertStatus(200);
    }

    public function test_komponen_mount_mengisi_data_diri_dari_profil_pengguna(): void
    {
        $pemohon = $this->buatPemohon([
            'pangkat_gol' => 'Penata Muda / III-a',
            'jabatan'     => 'Staf IT',
            'instansi'    => 'Dinas Kominfotik',
            'unit_kerja'  => 'Bidang Persandian',
            'no_hp'       => '081234567890',
        ]);

        Livewire::actingAs($pemohon)
            ->test('pages::pemohon.buat-permohonan')
            ->assertSet('pangkat_gol', 'Penata Muda / III-a')
            ->assertSet('jabatan', 'Staf IT')
            ->assertSet('instansi', 'Dinas Kominfotik')
            ->assertSet('unit_kerja', 'Bidang Persandian')
            ->assertSet('no_hp', '081234567890');
    }

    // ======================================================================
    // TC-LW-PERM-002: Simpan draft
    // ======================================================================

    public function test_simpan_draft_berhasil_membuat_permohonan_berstatus_draft(): void
    {
        Storage::fake('local');

        $pemohon = $this->buatPemohon();
        $file    = UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf');

        Livewire::actingAs($pemohon)
            ->test('pages::pemohon.buat-permohonan')
            ->set('jenis_permohonan', JenisPermohonan::SertifikatElektronik->value)
            ->set('surat_permohonan', $file)
            ->call('simpanDraft')
            ->assertHasNoErrors()
            ->assertRedirect(route('pemohon.dashboard'));

        $this->assertDatabaseHas('permohonan', [
            'pemohon_id'      => $pemohon->id,
            'jenis_permohonan' => JenisPermohonan::SertifikatElektronik->value,
            'status'          => StatusPermohonan::Draft->value,
        ]);
    }

    // ======================================================================
    // TC-LW-PERM-003: Validasi jenis permohonan wajib
    // ======================================================================

    public function test_simpan_draft_gagal_jika_jenis_permohonan_tidak_dipilih(): void
    {
        $pemohon = $this->buatPemohon();

        Livewire::actingAs($pemohon)
            ->test('pages::pemohon.buat-permohonan')
            ->set('jenis_permohonan', '') // Tidak dipilih
            ->call('simpanDraft')
            ->assertHasErrors(['jenis_permohonan']);
    }

    public function test_simpan_draft_gagal_jika_jenis_permohonan_tidak_valid(): void
    {
        $pemohon = $this->buatPemohon();

        Livewire::actingAs($pemohon)
            ->test('pages::pemohon.buat-permohonan')
            ->set('jenis_permohonan', 'JENIS_TIDAK_VALID')
            ->call('simpanDraft')
            ->assertHasErrors(['jenis_permohonan']);
    }

    // ======================================================================
    // TC-LW-PERM-004: Kirim permohonan — validasi dokumen wajib
    // ======================================================================

    public function test_kirim_permohonan_gagal_jika_dokumen_wajib_tidak_ada(): void
    {
        $pemohon = $this->buatPemohon([
            'no_hp'       => '081234567890',
            'pangkat_gol' => 'Penata / III-c',
            'jabatan'     => 'Staf',
            'instansi'    => 'Dinas Test',
            'unit_kerja'  => 'Bidang Test',
        ]);

        Livewire::actingAs($pemohon)
            ->test('pages::pemohon.buat-permohonan')
            ->set('jenis_permohonan', JenisPermohonan::SertifikatElektronik->value)
            // Tidak ada dokumen yang diunggah
            ->call('kirim')
            ->assertHasErrors(); // Harus ada error dokumen wajib
    }

    // ======================================================================
    // TC-LW-PERM-005: Memuat permohonan draft yang sudah ada
    // ======================================================================

    public function test_komponen_memuat_draft_yang_sudah_ada(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon, [
            'status'          => StatusPermohonan::Draft,
            'jenis_permohonan' => JenisPermohonan::SertifikatElektronik,
        ]);

        Livewire::actingAs($pemohon)
            ->test('pages::pemohon.buat-permohonan', ['permohonan' => $permohonan->id])
            ->assertSet('jenis_permohonan', JenisPermohonan::SertifikatElektronik->value)
            ->assertSet('permohonanId', $permohonan->id);
    }

    // ======================================================================
    // TC-LW-PERM-006: Validasi tipe file
    // ======================================================================

    public function test_simpan_draft_gagal_jika_tipe_file_tidak_valid(): void
    {
        Storage::fake('local');

        $pemohon        = $this->buatPemohon();
        $fileTidakValid = UploadedFile::fake()->create('dokumen.exe', 100, 'application/x-msdownload');

        Livewire::actingAs($pemohon)
            ->test('pages::pemohon.buat-permohonan')
            ->set('jenis_permohonan', JenisPermohonan::SertifikatElektronik->value)
            ->set('surat_permohonan', $fileTidakValid)
            ->call('simpanDraft')
            ->assertHasErrors(['surat_permohonan']);
    }

    // ======================================================================
    // TC-LW-PERM-007: Dashboard pemohon menampilkan daftar permohonan
    // ======================================================================

    public function test_dashboard_pemohon_dapat_diakses(): void
    {
        $pemohon = $this->buatPemohon();

        Livewire::actingAs($pemohon)
            ->test('pages::pemohon.dashboard')
            ->assertStatus(200);
    }

    public function test_detail_permohonan_dapat_diakses_oleh_pemohon_pemilik(): void
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        Livewire::actingAs($pemohon)
            ->test('pages::pemohon.detail-permohonan', ['permohonan' => $permohonan])
            ->assertStatus(200);
    }
}
