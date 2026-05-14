<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     * Kategori: AUTENTIKASI
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            // Sistem pemulihan akun dengan kata rahasia (bukan email reset)
            $table->string('recovery_phrase')->nullable()
                  ->comment('Kata rahasia terenkripsi untuk reset password. Tidak bisa dipulihkan jika lupa.');

            // Penanda apakah user adalah pemilik/admin sistem
            $table->boolean('is_admin')->default(false)
                  ->comment('Admin memiliki akses penuh ke semua fitur tanpa perlu diatur satu per satu.');

            $table->rememberToken();
            $table->timestamps();
        });

        // Tabel token untuk keperluan sistem Laravel (bisa diabaikan jika tidak pakai email reset)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Batalkan migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
