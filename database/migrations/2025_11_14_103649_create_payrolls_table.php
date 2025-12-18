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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->date('pay_date');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            
            // Regular Pay
            $table->decimal('regular_hours', 8, 2)->default(0);
            $table->decimal('regular_rate', 10, 2)->default(0);
            $table->decimal('regular_current', 10, 2)->default(0);
            $table->decimal('regular_ytd', 10, 2)->default(0);
            
            // Stat Pay
            $table->decimal('stat_hours', 8, 2)->default(0);
            $table->decimal('stat_rate', 10, 2)->default(0);
            $table->decimal('stat_current', 10, 2)->default(0);
            $table->decimal('stat_ytd', 10, 2)->default(0);
            
            // Overtime Pay
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);
            $table->decimal('overtime_current', 10, 2)->default(0);
            $table->decimal('overtime_ytd', 10, 2)->default(0);
            
            // Totals
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->decimal('total_current', 10, 2)->default(0);
            $table->decimal('total_ytd', 10, 2)->default(0);
            
            // Deductions - CPP
            $table->decimal('cpp_emp_current', 10, 2)->default(0);
            $table->decimal('cpp_emp_ytd', 10, 2)->default(0);
            
            // Deductions - EI
            $table->decimal('ei_emp_current', 10, 2)->default(0);
            $table->decimal('ei_emp_ytd', 10, 2)->default(0);
            
            // Deductions - FIT
            $table->decimal('fit_current', 10, 2)->default(0);
            $table->decimal('fit_ytd', 10, 2)->default(0);
            
            // Total Deductions
            $table->decimal('total_deduction_current', 10, 2)->default(0);
            $table->decimal('total_deduction_ytd', 10, 2)->default(0);
            
            // Vacation
            $table->decimal('vac_earned_current', 10, 2)->default(0);
            $table->decimal('vac_earned_ytd', 10, 2)->default(0);
            $table->decimal('vac_paid_current', 10, 2)->default(0);
            $table->decimal('vac_paid_ytd', 10, 2)->default(0);
            
            // Net Pay
            $table->decimal('net_pay', 10, 2)->default(0);
            $table->date('payment_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
