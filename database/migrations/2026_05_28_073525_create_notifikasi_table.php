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
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('permohonan_id')->constrained('permohonan')->cascadeOnDelete();
            $table->string('judul', 150);
            $table->text('pesan');
            $table->enum('tipe', ['permohonan_baru', 'pengajuan_ulang', 'diterima', 'ditolak']);
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
