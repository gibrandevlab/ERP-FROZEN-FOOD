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
     * Tabel ini menyimpan daftar master semua fitur/modul aplikasi.
     * Contoh baris: { key: 'products', label: 'Manajemen Stok', category: 'bisnis' }
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            // Kode unik fitur, digunakan sebagai nama Gate di Laravel
            // Contoh: 'products', 'ledger', 'categories'
            $table->string('key')->unique()
                  ->comment('Kode unik fitur. Dipakai sebagai nama Gate: view-{key}, create-{key}, dst.');

            // Nama tampilan untuk UI panel admin
            $table->string('label')
                  ->comment('Nama tampilan di panel admin, contoh: Manajemen Stok');

            // Grup modul untuk pengelompokan di UI
            $table->string('category')->default('umum')
                  ->comment('Grup modul: bisnis, keuangan, sistem, dll.');

            $table->text('description')->nullable()
                  ->comment('Penjelasan singkat tentang fitur ini');

            $table->timestamps();
        });
    }

    /**
     * Batalkan migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
