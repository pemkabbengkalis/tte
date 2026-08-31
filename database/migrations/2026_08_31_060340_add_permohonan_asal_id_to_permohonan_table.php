<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referensi ke permohonan asal, dipakai untuk permohonan jenis
     * "Perpanjangan Sertifikat Elektronik" agar tercatat sebagai kelanjutan
     * dari sertifikat yang sudah pernah terbit.
     */
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->foreignUuid('permohonan_asal_id')
                ->nullable()
                ->after('jenis_permohonan')
                ->constrained('permohonan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('permohonan_asal_id');
        });
    }
};
