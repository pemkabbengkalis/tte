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
        Schema::create('template_dokumen', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->string('nama_template', 150);
            $table->text('deskripsi')->nullable();
            $table->string('nama_file', 255);
            $table->string('path_file', 500);
            $table->string('versi', 10)->default(1.0);
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_dokumen');
    }
};
