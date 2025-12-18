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
            // Remove the redundant total columns since total_sale and total should be the same
            $table->dropColumn([
                'regular_total', 'plus_total', 'sup_plus_total', 'diesel_total'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Restore the total columns
            $table->decimal('regular_total', 10, 2)->nullable()->after('regular_total_sale');
            $table->decimal('plus_total', 10, 2)->nullable()->after('plus_total_sale');
            $table->decimal('sup_plus_total', 10, 2)->nullable()->after('sup_plus_total_sale');
            $table->decimal('diesel_total', 10, 2)->nullable()->after('diesel_total_sale');
        });
    }
};
