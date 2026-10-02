<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pb_sku_upcs', function (Blueprint $table) {
            // A UPC can transiently exist on two SKUs mid-page during a sync run
            // (see pricebook sync spec §6.3), so it cannot be unique.
            $table->dropUnique(['upc']);
            $table->unsignedBigInteger('source_id')->nullable()->after('id')->index();
            $table->unsignedBigInteger('revision')->nullable();
        });

        Schema::table('pb_sku_upcs', function (Blueprint $table) {
            $table->index('upc');
        });
    }

    public function down(): void
    {
        Schema::table('pb_sku_upcs', function (Blueprint $table) {
            $table->dropIndex(['upc']);
            $table->dropColumn(['source_id', 'revision']);
        });

        Schema::table('pb_sku_upcs', function (Blueprint $table) {
            $table->unique('upc');
        });
    }
};
