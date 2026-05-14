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
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('payment_status', ['paid', 'unpaid'])
                  ->default('paid')
                  ->after('amount')
                  ->comment('paid = Lunas, unpaid = Belum Lunas (Utang/Piutang)');
            
            $table->date('due_date')
                  ->nullable()
                  ->after('payment_status')
                  ->comment('Tanggal jatuh tempo (jika berutang)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'due_date']);
        });
    }
};
