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
        Schema::create('fuel_volumes', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('shift', ['morning', 'evening']);
            
            // Regular fuel fields
            $table->decimal('regular_tc_volume', 10, 2)->nullable();
            $table->decimal('regular_product_height', 10, 2)->nullable();
            
            // Premium fuel fields
            $table->decimal('premium_tc_volume', 10, 2)->nullable();
            $table->decimal('premium_product_height', 10, 2)->nullable();
            
            // Diesel fuel fields
            $table->decimal('diesel_tc_volume', 10, 2)->nullable();
            $table->decimal('diesel_product_height', 10, 2)->nullable();
            
            // Added fuel fields
            $table->decimal('added_regular', 10, 2)->nullable();
            $table->decimal('added_premium', 10, 2)->nullable();
            $table->decimal('added_diesel', 10, 2)->nullable();
            
            // User who created the entry
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
            
            // Ensure only one entry per date and shift combination
            $table->unique(['date', 'shift']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_volumes');
    }
};
