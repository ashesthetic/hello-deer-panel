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
        Schema::create('owner_equities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->enum('transaction_type', ['contribution', 'withdrawal', 'distribution', 'adjustment']);
            $table->decimal('amount', 15, 2); // Amount in dollars
            $table->date('transaction_date');
            $table->string('reference_number')->nullable(); // Check number, transfer ID, etc.
            $table->string('payment_method')->nullable(); // Cash, Check, E-transfer, Direct Deposit, etc.
            $table->text('description')->nullable();
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
        Schema::dropIfExists('owner_equities');
    }
};
