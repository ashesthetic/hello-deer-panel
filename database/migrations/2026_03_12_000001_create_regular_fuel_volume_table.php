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
        Schema::create('regular_fuel_volume', function (Blueprint $table) {
            $table->id();
            $table->dateTime('datetime');

            // Regular fuel fields
            $table->decimal('regular_volume', 10, 2)->nullable();
            $table->decimal('regular_height', 10, 2)->nullable();
            $table->decimal('regular_ullage', 10, 2)->nullable();
            $table->decimal('regular_water', 10, 2)->nullable();
            $table->decimal('regular_temp', 10, 2)->nullable();
            $table->decimal('regular_fill', 10, 2)->nullable();
            $table->string('regular_status')->nullable();

            // Premium fuel fields
            $table->decimal('premium_volume', 10, 2)->nullable();
            $table->decimal('premium_height', 10, 2)->nullable();
            $table->decimal('premium_ullage', 10, 2)->nullable();
            $table->decimal('premium_water', 10, 2)->nullable();
            $table->decimal('premium_temp', 10, 2)->nullable();
            $table->decimal('premium_fill', 10, 2)->nullable();
            $table->string('premium_status')->nullable();

            // Diesel fuel fields
            $table->decimal('diesel_volume', 10, 2)->nullable();
            $table->decimal('diesel_height', 10, 2)->nullable();
            $table->decimal('diesel_ullage', 10, 2)->nullable();
            $table->decimal('diesel_water', 10, 2)->nullable();
            $table->decimal('diesel_temp', 10, 2)->nullable();
            $table->decimal('diesel_fill', 10, 2)->nullable();
            $table->string('diesel_status')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regular_fuel_volume');
    }
};
