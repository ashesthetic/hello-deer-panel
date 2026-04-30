<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->string('plu_code', 13);
            $table->string('item_number', 13)->nullable()->index();
            $table->string('department_number', 6)->nullable()->index();
            $table->string('plu_modifier')->nullable();
            $table->unsignedInteger('line_sequence');
            $table->string('description');
            $table->string('merchandise_code')->nullable();
            $table->string('entry_method')->nullable();
            $table->decimal('quantity', 8, 3)->default(0);
            $table->decimal('actual_sale_price', 10, 2)->default(0);
            $table->decimal('regular_sell_price', 10, 2)->default(0);
            $table->decimal('sales_amount', 10, 2)->default(0);
            $table->string('tax_level_id')->nullable();
            $table->decimal('tax_collected_amount', 10, 2)->default(0);
            $table->decimal('taxable_sales_amount', 10, 2)->default(0);
            $table->string('item_type_code')->nullable();
            $table->string('item_type_sub_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_items');
    }
};
