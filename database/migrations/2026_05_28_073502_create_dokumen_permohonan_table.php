<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dokumen_permohonan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->foreignUuid('permohonan_id')->constrained('permohonan')->cascadeOnDelete();
            $table->enum('jenis_dokumen', ['surat_permohonan', 'sk_jabatan', 'sk_pangkat', 'ktp', 'email_dinas']);
            $table->string('nama_file', 255);
            $table->string('path_file', 500);
            $table->unsignedInteger('ukuran_file');
            $table->string('mime_type', 50);
            $table->unsignedInteger('versi')->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->softDeletes();

            $table->index('jenis_dokumen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_permohonan');
    }
};
