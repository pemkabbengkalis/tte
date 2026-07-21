<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan jenis dokumen "hasil_tte": berkas hasil tanda tangan elektronik
     * yang diunggah verifikator setelah permohonan berstatus diterima.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE dokumen_permohonan MODIFY COLUMN jenis_dokumen ENUM('surat_permohonan', 'sk_jabatan', 'sk_pangkat', 'ktp', 'hasil_tte') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('dokumen_permohonan')->where('jenis_dokumen', 'hasil_tte')->delete();

        DB::statement("ALTER TABLE dokumen_permohonan MODIFY COLUMN jenis_dokumen ENUM('surat_permohonan', 'sk_jabatan', 'sk_pangkat', 'ktp') NOT NULL");
    }
};
