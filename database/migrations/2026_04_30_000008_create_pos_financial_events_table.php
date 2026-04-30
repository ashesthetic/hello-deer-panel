<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_financial_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('naxml_import_id')->constrained('naxml_imports')->cascadeOnDelete();
            $table->string('store_location_id');
            $table->string('shift_id');
            $table->string('register_id')->nullable();
            $table->string('cashier_id')->nullable();
            $table->string('sequence_number');
            $table->date('business_date')->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->string('account_id')->nullable();
            $table->string('account_name')->nullable();
            $table->decimal('detail_amount', 10, 2)->default(0);
            $table->string('tender_code')->nullable();
            $table->string('tender_sub_code')->nullable();
            $table->decimal('tender_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_financial_events');
    }
};
