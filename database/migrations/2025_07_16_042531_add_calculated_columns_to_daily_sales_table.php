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
            // Add calculated columns
            $table->decimal('product_sale', 10, 2)->default(0)->after('gst');
            $table->decimal('counter_sale', 10, 2)->default(0)->after('delivery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_sales', function (Blueprint $table) {
            $table->dropColumn(['product_sale', 'counter_sale']);
        });
    }
};
