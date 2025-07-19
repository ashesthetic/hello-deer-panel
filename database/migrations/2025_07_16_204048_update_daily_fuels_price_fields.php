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
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Drop the old price columns
            $table->dropColumn([
                'regular_price', 'plus_price', 'sup_plus_price', 'diesel_price'
            ]);
            
            // Add new total sale columns with 3 decimal places
            $table->decimal('regular_total_sale', 10, 3)->nullable()->after('regular_quantity');
            $table->decimal('plus_total_sale', 10, 3)->nullable()->after('plus_quantity');
            $table->decimal('sup_plus_total_sale', 10, 3)->nullable()->after('sup_plus_quantity');
            $table->decimal('diesel_total_sale', 10, 3)->nullable()->after('diesel_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Drop new total sale columns
            $table->dropColumn([
                'regular_total_sale', 'plus_total_sale', 'sup_plus_total_sale', 'diesel_total_sale'
            ]);
            
            // Restore old price columns
            $table->decimal('regular_price', 10, 2)->default(0)->after('regular_quantity');
            $table->decimal('plus_price', 10, 2)->default(0)->after('plus_quantity');
            $table->decimal('sup_plus_price', 10, 2)->default(0)->after('sup_plus_quantity');
            $table->decimal('diesel_price', 10, 2)->default(0)->after('diesel_quantity');
        });
    }
};
