<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // NIP tidak lagi wajib saat registrasi
            $table->string('nip', 20)->nullable()->change();

            // Perluas kolom email dari 100 → 150 karakter
            $table->string('email', 150)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 20)->nullable(false)->change();
            $table->string('email', 100)->change();
        });
    }
};
