<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Jenis permohonan kini hanya satu: "Penerbitan Sertifikat Elektronik".
     * Selaraskan kolom enum di database dengan App\Enums\JenisPermohonan.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1. Perluas enum agar memuat nilai baru tanpa truncation.
        DB::statement("ALTER TABLE permohonan MODIFY COLUMN jenis_permohonan ENUM('TTE','Pengamanan Dokumen','Pengamanan Email','Pengamanan Web','Penerbitan Sertifikat Elektronik') NOT NULL");

        // 2. Konversi seluruh data lama ke jenis tunggal.
        DB::table('permohonan')->update(['jenis_permohonan' => 'Penerbitan Sertifikat Elektronik']);

        // 3. Persempit enum menjadi satu jenis saja.
        DB::statement("ALTER TABLE permohonan MODIFY COLUMN jenis_permohonan ENUM('Penerbitan Sertifikat Elektronik') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE permohonan MODIFY COLUMN jenis_permohonan ENUM('TTE','Pengamanan Dokumen','Pengamanan Email','Pengamanan Web','Penerbitan Sertifikat Elektronik') NOT NULL");

        DB::table('permohonan')
            ->where('jenis_permohonan', 'Penerbitan Sertifikat Elektronik')
            ->update(['jenis_permohonan' => 'TTE']);

        DB::statement("ALTER TABLE permohonan MODIFY COLUMN jenis_permohonan ENUM('TTE','Pengamanan Dokumen','Pengamanan Email','Pengamanan Web') NOT NULL");
    }
};
