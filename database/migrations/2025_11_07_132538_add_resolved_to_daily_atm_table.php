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
        Schema::table('daily_atm', function (Blueprint $table) {
            $table->boolean('resolved')->default(0)->after('withdraw');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_atm', function (Blueprint $table) {
            $table->dropColumn('resolved');
        });
    }
};
