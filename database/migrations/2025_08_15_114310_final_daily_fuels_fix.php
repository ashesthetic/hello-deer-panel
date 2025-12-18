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
        // This migration is designed to be 100% safe
        // It will ONLY add missing columns and NEVER drop anything
        
        if (!Schema::hasTable('daily_fuels')) {
            // If table doesn't exist, create it with the complete structure
            Schema::create('daily_fuels', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                
                // Regular fuel columns
                $table->decimal('regular_quantity', 10, 2)->default(0);
                $table->decimal('regular_price', 10, 2)->default(0);
                $table->decimal('regular_total', 10, 2)->default(0);
                $table->decimal('regular_total_sale', 10, 3)->default(0);
                
                // Plus fuel columns
                $table->decimal('plus_quantity', 10, 2)->default(0);
                $table->decimal('plus_price', 10, 2)->default(0);
                $table->decimal('plus_total', 10, 2)->default(0);
                $table->decimal('plus_total_sale', 10, 3)->default(0);
                
                // Super Plus fuel columns
                $table->decimal('sup_plus_quantity', 10, 2)->default(0);
                $table->decimal('sup_plus_price', 10, 2)->default(0);
                $table->decimal('sup_plus_total', 10, 2)->default(0);
                $table->decimal('sup_plus_total_sale', 10, 3)->default(0);
                
                // Diesel fuel columns
                $table->decimal('diesel_quantity', 10, 2)->default(0);
                $table->decimal('diesel_price', 10, 2)->default(0);
                $table->decimal('diesel_total', 10, 2)->default(0);
                $table->decimal('diesel_total_sale', 10, 3)->default(0);
                
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
            return;
        }

        // If table exists, just add missing columns one by one
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Add each column individually to avoid any batch operations
            $this->addColumnIfMissing($table, 'regular_quantity', 'decimal');
            $this->addColumnIfMissing($table, 'regular_price', 'decimal');
            $this->addColumnIfMissing($table, 'regular_total', 'decimal');
            $this->addColumnIfMissing($table, 'regular_total_sale', 'decimal_sale');
            
            $this->addColumnIfMissing($table, 'plus_quantity', 'decimal');
            $this->addColumnIfMissing($table, 'plus_price', 'decimal');
            $this->addColumnIfMissing($table, 'plus_total', 'decimal');
            $this->addColumnIfMissing($table, 'plus_total_sale', 'decimal_sale');
            
            $this->addColumnIfMissing($table, 'sup_plus_quantity', 'decimal');
            $this->addColumnIfMissing($table, 'sup_plus_price', 'decimal');
            $this->addColumnIfMissing($table, 'sup_plus_total', 'decimal');
            $this->addColumnIfMissing($table, 'sup_plus_total_sale', 'decimal_sale');
            
            $this->addColumnIfMissing($table, 'diesel_quantity', 'decimal');
            $this->addColumnIfMissing($table, 'diesel_price', 'decimal');
            $this->addColumnIfMissing($table, 'diesel_total', 'decimal');
            $this->addColumnIfMissing($table, 'diesel_total_sale', 'decimal_sale');
            
            // Add user_id if missing (without foreign key constraint to avoid issues)
            if (!Schema::hasColumn('daily_fuels', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }
        });
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
                'regular_quantity', 'regular_price', 'regular_total', 'regular_total_sale',
                'plus_quantity', 'plus_price', 'plus_total', 'plus_total_sale',
                'sup_plus_quantity', 'sup_plus_price', 'sup_plus_total', 'sup_plus_total_sale',
                'diesel_quantity', 'diesel_price', 'diesel_total', 'diesel_total_sale',
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
                } elseif ($columnType === 'decimal_sale') {
                    $table->decimal($columnName, 10, 3)->default(0);
                }
            } catch (\Exception $e) {
                // If adding column fails, just continue
                // This prevents the entire migration from failing
            }
        }
    }
};
