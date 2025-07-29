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
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->renameColumn('amount', 'subtotal');
            $table->decimal('gst', 10, 2)->default(0.00)->after('subtotal');
            $table->decimal('total', 10, 2)->default(0.00)->after('gst');
            $table->text('notes')->nullable()->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->dropColumn(['gst', 'total', 'notes']);
            $table->renameColumn('subtotal', 'amount');
        });
    }
};
