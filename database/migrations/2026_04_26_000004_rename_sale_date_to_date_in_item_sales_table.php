<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_sales', function (Blueprint $table) {
            $table->renameColumn('sale_date', 'date');
        });
    }

    public function down(): void
    {
        Schema::table('item_sales', function (Blueprint $table) {
            $table->renameColumn('date', 'sale_date');
        });
    }
};
