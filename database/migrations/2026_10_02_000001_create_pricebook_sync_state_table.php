<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricebook_sync_state', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('last_revision')->nullable();
            $table->string('head_etag')->nullable();
            $table->timestamp('running_since')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->unsignedBigInteger('last_snapshot_revision')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricebook_sync_state');
    }
};
