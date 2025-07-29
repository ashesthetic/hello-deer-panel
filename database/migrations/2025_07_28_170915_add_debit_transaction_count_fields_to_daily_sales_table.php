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
            // Add Number of Debit Transaction fields for POS and AFD
            $table->integer('pos_debit_transaction_count')->default(0)->after('pos_interac_debit');
            $table->integer('afd_debit_transaction_count')->default(0)->after('afd_interac_debit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_sales', function (Blueprint $table) {
            $table->dropColumn([
                'pos_debit_transaction_count',
                'afd_debit_transaction_count'
            ]);
        });
    }
};
