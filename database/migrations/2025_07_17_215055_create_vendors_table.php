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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('possible_products');
            $table->enum('payment_method', ['PAD', 'Credit Card', 'E-transfer', 'Direct Deposit'])->default('PAD');
            $table->string('etransfer_email')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('transit_number')->nullable();
            $table->string('institute_number')->nullable();
            $table->string('account_number')->nullable();
            $table->string('void_check_path')->nullable();
            $table->json('order_before_days');
            $table->json('possible_delivery_days');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
