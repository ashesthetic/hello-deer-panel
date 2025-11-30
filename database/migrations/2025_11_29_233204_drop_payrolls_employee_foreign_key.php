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
        Schema::table('payrolls', function (Blueprint $table) {
            // Check if foreign key exists before dropping it
            $foreignKeys = \DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'payrolls'
                AND TABLE_SCHEMA = DATABASE()
                AND COLUMN_NAME = 'employee_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            if (count($foreignKeys) > 0) {
                // Drop the foreign key constraint
                $table->dropForeign(['employee_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Check if foreign key doesn't exist before adding it
            $foreignKeys = \DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'payrolls'
                AND TABLE_SCHEMA = DATABASE()
                AND COLUMN_NAME = 'employee_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            if (count($foreignKeys) === 0) {
                // Restore the foreign key constraint to employees table
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            }
        });
    }
};
