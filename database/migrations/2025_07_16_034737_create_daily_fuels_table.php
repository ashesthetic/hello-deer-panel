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
        Schema::create('daily_fuels', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('fuel_type');
            $table->decimal('quantity', 10, 2);
            $table->decimal('price_per_liter', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Add unique constraint for date and fuel_type combination
            $table->unique(['date', 'fuel_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_fuels');
    }
};
