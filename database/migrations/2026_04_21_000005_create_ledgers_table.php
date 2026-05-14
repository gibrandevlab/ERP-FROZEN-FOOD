<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     * Kategori: BISNIS — Pembukuan (Pemasukan & Pengeluaran)
     */
    public function up(): void
    {
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();

            // Jenis transaksi
            $table->enum('type', ['income', 'expense'])
                  ->comment('income = pemasukan, expense = pengeluaran');

            $table->string('title')->comment('Judul transaksi, contoh: Penjualan Nugget Minggu Ini');
            $table->string('slug')->unique()->comment('Dibuat otomatis dari judul + timestamp agar selalu unik');
            $table->decimal('amount', 15, 2)->comment('Nominal transaksi dalam Rupiah');
            $table->text('description')->nullable()->comment('Catatan tambahan');
            $table->date('date')->comment('Tanggal transaksi terjadi');

            // Referensi dokumen
            $table->string('reference')->nullable()
                  ->comment('Nomor invoice, kuitansi, atau referensi transaksi lainnya');

            // Relasi opsional ke produk yang terlibat
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete()
                  ->comment('Produk yang terlibat dalam transaksi ini (jika ada)');

            $table->string('proof_image')->nullable()
                  ->comment('Path foto bukti transaksi/kwitansi di storage');

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes()->comment('Hapus lunak — catatan keuangan tidak boleh benar-benar dihapus');
        });
    }

    /**
     * Batalkan migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
