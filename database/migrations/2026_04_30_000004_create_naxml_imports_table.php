<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('naxml_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->unique();
            $table->string('filepath');
            $table->date('business_date')->nullable()->index();
            $table->string('shift_id')->nullable();
            $table->string('store_location_id')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('transaction_count')->default(0);
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('financial_event_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('naxml_imports');
    }
};
