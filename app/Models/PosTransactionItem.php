<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransactionItem extends Model
{
    protected $fillable = [
        'pos_transaction_id',
        'plu_code',
        'item_number',
        'department_number',
        'plu_modifier',
        'line_sequence',
        'description',
        'merchandise_code',
        'entry_method',
        'quantity',
        'actual_sale_price',
        'regular_sell_price',
        'sales_amount',
        'tax_level_id',
        'tax_collected_amount',
        'taxable_sales_amount',
        'item_type_code',
        'item_type_sub_code',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'actual_sale_price' => 'decimal:2',
        'regular_sell_price' => 'decimal:2',
        'sales_amount' => 'decimal:2',
        'tax_collected_amount' => 'decimal:2',
        'taxable_sales_amount' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function sku()
    {
        return $this->belongsTo(PbSku::class, 'item_number', 'item_number');
    }

    public function department()
    {
        return $this->belongsTo(PbDepartment::class, 'department_number', 'department_number');
    }
}
