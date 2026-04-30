<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbDepartment extends Model
{
    protected $primaryKey = 'department_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'department_number',
        'description',
        'shift_report_flag',
        'sales_summary_report',
        'owner',
        'bt9000_inventory_control',
        'conexxus_product_code',
        'gift_card_department',
        'age_requirements',
        'default_item',
    ];

    protected $casts = [
        'shift_report_flag' => 'boolean',
        'sales_summary_report' => 'boolean',
        'bt9000_inventory_control' => 'boolean',
        'gift_card_department' => 'boolean',
    ];

    public function skus()
    {
        return $this->hasMany(PbSku::class, 'department_number', 'department_number');
    }

    public function transactionItems()
    {
        return $this->hasMany(PosTransactionItem::class, 'department_number', 'department_number');
    }
}
