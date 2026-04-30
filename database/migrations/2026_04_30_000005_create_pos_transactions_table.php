<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('naxml_import_id')->constrained('naxml_imports')->cascadeOnDelete();
            $table->string('store_location_id');
            $table->string('shift_id');
            $table->string('register_id');
            $table->string('cashier_id');
            $table->string('transaction_id');
            $table->string('sequence_number');
            $table->date('business_date')->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->dateTime('receipt_at')->nullable();
            $table->boolean('is_training')->default(false);
            $table->boolean('is_outside_sale')->default(false);
            $table->boolean('is_offline')->default(false);
            $table->boolean('is_suspended')->default(false);
            $table->decimal('total_gross_amount', 10, 2)->default(0);
            $table->decimal('total_net_amount', 10, 2)->default(0);
            $table->decimal('total_tax_exempt_amount', 10, 2)->default(0);
            $table->decimal('total_tax_amount', 10, 2)->default(0);
            $table->decimal('total_grand_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
    }
};
