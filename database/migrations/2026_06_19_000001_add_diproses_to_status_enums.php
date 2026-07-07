<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan status/aksi/tipe "diproses" pada alur permohonan baru:
     * Menunggu Verifikasi -> (lengkap) Diproses -> Diterima.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE permohonan MODIFY COLUMN status ENUM('draft','menunggu_verifikasi','diproses','diterima','ditolak','selesai') NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE riwayat_verifikasi MODIFY COLUMN aksi ENUM('diproses','diterima','ditolak') NOT NULL");
        DB::statement("ALTER TABLE notifikasi MODIFY COLUMN tipe ENUM('permohonan_baru','pengajuan_ulang','diproses','diterima','ditolak') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Kembalikan data 'diproses' ke status terdekat sebelum mengecilkan enum.
        DB::table('permohonan')->where('status', 'diproses')->update(['status' => 'menunggu_verifikasi']);
        DB::table('riwayat_verifikasi')->where('aksi', 'diproses')->delete();
        DB::table('notifikasi')->where('tipe', 'diproses')->update(['tipe' => 'diterima']);

        DB::statement("ALTER TABLE permohonan MODIFY COLUMN status ENUM('draft','menunggu_verifikasi','diterima','ditolak','selesai') NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE riwayat_verifikasi MODIFY COLUMN aksi ENUM('diterima','ditolak') NOT NULL");
        DB::statement("ALTER TABLE notifikasi MODIFY COLUMN tipe ENUM('permohonan_baru','pengajuan_ulang','diterima','ditolak') NOT NULL");
    }
};
