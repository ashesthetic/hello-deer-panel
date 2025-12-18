<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pay_date',
        'employee_id',
        'regular_hours',
        'regular_rate',
        'regular_current',
        'regular_ytd',
        'stat_hours',
        'stat_rate',
        'stat_current',
        'stat_ytd',
        'overtime_hours',
        'overtime_rate',
        'overtime_current',
        'overtime_ytd',
        'total_hours',
        'total_current',
        'total_ytd',
        'cpp_emp_current',
        'cpp_emp_ytd',
        'ei_emp_current',
        'ei_emp_ytd',
        'fit_current',
        'fit_ytd',
        'total_deduction_current',
        'total_deduction_ytd',
        'vac_earned_current',
        'vac_earned_ytd',
        'vac_paid_current',
        'vac_paid_ytd',
        'net_pay',
        'payment_date',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'payment_date' => 'date',
        'regular_hours' => 'decimal:2',
        'regular_rate' => 'decimal:2',
        'regular_current' => 'decimal:2',
        'regular_ytd' => 'decimal:2',
        'stat_hours' => 'decimal:2',
        'stat_rate' => 'decimal:2',
        'stat_current' => 'decimal:2',
        'stat_ytd' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_current' => 'decimal:2',
        'overtime_ytd' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'total_current' => 'decimal:2',
        'total_ytd' => 'decimal:2',
        'cpp_emp_current' => 'decimal:2',
        'cpp_emp_ytd' => 'decimal:2',
        'ei_emp_current' => 'decimal:2',
        'ei_emp_ytd' => 'decimal:2',
        'fit_current' => 'decimal:2',
        'fit_ytd' => 'decimal:2',
        'total_deduction_current' => 'decimal:2',
        'total_deduction_ytd' => 'decimal:2',
        'vac_earned_current' => 'decimal:2',
        'vac_earned_ytd' => 'decimal:2',
        'vac_paid_current' => 'decimal:2',
        'vac_paid_ytd' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    /**
     * Get the employee that owns the payroll.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
