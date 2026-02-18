<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseType extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'expense_type',
        'parent_expense_type_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parent_expense_type_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the parent expense type.
     */
    public function parentExpenseType()
    {
        return $this->belongsTo(ExpenseType::class, 'parent_expense_type_id');
    }

    /**
     * Get all child expense types.
     */
    public function childExpenseTypes()
    {
        return $this->hasMany(ExpenseType::class, 'parent_expense_type_id');
    }

    /**
     * Get all expense breakdowns for this expense type.
     */
    public function expenseBreakdowns()
    {
        return $this->hasMany(ExpenseBreakdown::class);
    }
}
