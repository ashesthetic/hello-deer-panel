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
        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->date('invoice_date');
            $table->enum('status', ['Paid', 'Unpaid'])->default('Unpaid');
            $table->enum('type', ['Income', 'Expense'])->default('Expense');
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['Card', 'Cash', 'Bank'])->nullable();
            $table->string('invoice_file_path')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_invoices');
    }
};
