<?php

namespace Tests\Feature\Policy;

use App\Enums\TipeNotifikasi;
use App\Models\Notifikasi;
use App\Policies\NotifikasiPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithUsers;
use Tests\TestCase;

/**
 * Pengujian fitur untuk NotifikasiPolicy.
 *
 * Memverifikasi aturan otorisasi akses notifikasi:
 * pengguna hanya dapat melihat dan memperbarui notifikasinya sendiri.
 *
 * Standar: ISO/IEC 25010 – Security (Authorization, Privacy)
 * Level: Feature (menggunakan database)
 */
class NotifikasiPolicyTest extends TestCase
{
    use RefreshDatabase, InteractsWithUsers;

    private NotifikasiPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new NotifikasiPolicy();
    }

    private function buatNotifikasi($user): Notifikasi
    {
        $pemohon    = $this->buatPemohon();
        $permohonan = $this->buatPermohonan($pemohon);

        return Notifikasi::create([
            'user_id'       => $user->id,
            'permohonan_id' => $permohonan->id,
            'judul'         => 'Notifikasi Test',
            'pesan'         => 'Ini adalah pesan notifikasi.',
            'tipe'          => TipeNotifikasi::Diterima,
            'is_read'       => false,
        ]);
    }

    // ======================================================================
    // TC-POLICY-NOTIF-001: view() — Akses lihat notifikasi
    // ======================================================================

    public function test_view_mengizinkan_pemilik_melihat_notifikasinya_sendiri(): void
    {
        $user       = $this->buatPemohon();
        $notifikasi = $this->buatNotifikasi($user);

        $this->assertTrue($this->policy->view($user, $notifikasi));
    }

    public function test_view_menolak_pengguna_melihat_notifikasi_orang_lain(): void
    {
        $user1      = $this->buatPemohon();
        $user2      = $this->buatPemohon();
        $notifikasi = $this->buatNotifikasi($user1);

        $this->assertFalse($this->policy->view($user2, $notifikasi));
    }

    public function test_view_menolak_verifikator_melihat_notifikasi_pemohon(): void
    {
        $pemohon     = $this->buatPemohon();
        $verifikator = $this->buatVerifikator();
        $notifikasi  = $this->buatNotifikasi($pemohon);

        $this->assertFalse($this->policy->view($verifikator, $notifikasi));
    }

    // ======================================================================
    // TC-POLICY-NOTIF-002: update() — Akses tandai notifikasi sebagai dibaca
    // ======================================================================

    public function test_update_mengizinkan_pemilik_memperbarui_notifikasinya_sendiri(): void
    {
        $user       = $this->buatPemohon();
        $notifikasi = $this->buatNotifikasi($user);

        $this->assertTrue($this->policy->update($user, $notifikasi));
    }

    public function test_update_menolak_pengguna_memperbarui_notifikasi_orang_lain(): void
    {
        $user1      = $this->buatPemohon();
        $user2      = $this->buatPemohon();
        $notifikasi = $this->buatNotifikasi($user1);

        $this->assertFalse($this->policy->update($user2, $notifikasi));
    }

    public function test_update_menolak_admin_memperbarui_notifikasi_pengguna_lain(): void
    {
        $pemohon    = $this->buatPemohon();
        $admin      = $this->buatAdmin();
        $notifikasi = $this->buatNotifikasi($pemohon);

        // NotifikasiPolicy tidak memiliki before(), sehingga admin pun tidak punya hak atas notifikasi orang lain
        $this->assertFalse($this->policy->update($admin, $notifikasi));
    }
}
