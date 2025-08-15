<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Check current table structure and add missing columns safely
            $this->addMissingColumns($table);
            
            // Add user_id if it doesn't exist
            if (!Schema::hasColumn('daily_fuels', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('set null');
            }
            
            // Update unique constraint safely
            $this->updateUniqueConstraint($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Remove new columns if they exist
            $columnsToRemove = [
                'regular_quantity', 'regular_price', 'regular_total',
                'plus_quantity', 'plus_price', 'plus_total',
                'sup_plus_quantity', 'sup_plus_price', 'sup_plus_total',
                'diesel_quantity', 'diesel_price', 'diesel_total',
                'user_id'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('daily_fuels', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Restore old structure if needed
            if (!Schema::hasColumn('daily_fuels', 'fuel_type')) {
                $table->string('fuel_type');
                $table->decimal('quantity', 10, 2);
                $table->decimal('price_per_liter', 10, 2);
                $table->decimal('total_amount', 10, 2);
            }
        });
    }

    /**
     * Add missing columns safely
     */
    private function addMissingColumns(Blueprint $table): void
    {
        $columnsToAdd = [
            'regular_quantity' => 'decimal',
            'regular_price' => 'decimal',
            'regular_total' => 'decimal',
            'plus_quantity' => 'decimal',
            'plus_price' => 'decimal',
            'plus_total' => 'decimal',
            'sup_plus_quantity' => 'decimal',
            'sup_plus_price' => 'decimal',
            'sup_plus_total' => 'decimal',
            'diesel_quantity' => 'decimal',
            'diesel_price' => 'decimal',
            'diesel_total' => 'decimal',
        ];

        foreach ($columnsToAdd as $columnName => $columnType) {
            if (!Schema::hasColumn('daily_fuels', $columnName)) {
                if ($columnType === 'decimal') {
                    $table->decimal($columnName, 10, 2)->default(0);
                }
            }
        }
    }

    /**
     * Update unique constraint safely
     */
    private function updateUniqueConstraint(Blueprint $table): void
    {
        try {
            // Check if the old unique constraint exists using MySQL-specific query
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'daily_fuels' 
                AND CONSTRAINT_TYPE = 'UNIQUE'
                AND CONSTRAINT_NAME LIKE '%date_fuel_type%'
            ");
            
            $hasOldConstraint = !empty($constraints);
            
            // Drop old constraint if it exists
            if ($hasOldConstraint) {
                foreach ($constraints as $constraint) {
                    $table->dropUnique($constraint->CONSTRAINT_NAME);
                }
            }
            
            // Add new unique constraint on date only
            $table->unique('date');
            
        } catch (\Exception $e) {
            // If there's an error, just add the new constraint
            // The old one might not exist or might be named differently
            try {
                $table->unique('date');
            } catch (\Exception $e2) {
                // If this also fails, the constraint might already exist
                // We'll continue without it
            }
        }
    }
};
