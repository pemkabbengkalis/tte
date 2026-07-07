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
        Schema::create('permohonan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->string('nomor_permohonan', 30)->nullable()->unique();
            $table->foreignUuid('pemohon_id')->constrained('users')->cascadeOnDelete();
            $table->enum('jenis_permohonan', ['TTE', 'Pengamanan Dokumen', 'Pengamanan Email', 'Pengamanan Web']);
            $table->enum('status', ['draft', 'menunggu_verifikasi', 'diterima', 'ditolak', 'selesai'])->default('draft');
            $table->timestamp('tanggal_pengajuan')->nullable();
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->foreignUuid('verifikator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_verifikator')->nullable();
            $table->unsignedInteger('jumlah_pengajuan')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permohonan');
    }
};
