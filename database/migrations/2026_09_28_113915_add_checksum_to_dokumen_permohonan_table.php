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
        Schema::table('dokumen_permohonan', function (Blueprint $table) {
            $table->string('checksum', 64)->nullable()->after('dek')->comment('SHA-256 hash dari file terenkripsi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dokumen_permohonan', function (Blueprint $table) {
            $table->dropColumn('checksum');
        });
    }
};
