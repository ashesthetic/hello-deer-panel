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
        Schema::create('fuel_delivery', function (Blueprint $table) {
            $table->id();
            $table->date('delivery_date');
            $table->string('invoice_number')->nullable();
            $table->decimal('regular', 10, 2)->nullable();
            $table->decimal('premium', 10, 2)->nullable();
            $table->decimal('diesel', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->boolean('issued')->default(false);
            $table->date('issued_date')->nullable();
            $table->boolean('resolved')->default(false);
            $table->date('resolved_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_delivery');
    }
};
