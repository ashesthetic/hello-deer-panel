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
        Schema::table('provider_bills', function (Blueprint $table) {
            // Rename amount to subtotal
            $table->renameColumn('amount', 'subtotal');
            
            // Add new columns
            $table->decimal('gst', 10, 2)->default(0.00)->after('subtotal');
            $table->decimal('credit', 10, 2)->default(0.00)->after('gst');
            $table->decimal('total', 10, 2)->default(0.00)->after('credit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_bills', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn(['gst', 'credit', 'total']);
            
            // Rename subtotal back to amount
            $table->renameColumn('subtotal', 'amount');
        });
    }
};
