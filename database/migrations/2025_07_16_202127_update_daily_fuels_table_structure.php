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
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Check if columns exist before dropping them
            if (Schema::hasColumn('daily_fuels', 'fuel_type')) {
                $table->dropColumn(['fuel_type', 'quantity', 'price_per_liter', 'total_amount']);
            }
            
            // Add new columns for each fuel type
            $table->decimal('regular_quantity', 10, 2)->default(0)->after('date');
            $table->decimal('regular_price', 10, 2)->default(0)->after('regular_quantity');
            $table->decimal('regular_total', 10, 2)->default(0)->after('regular_price');
            
            $table->decimal('plus_quantity', 10, 2)->default(0)->after('regular_total');
            $table->decimal('plus_price', 10, 2)->default(0)->after('plus_quantity');
            $table->decimal('plus_total', 10, 2)->default(0)->after('plus_price');
            
            $table->decimal('sup_plus_quantity', 10, 2)->default(0)->after('plus_total');
            $table->decimal('sup_plus_price', 10, 2)->default(0)->after('sup_plus_quantity');
            $table->decimal('sup_plus_total', 10, 2)->default(0)->after('sup_plus_price');
            
            $table->decimal('diesel_quantity', 10, 2)->default(0)->after('sup_plus_total');
            $table->decimal('diesel_price', 10, 2)->default(0)->after('diesel_quantity');
            $table->decimal('diesel_total', 10, 2)->default(0)->after('diesel_price');
            
            // Add user_id for permissions
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('set null');
            
            // Update unique constraint to just date since we now have all fuel types in one row
            // Check if the unique constraint exists before dropping it
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('daily_fuels');
            if (array_key_exists('daily_fuels_date_fuel_type_unique', $indexes)) {
                $table->dropUnique(['date', 'fuel_type']);
            }
            $table->unique('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_fuels', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'regular_quantity', 'regular_price', 'regular_total',
                'plus_quantity', 'plus_price', 'plus_total',
                'sup_plus_quantity', 'sup_plus_price', 'sup_plus_total',
                'diesel_quantity', 'diesel_price', 'diesel_total',
                'user_id'
            ]);
            
            // Restore old columns
            $table->string('fuel_type');
            $table->decimal('quantity', 10, 2);
            $table->decimal('price_per_liter', 10, 2);
            $table->decimal('total_amount', 10, 2);
            
            // Restore old unique constraint
            $table->unique(['date', 'fuel_type']);
        });
    }
};
