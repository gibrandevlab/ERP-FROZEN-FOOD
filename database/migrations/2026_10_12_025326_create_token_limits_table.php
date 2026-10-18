<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Kuota harian (0 = tidak ada batas)
            $table->unsignedInteger('daily_token_limit')->default(0)
                ->comment('Maks token/hari. 0 = unlimited.');

            // Kuota total / bulanan (0 = tidak ada batas)
            $table->unsignedInteger('monthly_token_limit')->default(0)
                ->comment('Maks token/bulan. 0 = unlimited.');

            // Apakah fitur AI chat diizinkan sama sekali?
            $table->boolean('is_enabled')->default(true)
                ->comment('false = akses AI diblokir total untuk user ini.');

            // Catatan admin
            $table->string('notes')->nullable();

            $table->timestamps();

            $table->unique('user_id'); // satu baris per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_limits');
    }
};
