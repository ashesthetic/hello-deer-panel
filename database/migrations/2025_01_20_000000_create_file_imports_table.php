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
        Schema::create('file_imports', function (Blueprint $table) {
            $table->id();
            $table->date('import_date');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->tinyInteger('processed')->default(0); // 0 = not processed, 1 = processed
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index('import_date');
            $table->index('processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_imports');
    }
};
