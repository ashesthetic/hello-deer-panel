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
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            
            // Employee relationship
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            
            // Schedule period
            $table->date('week_start_date'); // Monday of the week
            $table->date('week_end_date');   // Sunday of the week
            
            // Schedule details
            $table->string('title')->nullable(); // Optional title for the schedule
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
            
            // System fields
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Created by
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['employee_id', 'week_start_date']);
            $table->index(['status', 'week_start_date']);
        });

        // Create work schedule days table for flexible daily hours
        Schema::create('work_schedule_days', function (Blueprint $table) {
            $table->id();
            
            // Work schedule relationship
            $table->foreignId('work_schedule_id')->constrained()->onDelete('cascade');
            
            // Day details
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->date('date');
            $table->time('start_time')->nullable(); // Can be null for days off
            $table->time('end_time')->nullable();   // Can be null for days off
            $table->decimal('hours_worked', 4, 2)->default(0); // Calculated hours
            $table->boolean('is_working_day')->default(false);
            $table->text('notes')->nullable(); // Day-specific notes
            
            $table->timestamps();
            
            // Indexes
            $table->index(['work_schedule_id', 'day_of_week']);
            $table->index(['date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_schedule_days');
        Schema::dropIfExists('work_schedules');
    }
};
