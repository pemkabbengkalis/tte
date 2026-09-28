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
            $table->text('dek')->nullable()->after('mime_type')->comment('Data Encryption Key terenkripsi oleh KEK');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dokumen_permohonan', function (Blueprint $table) {
            $table->dropColumn('dek');
        });
    }
};
