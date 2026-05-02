<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop both existing FKs
        Schema::table('item_sales', function (Blueprint $table) {
            $table->dropForeign(['item_number']);
            $table->dropForeign(['department_number']);
        });

        // Change department_number from unsignedInteger to string(6)
        DB::statement('ALTER TABLE item_sales MODIFY department_number VARCHAR(6) NULL');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::table('item_sales', function (Blueprint $table) {
            $table->foreign('item_number')
                ->references('item_number')
                ->on('pb_skus')
                ->onDelete('set null');
            $table->foreign('department_number')
                ->references('department_number')
                ->on('pb_departments')
                ->onDelete('set null');
        });
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::table('item_sales', function (Blueprint $table) {
            $table->dropForeign(['item_number']);
            $table->dropForeign(['department_number']);
        });

        DB::statement('ALTER TABLE item_sales MODIFY department_number INT UNSIGNED NULL');

        Schema::table('item_sales', function (Blueprint $table) {
            $table->foreign('item_number')
                ->references('item_number')
                ->on('products')
                ->onDelete('cascade');
            $table->foreign('department_number')
                ->references('department_number')
                ->on('departments')
                ->onDelete('set null');
        });
    }
};
