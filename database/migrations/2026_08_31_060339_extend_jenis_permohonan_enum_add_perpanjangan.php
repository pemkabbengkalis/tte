<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan jenis permohonan "Perpanjangan Sertifikat Elektronik" untuk
     * mendukung fitur perpanjangan atas permohonan yang sudah Selesai.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE permohonan MODIFY COLUMN jenis_permohonan ENUM('Penerbitan Sertifikat Elektronik','Perpanjangan Sertifikat Elektronik') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('permohonan')
            ->where('jenis_permohonan', 'Perpanjangan Sertifikat Elektronik')
            ->update(['jenis_permohonan' => 'Penerbitan Sertifikat Elektronik']);

        DB::statement("ALTER TABLE permohonan MODIFY COLUMN jenis_permohonan ENUM('Penerbitan Sertifikat Elektronik') NOT NULL");
    }
};
