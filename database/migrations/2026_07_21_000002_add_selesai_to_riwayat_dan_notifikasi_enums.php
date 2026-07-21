<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan aksi/tipe "selesai" untuk alur baru:
     * Diterima -> (verifikator unggah hasil TTE) -> Selesai.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE riwayat_verifikasi MODIFY COLUMN aksi ENUM('diproses','diterima','ditolak','selesai') NOT NULL");
        DB::statement("ALTER TABLE notifikasi MODIFY COLUMN tipe ENUM('permohonan_baru','pengajuan_ulang','diproses','diterima','ditolak','selesai') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('riwayat_verifikasi')->where('aksi', 'selesai')->delete();
        DB::table('notifikasi')->where('tipe', 'selesai')->update(['tipe' => 'diterima']);

        DB::statement("ALTER TABLE riwayat_verifikasi MODIFY COLUMN aksi ENUM('diproses','diterima','ditolak') NOT NULL");
        DB::statement("ALTER TABLE notifikasi MODIFY COLUMN tipe ENUM('permohonan_baru','pengajuan_ulang','diproses','diterima','ditolak') NOT NULL");
    }
};
