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
            // General Section - New fields
            $table->integer('number_of_safedrops')->default(0)->after('reported_total');
            $table->decimal('safedrops_amount', 10, 2)->default(0)->after('number_of_safedrops');
            $table->decimal('cash_on_hand', 10, 2)->default(0)->after('safedrops_amount');
            
            // Daily Total Section - New fields
            $table->decimal('store_discount', 10, 2)->default(0)->after('gst');
            $table->decimal('penny_rounding', 10, 2)->default(0)->after('store_discount');
            $table->decimal('daily_total', 10, 2)->default(0)->after('penny_rounding');
            
            // Breakdown Section - New calculated total field
            $table->decimal('breakdown_total', 10, 2)->default(0)->after('delivery');
            
            // Card Transactions - POS Transaction fields
            $table->decimal('pos_visa', 10, 2)->default(0)->after('breakdown_total');
            $table->decimal('pos_mastercard', 10, 2)->default(0)->after('pos_visa');
            $table->decimal('pos_amex', 10, 2)->default(0)->after('pos_mastercard');
            $table->decimal('pos_commercial', 10, 2)->default(0)->after('pos_amex');
            $table->decimal('pos_up_credit', 10, 2)->default(0)->after('pos_commercial');
            $table->decimal('pos_discover', 10, 2)->default(0)->after('pos_up_credit');
            $table->decimal('pos_interac_debit', 10, 2)->default(0)->after('pos_discover');
            
            // Card Transactions - AFD Transaction fields
            $table->decimal('afd_visa', 10, 2)->default(0)->after('pos_interac_debit');
            $table->decimal('afd_mastercard', 10, 2)->default(0)->after('afd_visa');
            $table->decimal('afd_amex', 10, 2)->default(0)->after('afd_mastercard');
            $table->decimal('afd_commercial', 10, 2)->default(0)->after('afd_amex');
            $table->decimal('afd_up_credit', 10, 2)->default(0)->after('afd_commercial');
            $table->decimal('afd_discover', 10, 2)->default(0)->after('afd_up_credit');
            $table->decimal('afd_interac_debit', 10, 2)->default(0)->after('afd_discover');
            
            // Loyalty Section
            $table->decimal('journey_discount', 10, 2)->default(0)->after('afd_interac_debit');
            $table->decimal('aeroplan_discount', 10, 2)->default(0)->after('journey_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_sales', function (Blueprint $table) {
            // Drop all new fields
            $table->dropColumn([
                'number_of_safedrops',
                'safedrops_amount',
                'cash_on_hand',
                'store_discount',
                'penny_rounding',
                'daily_total',
                'breakdown_total',
                'pos_visa',
                'pos_mastercard',
                'pos_amex',
                'pos_commercial',
                'pos_up_credit',
                'pos_discover',
                'pos_interac_debit',
                'afd_visa',
                'afd_mastercard',
                'afd_amex',
                'afd_commercial',
                'afd_up_credit',
                'afd_discover',
                'afd_interac_debit',
                'journey_discount',
                'aeroplan_discount'
            ]);
        });
    }
};
