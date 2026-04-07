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
        Schema::table('daily_sales', function (Blueprint $table) {
            $table->integer('total_transactions')->default(0)->after('afd_debit_transaction_count');
            $table->integer('fuel_transactions')->default(0)->after('total_transactions');
            $table->integer('store_transactions')->default(0)->after('fuel_transactions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_sales', function (Blueprint $table) {
            $table->dropColumn([
                'total_transactions',
                'fuel_transactions',
                'store_transactions',
            ]);
        });
    }
};
