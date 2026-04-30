<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_transaction_items', function (Blueprint $table) {
            $table->decimal('actual_sale_price', 10, 3)->default(0)->change();
            $table->decimal('regular_sell_price', 10, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('pos_transaction_items', function (Blueprint $table) {
            $table->decimal('actual_sale_price', 10, 2)->default(0)->change();
            $table->decimal('regular_sell_price', 10, 2)->default(0)->change();
        });
    }
};
