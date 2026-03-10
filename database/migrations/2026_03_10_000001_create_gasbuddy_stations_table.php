<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gasbuddy_stations', function (Blueprint $table) {
            $table->id();
            $table->string('gasbuddy_station_id')->unique();
            $table->string('name');
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('distance')->nullable(); // e.g. "0.01mi"
            $table->decimal('regular_gas', 8, 3)->nullable();
            $table->decimal('midgrade_gas', 8, 3)->nullable();
            $table->decimal('premium_gas', 8, 3)->nullable();
            $table->decimal('diesel', 8, 3)->nullable();
            $table->timestamp('regular_gas_posted_at')->nullable();
            $table->timestamp('midgrade_gas_posted_at')->nullable();
            $table->timestamp('premium_gas_posted_at')->nullable();
            $table->timestamp('diesel_posted_at')->nullable();
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gasbuddy_stations');
    }
};
