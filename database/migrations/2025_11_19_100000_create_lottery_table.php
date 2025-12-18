<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('item');
            $table->enum('shift', ['Morning', 'Evening']);
            $table->decimal('start', 10, 2)->default(0);
            $table->decimal('end', 10, 2)->default(0);
            $table->decimal('added', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery');
    }
};
