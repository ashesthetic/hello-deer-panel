<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'products_bak'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $constraintNames = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $tableName)
                ->where('COLUMN_NAME', 'department_number')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->pluck('CONSTRAINT_NAME');

            foreach ($constraintNames as $constraintName) {
                DB::statement(sprintf(
                    'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                    str_replace('`', '``', $tableName),
                    str_replace('`', '``', $constraintName)
                ));
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('department_number')
                ->references('department_number')
                ->on('departments')
                ->onDelete('cascade');
        });
    }
};
