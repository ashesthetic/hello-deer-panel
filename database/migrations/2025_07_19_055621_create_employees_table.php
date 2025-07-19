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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            
            // Personal Information
            $table->string('full_legal_name');
            $table->string('preferred_name')->nullable();
            $table->date('date_of_birth');
            $table->text('address');
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Canada');
            $table->string('phone_number');
            $table->string('alternate_number')->nullable();
            $table->string('email')->unique();
            
            // Emergency Contact
            $table->string('emergency_name');
            $table->string('emergency_relationship');
            $table->string('emergency_address_line1')->nullable();
            $table->string('emergency_address_line2')->nullable();
            $table->string('emergency_city')->nullable();
            $table->string('emergency_state')->nullable();
            $table->string('emergency_postal_code')->nullable();
            $table->string('emergency_country')->nullable();
            $table->string('emergency_phone');
            $table->string('emergency_alternate_number')->nullable();
            
            // Official Information
            $table->string('status_in_canada');
            $table->string('other_status')->nullable();
            $table->string('sin_number');
            
            // Employment Information
            $table->string('position');
            $table->string('department');
            $table->date('hire_date');
            $table->decimal('hourly_rate', 8, 2)->default(15.00);
            
            // Social Information
            $table->string('facebook')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('twitter')->nullable();
            
            // File Uploads
            $table->string('government_id_file')->nullable();
            $table->string('work_permit_file')->nullable();
            $table->string('resume_file')->nullable();
            $table->string('photo_file')->nullable();
            $table->string('void_cheque_file')->nullable();
            
            // System Fields
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
