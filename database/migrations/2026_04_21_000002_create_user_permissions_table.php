<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     * Kategori: OTORISASI
     *
     * Tabel ini adalah matriks hak akses.
     * Setiap baris = satu user dengan satu fitur, beserta 4 boolean aksesnya.
     * Admin bisa mencentang/menghilangkan centang lewat panel admin.
     */
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('User yang mendapat hak akses');

            $table->foreignId('permission_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('Fitur yang diatur aksesnya');

            // 4 jenis akses — admin bisa centang via UI
            $table->boolean('can_view')->default(false)->comment('Boleh melihat data');
            $table->boolean('can_create')->default(false)->comment('Boleh menambah data baru');
            $table->boolean('can_edit')->default(false)->comment('Boleh mengubah data');
            $table->boolean('can_delete')->default(false)->comment('Boleh menghapus data');

            $table->timestamps();

            // Satu user hanya boleh punya satu baris per fitur
            $table->unique(['user_id', 'permission_id'], 'user_permission_unique');
        });
    }

    /**
     * Batalkan migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
