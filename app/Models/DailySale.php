<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySale extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'fuel_sale',
        'store_sale',
        'gst',
        'store_discount',
        'penny_rounding',
        'daily_total',
        'card',
        'cash',
        'coupon',
        'delivery',
        'lottery_payout',
        'breakdown_total',
        'reported_total',
        'number_of_safedrops',
        'safedrops_amount',
        'cash_on_hand',
        'pos_visa',
        'pos_mastercard',
        'pos_amex',
        'pos_commercial',
        'pos_up_credit',
        'pos_discover',
        'pos_interac_debit',
        'pos_debit_transaction_count',
        'afd_visa',
        'afd_mastercard',
        'afd_amex',
        'afd_commercial',
        'afd_up_credit',
        'afd_discover',
        'afd_interac_debit',
        'afd_debit_transaction_count',
        'journey_discount',
        'aeroplan_discount',
        'tobacco_25',
        'tobacco_20',
        'lottery',
        'prepay',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'fuel_sale' => 'decimal:2',
        'store_sale' => 'decimal:2',
        'gst' => 'decimal:2',
        'store_discount' => 'decimal:2',
        'penny_rounding' => 'decimal:2',
        'daily_total' => 'decimal:2',
        'card' => 'decimal:2',
        'cash' => 'decimal:2',
        'coupon' => 'decimal:2',
        'delivery' => 'decimal:2',
        'lottery_payout' => 'decimal:2',
        'breakdown_total' => 'decimal:2',
        'reported_total' => 'decimal:2',
        'number_of_safedrops' => 'integer',
        'safedrops_amount' => 'decimal:2',
        'cash_on_hand' => 'decimal:2',
        'pos_visa' => 'decimal:2',
        'pos_mastercard' => 'decimal:2',
        'pos_amex' => 'decimal:2',
        'pos_commercial' => 'decimal:2',
        'pos_up_credit' => 'decimal:2',
        'pos_discover' => 'decimal:2',
        'pos_interac_debit' => 'decimal:2',
        'pos_debit_transaction_count' => 'integer',
        'afd_visa' => 'decimal:2',
        'afd_mastercard' => 'decimal:2',
        'afd_amex' => 'decimal:2',
        'afd_commercial' => 'decimal:2',
        'afd_up_credit' => 'decimal:2',
        'afd_discover' => 'decimal:2',
        'afd_interac_debit' => 'decimal:2',
        'afd_debit_transaction_count' => 'integer',
        'journey_discount' => 'decimal:2',
        'aeroplan_discount' => 'decimal:2',
        'tobacco_25' => 'decimal:2',
        'tobacco_20' => 'decimal:2',
        'lottery' => 'decimal:2',
        'prepay' => 'decimal:2',
    ];

    // Calculate daily total (Fuel Sales + Item Sales + Store Discount + GST + Penny Rounding)
    public function getDailyTotalAttribute()
    {
        return $this->fuel_sale + $this->store_sale + $this->store_discount + $this->gst + $this->penny_rounding;
    }

    // Calculate breakdown total (POS Sale + Cash + Loyalty Coupon + Delivery + Lottery Payout)
    public function getBreakdownTotalAttribute()
    {
        return $this->card + $this->cash + $this->coupon + $this->delivery + $this->lottery_payout;
    }

    // Calculate total POS transactions
    public function getTotalPosTransactionsAttribute()
    {
        return $this->pos_visa + $this->pos_mastercard + $this->pos_amex + $this->pos_commercial + 
               $this->pos_up_credit + $this->pos_discover + $this->pos_interac_debit;
    }

    // Calculate total AFD transactions
    public function getTotalAfdTransactionsAttribute()
    {
        return $this->afd_visa + $this->afd_mastercard + $this->afd_amex + $this->afd_commercial + 
               $this->afd_up_credit + $this->afd_discover + $this->afd_interac_debit;
    }

    // Calculate total loyalty discounts
    public function getTotalLoyaltyDiscountsAttribute()
    {
        return $this->journey_discount + $this->aeroplan_discount;
    }

    // Calculate total low margin items
    public function getTotalLowMarginItemsAttribute()
    {
        return $this->tobacco_25 + $this->tobacco_20 + $this->lottery + $this->prepay;
    }

    // Calculate store sale (Store Sale - (Tobacco 25 + Tobacco 20 + Lottery + Prepay + GST))
    public function getStoreSaleCalculatedAttribute()
    {
        $lowMarginItems = $this->tobacco_25 + $this->tobacco_20 + $this->lottery + $this->prepay;
        return $this->store_sale - $lowMarginItems - $this->gst;
    }

    // Calculate approximate profit
    public function getApproximateProfitAttribute()
    {
        $fuelProfit = ($this->fuel_sale * config('profit.fuel_percentage', 4)) / 100;
        $tobacco25Profit = ($this->tobacco_25 * config('profit.tobacco_25_percentage', 8)) / 100;
        $tobacco20Profit = ($this->tobacco_20 * config('profit.tobacco_20_percentage', 8)) / 100;
        $lotteryProfit = ($this->lottery * config('profit.lottery_percentage', 2)) / 100;
        $prepayProfit = ($this->prepay * config('profit.prepay_percentage', 1)) / 100;
        
        // Calculate store sale directly to avoid infinite loop
        $lowMarginItems = $this->tobacco_25 + $this->tobacco_20 + $this->lottery + $this->prepay;
        $storeSaleCalculated = $this->store_sale - $lowMarginItems - $this->gst;
        $storeSaleProfit = ($storeSaleCalculated * config('profit.store_sale_percentage', 50)) / 100;

        return $fuelProfit + $tobacco25Profit + $tobacco20Profit + $lotteryProfit + $prepayProfit + $storeSaleProfit;
    }

    // Legacy methods for backward compatibility
    public function getTotalProductSaleAttribute()
    {
        return $this->fuel_sale + $this->store_sale + $this->gst;
    }

    public function getTotalCounterSaleAttribute()
    {
        return $this->card + $this->cash + $this->coupon + $this->delivery;
    }

    public function getGrandTotalAttribute()
    {
        return $this->total_product_sale + $this->total_counter_sale;
    }

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
