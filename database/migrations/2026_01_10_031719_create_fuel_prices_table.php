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
        Schema::create('fuel_prices', function (Blueprint $table) {
            $table->id();
            $table->decimal('regular_87', 8, 3)->default(0); // Regular 87 price
            $table->decimal('midgrade_91', 8, 3)->default(0); // Midgrade 91 price
            $table->decimal('premium_94', 8, 3)->default(0); // Premium 94 price
            $table->decimal('diesel', 8, 3)->default(0); // Diesel price
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_prices');
    }
};
