<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_pos', function (Blueprint $table) {
            $table->decimal('resolved_amount', 10, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('daily_pos', function (Blueprint $table) {
            $table->dropColumn('resolved_amount');
        });
    }
};
