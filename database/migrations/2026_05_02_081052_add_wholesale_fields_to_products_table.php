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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('wholesale_price', 15, 2)->nullable()->after('price')
                  ->comment('Harga grosir untuk pembelian dalam jumlah banyak');
            $table->integer('wholesale_min_qty')->nullable()->after('wholesale_price')
                  ->comment('Minimal kuantitas untuk mendapatkan harga grosir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['wholesale_price', 'wholesale_min_qty']);
        });
    }
};
