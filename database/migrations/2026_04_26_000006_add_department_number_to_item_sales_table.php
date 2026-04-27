<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_sales', function (Blueprint $table) {
            $table->unsignedInteger('department_number')->nullable()->after('item_number');
            $table->foreign('department_number')
                ->references('department_number')
                ->on('departments')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('item_sales', function (Blueprint $table) {
            $table->dropForeign(['department_number']);
            $table->dropColumn('department_number');
        });
    }
};
