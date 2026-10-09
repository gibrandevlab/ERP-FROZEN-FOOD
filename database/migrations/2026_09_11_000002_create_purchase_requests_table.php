<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     * Membuat tabel purchase_requests untuk mengelola alur pengajuan pembelian barang (PR).
     */
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();

            // Nomor Tiket Unik, contoh: PR-20260911-0001
            $table->string('ticket_number')->unique()
                  ->comment('Nomor tiket transaksi PR unik');

            // Relasi Barang & Lokasi Gudang yang Minta Stok
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete()
                  ->comment('Supplier tujuan pembelian (diisi saat dipesan staf pembeli)');

            // Jumlah Barang
            $table->integer('requested_qty')->comment('Jumlah barang yang diajukan gudang');
            $table->integer('received_qty')->nullable()->comment('Jumlah barang fisik yang diterima gudang');

            // Status Alur PR (5-stage)
            $table->enum('status', ['pending', 'approved', 'rejected', 'purchased', 'completed'])
                  ->default('pending')
                  ->comment('pending = Baru diajukan, approved = Disetujui Pimpinan, rejected = Ditolak, purchased = Sudah dibeli & diinput nota, completed = Barang diterima & stok masuk');

            // Tahap 2: Audit Pembuat Tiket (Staff Gudang)
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete()
                  ->comment('User staff gudang pembuat PR');
            $table->text('request_notes')->nullable()->comment('Catatan alasan kebutuhan barang');

            // Tahap 3A: Audit Review Pimpinan
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()
                  ->comment('User pimpinan/admin yang approve/reject');
            $table->text('rejection_reason')->nullable()->comment('Alasan penolakan jika status rejected');
            $table->timestamp('reviewed_at')->nullable()->comment('Waktu persetujuan/penolakan');

            // Tahap 3B: Audit Pembelian Staf Pembeli (Purchasing)
            $table->foreignId('purchased_by')->nullable()->constrained('users')->nullOnDelete()
                  ->comment('User staf pembeli / purchasing');
            $table->string('invoice_number')->nullable()->comment('ID Nota / Nomor Faktur Pembelian dari Supplier');
            $table->string('proof_image')->nullable()->comment('Path foto nota/kwitansi di storage');
            $table->decimal('total_cost', 15, 2)->nullable()->comment('Total nominal pembelian dari nota');
            $table->timestamp('purchased_at')->nullable()->comment('Waktu pembelian dilakukan');

            // Tahap 4: Audit Penerimaan Stok (Staff Gudang)
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete()
                  ->comment('User staff gudang penerima barang fisik');
            $table->timestamp('received_at')->nullable()->comment('Waktu barang diterima fisik');

            // Relasi ke Catatan Pengeluaran Pembukuan (Ledger)
            $table->foreignId('ledger_id')->nullable()->constrained('ledgers')->nullOnDelete()
                  ->comment('Relasi ke catatan ledgers pengeluaran kas');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Batalkan migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
