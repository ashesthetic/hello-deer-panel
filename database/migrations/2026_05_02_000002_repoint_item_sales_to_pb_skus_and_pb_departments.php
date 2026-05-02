<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignKeyIfExists('item_sales', 'item_number');
        $this->dropForeignKeyIfExists('item_sales', 'department_number');

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
        $this->dropForeignKeyIfExists('item_sales', 'item_number');
        $this->dropForeignKeyIfExists('item_sales', 'department_number');

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

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $constraintNames = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($constraintNames as $constraintName) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                str_replace('`', '``', $table),
                str_replace('`', '``', $constraintName)
            ));
        }
    }
};
