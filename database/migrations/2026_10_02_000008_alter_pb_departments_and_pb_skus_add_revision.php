<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pb_departments', function (Blueprint $table) {
            $table->unsignedBigInteger('revision')->nullable();
        });

        Schema::table('pb_skus', function (Blueprint $table) {
            $table->unsignedBigInteger('revision')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pb_departments', function (Blueprint $table) {
            $table->dropColumn('revision');
        });

        Schema::table('pb_skus', function (Blueprint $table) {
            $table->dropColumn('revision');
        });
    }
};
