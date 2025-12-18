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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->text('notes')->nullable();
            
            // For income/expense transactions
            $table->foreignId('bank_account_id')->nullable()->constrained()->onDelete('set null');
            
            // For transfer transactions
            $table->foreignId('from_bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            $table->foreignId('to_bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            
            // Reference to vendor invoice if auto-created
            $table->foreignId('vendor_invoice_id')->nullable()->constrained()->onDelete('set null');
            
            // Transaction metadata
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            
            // User tracking
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['type', 'transaction_date']);
            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['vendor_invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
