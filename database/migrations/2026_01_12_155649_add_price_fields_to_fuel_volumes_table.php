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
        Schema::table('fuel_volumes', function (Blueprint $table) {
            $table->decimal('regular_price', 8, 3)->nullable()->after('added_diesel'); // Regular fuel price
            $table->decimal('premium_price', 8, 3)->nullable()->after('regular_price'); // Premium fuel price
            $table->decimal('diesel_price', 8, 3)->nullable()->after('premium_price'); // Diesel fuel price
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_volumes', function (Blueprint $table) {
            $table->dropColumn(['regular_price', 'premium_price', 'diesel_price']);
        });
    }
};
