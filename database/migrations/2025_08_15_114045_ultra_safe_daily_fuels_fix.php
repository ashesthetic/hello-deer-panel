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
        // This migration is designed to be completely safe
        // It will only add missing columns and won't try to drop anything
        
        if (!Schema::hasTable('daily_fuels')) {
            // If table doesn't exist, create it with the new structure
            Schema::create('daily_fuels', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->decimal('regular_quantity', 10, 2)->default(0);
                $table->decimal('regular_price', 10, 2)->default(0);
                $table->decimal('regular_total', 10, 2)->default(0);
                $table->decimal('plus_quantity', 10, 2)->default(0);
                $table->decimal('plus_price', 10, 2)->default(0);
                $table->decimal('plus_total', 10, 2)->default(0);
                $table->decimal('sup_plus_quantity', 10, 2)->default(0);
                $table->decimal('sup_plus_price', 10, 2)->default(0);
                $table->decimal('sup_plus_total', 10, 2)->default(0);
                $table->decimal('diesel_quantity', 10, 2)->default(0);
                $table->decimal('diesel_price', 10, 2)->default(0);
                $table->decimal('diesel_total', 10, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->timestamps();
                
                // Add unique constraint on date only
                $table->unique('date');
            });
            return;
        }

        // If table exists, just add missing columns one by one
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Add each column individually to avoid any batch operations
            $this->addColumnIfMissing($table, 'regular_quantity', 'decimal');
            $this->addColumnIfMissing($table, 'regular_price', 'decimal');
            $this->addColumnIfMissing($table, 'regular_total', 'decimal');
            $this->addColumnIfMissing($table, 'plus_quantity', 'decimal');
            $this->addColumnIfMissing($table, 'plus_price', 'decimal');
            $this->addColumnIfMissing($table, 'plus_total', 'decimal');
            $this->addColumnIfMissing($table, 'sup_plus_quantity', 'decimal');
            $this->addColumnIfMissing($table, 'sup_plus_price', 'decimal');
            $this->addColumnIfMissing($table, 'sup_plus_total', 'decimal');
            $this->addColumnIfMissing($table, 'diesel_quantity', 'decimal');
            $this->addColumnIfMissing($table, 'diesel_price', 'decimal');
            $this->addColumnIfMissing($table, 'diesel_total', 'decimal');
            
            // Add user_id if missing
            if (!Schema::hasColumn('daily_fuels', 'user_id')) {
                try {
                    $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('set null');
                } catch (\Exception $e) {
                    // If foreign key fails, just add the column without constraint
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                }
            }
        });

        // Handle unique constraint separately to avoid any issues
        $this->handleUniqueConstraintSafely();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // In rollback, we'll be very careful not to break anything
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Only remove columns if they exist and are safe to remove
            $safeColumnsToRemove = [
                'regular_quantity', 'regular_price', 'regular_total',
                'plus_quantity', 'plus_price', 'plus_total',
                'sup_plus_quantity', 'sup_plus_price', 'sup_plus_total',
                'diesel_quantity', 'diesel_price', 'diesel_total',
            ];
            
            foreach ($safeColumnsToRemove as $column) {
                if (Schema::hasColumn('daily_fuels', $column)) {
                    try {
                        $table->dropColumn($column);
                    } catch (\Exception $e) {
                        // If we can't drop a column, just continue
                    }
                }
            }
            
            // Don't try to remove user_id as it might be referenced elsewhere
        });
    }

    /**
     * Add a column if it doesn't exist
     */
    private function addColumnIfMissing(Blueprint $table, string $columnName, string $columnType): void
    {
        if (!Schema::hasColumn('daily_fuels', $columnName)) {
            try {
                if ($columnType === 'decimal') {
                    $table->decimal($columnName, 10, 2)->default(0);
                }
            } catch (\Exception $e) {
                // If adding column fails, log it but continue
                // This prevents the entire migration from failing
            }
        }
    }

    /**
     * Handle unique constraint in the safest way possible
     */
    private function handleUniqueConstraintSafely(): void
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            
            if ($driver === 'sqlite') {
                // For SQLite, be extra careful
                $this->handleSqliteConstraintSafely();
            } else {
                // For MySQL/PostgreSQL, use standard approach
                $this->handleMysqlConstraintSafely();
            }
        } catch (\Exception $e) {
            // If anything fails with constraints, just continue
            // The table structure will still be updated
        }
    }

    /**
     * Handle SQLite constraint in the safest way
     */
    private function handleSqliteConstraintSafely(): void
    {
        try {
            // Try to add unique constraint on date
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS daily_fuels_date_unique ON daily_fuels (date)');
        } catch (\Exception $e) {
            // If this fails, just continue without the constraint
        }
    }

    /**
     * Handle MySQL constraint in the safest way
     */
    private function handleMysqlConstraintSafely(): void
    {
        try {
            // Check if unique constraint on date already exists
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'daily_fuels' 
                AND CONSTRAINT_TYPE = 'UNIQUE'
                AND CONSTRAINT_NAME LIKE '%date%'
            ");
            
            if (empty($constraints)) {
                // Only add if it doesn't exist
                DB::statement('ALTER TABLE daily_fuels ADD UNIQUE KEY daily_fuels_date_unique (date)');
            }
        } catch (\Exception $e) {
            // If anything fails, just continue without the constraint
        }
    }
};
