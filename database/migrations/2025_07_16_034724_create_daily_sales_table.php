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
        Schema::create('daily_sales', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            
            // Product Sale fields
            $table->decimal('fuel_sale', 10, 2)->default(0);
            $table->decimal('store_sale', 10, 2)->default(0);
            $table->decimal('gst', 10, 2)->default(0);
            
            // Counter Sale fields
            $table->decimal('card', 10, 2)->default(0);
            $table->decimal('cash', 10, 2)->default(0);
            $table->decimal('coupon', 10, 2)->default(0);
            $table->decimal('delivery', 10, 2)->default(0);
            
            // Notes field
            $table->longText('notes')->nullable();
            
            $table->timestamps();
            
            // Add unique constraint for date
            $table->unique('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sales');
    }
};
