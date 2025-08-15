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
            // Only drop columns if they actually exist
            $columnsToDrop = [];
            
            if (Schema::hasColumn('daily_fuels', 'fuel_type')) {
                $columnsToDrop[] = 'fuel_type';
            }
            if (Schema::hasColumn('daily_fuels', 'quantity')) {
                $columnsToDrop[] = 'quantity';
            }
            if (Schema::hasColumn('daily_fuels', 'price_per_liter')) {
                $columnsToDrop[] = 'price_per_liter';
            }
            if (Schema::hasColumn('daily_fuels', 'total_amount')) {
                $columnsToDrop[] = 'total_amount';
            }
            
            // Only drop columns if we have any to drop
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            
            // Add new columns for each fuel type (only if they don't exist)
            $this->addNewColumns($table);
            
            // Add user_id for permissions if it doesn't exist
            if (!Schema::hasColumn('daily_fuels', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('set null');
            }
            
            // Handle unique constraint safely
            $this->handleUniqueConstraint($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Drop new columns if they exist
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
            
            // Restore old columns only if they don't exist
            if (!Schema::hasColumn('daily_fuels', 'fuel_type')) {
                $table->string('fuel_type');
            }
            if (!Schema::hasColumn('daily_fuels', 'quantity')) {
                $table->decimal('quantity', 10, 2);
            }
            if (!Schema::hasColumn('daily_fuels', 'price_per_liter')) {
                $table->decimal('price_per_liter', 10, 2);
            }
            if (!Schema::hasColumn('daily_fuels', 'total_amount')) {
                $table->decimal('total_amount', 10, 2);
            }
            
            // Restore old unique constraint
            $table->unique(['date', 'fuel_type']);
        });
    }

    /**
     * Add new columns safely
     */
    private function addNewColumns(Blueprint $table): void
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
     * Handle unique constraint safely for both SQLite and MySQL
     */
    private function handleUniqueConstraint(Blueprint $table): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'sqlite') {
            // For SQLite, handle constraints differently
            $this->handleSqliteConstraints($table);
        } else {
            // For MySQL/PostgreSQL, use standard methods
            $this->handleMysqlConstraints($table);
        }
    }

    /**
     * Handle SQLite constraints
     */
    private function handleSqliteConstraints(Blueprint $table): void
    {
        try {
            // Try to drop the old constraint if it exists
            $table->dropUnique(['date', 'fuel_type']);
        } catch (\Exception $e) {
            // Constraint doesn't exist, that's fine
        }
        
        try {
            // Add new unique constraint on date only
            $table->unique('date');
        } catch (\Exception $e) {
            // Constraint might already exist, that's fine too
        }
    }

    /**
     * Handle MySQL/PostgreSQL constraints
     */
    private function handleMysqlConstraints(Blueprint $table): void
    {
        try {
            // Check if the old unique constraint exists
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'daily_fuels' 
                AND CONSTRAINT_TYPE = 'UNIQUE'
                AND CONSTRAINT_NAME LIKE '%date_fuel_type%'
            ");
            
            if (!empty($constraints)) {
                foreach ($constraints as $constraint) {
                    $table->dropUnique($constraint->CONSTRAINT_NAME);
                }
            }
        } catch (\Exception $e) {
            // If we can't check constraints, try to drop it directly
            try {
                $table->dropUnique(['date', 'fuel_type']);
            } catch (\Exception $e2) {
                // Constraint doesn't exist, that's fine
            }
        }
        
        try {
            // Add new unique constraint on date only
            $table->unique('date');
        } catch (\Exception $e) {
            // Constraint might already exist, that's fine too
        }
    }
};
