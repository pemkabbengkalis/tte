<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus semua record dokumen dengan jenis_dokumen = email_dinas
        DB::table('dokumen_permohonan')->where('jenis_dokumen', 'email_dinas')->delete();

        // Update kolom enum untuk MySQL (SQLite tidak memerlukan ini)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE dokumen_permohonan MODIFY COLUMN jenis_dokumen ENUM('surat_permohonan', 'sk_jabatan', 'sk_pangkat', 'ktp') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE dokumen_permohonan MODIFY COLUMN jenis_dokumen ENUM('surat_permohonan', 'sk_jabatan', 'sk_pangkat', 'ktp', 'email_dinas') NOT NULL");
        }
    }
};
