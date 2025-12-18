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
        Schema::create('provider_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->date('billing_date');
            $table->date('service_date_from');
            $table->date('service_date_to');
            $table->date('due_date');
            $table->decimal('amount', 10, 2);
            $table->string('invoice_file_path')->nullable();
            $table->enum('status', ['Pending', 'Paid'])->default('Pending');
            $table->date('date_paid')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('providers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_bills');
    }
};
