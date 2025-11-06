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
        // Drop the existing table
        Schema::dropIfExists('owner_equities');

        // Create the new table with the updated structure
        Schema::create('owner_equities', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('description', 255);
            $table->enum('type', ['investment', 'withdrawal']);
            $table->decimal('amount', 15, 2);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new table
        Schema::dropIfExists('owner_equities');

        // Recreate the old table structure
        Schema::create('owner_equities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->enum('transaction_type', ['contribution', 'withdrawal', 'distribution', 'adjustment']);
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }
};
