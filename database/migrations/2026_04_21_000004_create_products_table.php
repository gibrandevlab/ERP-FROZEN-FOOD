<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     * Kategori: BISNIS — Stok/Inventori
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Relasi ke kategori (opsional, tidak wajib diisi)
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->string('name')->comment('Nama produk, contoh: Nugget Ayam 500gr');
            $table->string('slug')->unique()->comment('Dibuat otomatis dari nama produk');
            $table->string('sku')->unique()->nullable()->comment('Kode produk internal, contoh: PRD-001');
            $table->text('description')->nullable();

            // Harga — gunakan decimal agar aman untuk kalkulasi keuangan
            $table->decimal('price', 15, 2)->default(0)->comment('Harga jual ke pembeli');
            $table->decimal('cost', 15, 2)->default(0)->comment('Harga modal/beli dari supplier');

            $table->string('unit')->default('pcs')->comment('Satuan: pcs, kg, liter, pack, dll');
            $table->string('image')->nullable()->comment('Path gambar produk di storage');
            $table->boolean('is_active')->default(true)->comment('Produk aktif atau diarsipkan');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes()->comment('Hapus lunak — data tidak benar-benar dihapus dari DB');
        });
    }

    /**
     * Batalkan migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
