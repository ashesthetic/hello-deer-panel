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
        Schema::table('daily_sales', function (Blueprint $table) {
            $table->decimal('tobacco_25', 10, 2)->default(0)->after('aeroplan_discount');
            $table->decimal('tobacco_20', 10, 2)->default(0)->after('tobacco_25');
            $table->decimal('lottery', 10, 2)->default(0)->after('tobacco_20');
            $table->decimal('prepay', 10, 2)->default(0)->after('lottery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_sales', function (Blueprint $table) {
            $table->dropColumn(['tobacco_25', 'tobacco_20', 'lottery', 'prepay']);
        });
    }
};
