<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailySale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Utils\TimezoneUtil;

class DailySaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Build query based on user role
        $query = DailySale::with('user');
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        // Add date range filter if provided
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        // Handle sorting for calculated fields
        if ($sortBy === 'daily_total') {
            $query->orderByRaw('(fuel_sale + store_sale + store_discount + gst + penny_rounding) ' . $sortDirection);
        } elseif ($sortBy === 'breakdown_total') {
            $query->orderByRaw('(card + cash + coupon + delivery) ' . $sortDirection);
        } elseif ($sortBy === 'reported_total') {
            $query->orderBy('reported_total', $sortDirection);
        } elseif ($sortBy === 'approximate_profit') {
            // For approximate profit, we'll sort by the calculated value after fetching
            $query->orderBy('date', $sortDirection); // Default sort, will be re-sorted after calculation
        } else {
            // Default sorting and other direct fields
            $allowedSortFields = [
                'date', 'fuel_sale', 'store_sale', 'gst', 'store_discount', 'penny_rounding',
                'card', 'cash', 'coupon', 'delivery', 'reported_total', 'number_of_safedrops',
                'safedrops_amount', 'cash_on_hand', 'approximate_profit'
            ];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'date';
            }
            $query->orderBy($sortBy, $sortDirection);
        }
        
        $dailySales = $query->paginate($perPage);
        
        // Add calculated fields to each sale
        $dailySales->getCollection()->transform(function ($sale) {
            $sale->daily_total = $sale->fuel_sale + $sale->store_sale + $sale->store_discount + $sale->gst + $sale->penny_rounding;
            $sale->breakdown_total = $sale->card + $sale->cash + $sale->coupon + $sale->delivery;
            $sale->total_pos_transactions = $sale->pos_visa + $sale->pos_mastercard + $sale->pos_amex + $sale->pos_commercial + 
                                           $sale->pos_up_credit + $sale->pos_discover + $sale->pos_interac_debit;
            $sale->total_afd_transactions = $sale->afd_visa + $sale->afd_mastercard + $sale->afd_amex + $sale->afd_commercial + 
                                           $sale->afd_up_credit + $sale->afd_discover + $sale->afd_interac_debit;
            $sale->total_loyalty_discounts = $sale->journey_discount + $sale->aeroplan_discount;
            $sale->total_low_margin_items = $sale->tobacco_25 + $sale->tobacco_20 + $sale->lottery + $sale->prepay;
            $sale->store_sale_calculated = $sale->getStoreSaleCalculatedAttribute();
            $sale->approximate_profit = $sale->getApproximateProfitAttribute();
            
            // Legacy fields for backward compatibility
            $sale->total_product_sale = $sale->fuel_sale + $sale->store_sale + $sale->gst;
            $sale->total_counter_sale = $sale->card + $sale->cash + $sale->coupon + $sale->delivery;
            $sale->grand_total = $sale->total_product_sale + $sale->total_counter_sale;
            
            // Ensure reported_total is not null
            $sale->reported_total = $sale->reported_total ?? 0;
            return $sale;
        });
        
        // Handle sorting for approximate_profit after calculations
        if ($sortBy === 'approximate_profit') {
            $collection = $dailySales->getCollection();
            if ($sortDirection === 'asc') {
                $collection = $collection->sortBy('approximate_profit');
            } else {
                $collection = $collection->sortByDesc('approximate_profit');
            }
            $dailySales->setCollection($collection);
        }
        
        return response()->json($dailySales);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date' => 'required|date|unique:daily_sales,date',
            'fuel_sale' => 'required|numeric|min:0',
            'store_sale' => 'required|numeric|min:0',
            'gst' => 'required|numeric|min:0',
            'store_discount' => 'required|numeric|min:0',
            'penny_rounding' => 'required|numeric|min:0',
            'card' => 'required|numeric|min:0',
            'cash' => 'required|numeric|min:0',
            'coupon' => 'required|numeric|min:0',
            'delivery' => 'required|numeric|min:0',
            'lottery_payout' => 'required|numeric|min:0',
            'reported_total' => 'required|numeric|min:0',
            'number_of_safedrops' => 'required|integer|min:0',
            'safedrops_amount' => 'required|numeric|min:0',
            'cash_on_hand' => 'required|numeric',
            'pos_visa' => 'required|numeric|min:0',
            'pos_mastercard' => 'required|numeric|min:0',
            'pos_amex' => 'required|numeric|min:0',
            'pos_commercial' => 'required|numeric|min:0',
            'pos_up_credit' => 'required|numeric|min:0',
            'pos_discover' => 'required|numeric|min:0',
            'pos_interac_debit' => 'required|numeric|min:0',
            'pos_debit_transaction_count' => 'required|integer|min:0',
            'afd_visa' => 'required|numeric|min:0',
            'afd_mastercard' => 'required|numeric|min:0',
            'afd_amex' => 'required|numeric|min:0',
            'afd_commercial' => 'required|numeric|min:0',
            'afd_up_credit' => 'required|numeric|min:0',
            'afd_discover' => 'required|numeric|min:0',
            'afd_interac_debit' => 'required|numeric|min:0',
            'afd_debit_transaction_count' => 'required|integer|min:0',
            'journey_discount' => 'required|numeric|min:0',
            'aeroplan_discount' => 'required|numeric|min:0',
            'tobacco_25' => 'required|numeric|min:0',
            'tobacco_20' => 'required|numeric|min:0',
            'lottery' => 'required|numeric|min:0',
            'prepay' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['user_id'] = $user->id; // Associate with current user
        
        $dailySale = DailySale::create($data);
        
        // Add calculated fields
        $dailySale->daily_total = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->store_discount + $dailySale->gst + $dailySale->penny_rounding;
        $dailySale->breakdown_total = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery + $dailySale->lottery_payout;
        $dailySale->total_pos_transactions = $dailySale->pos_visa + $dailySale->pos_mastercard + $dailySale->pos_amex + $dailySale->pos_commercial + 
                                            $dailySale->pos_up_credit + $dailySale->pos_discover + $dailySale->pos_interac_debit;
        $dailySale->total_afd_transactions = $dailySale->afd_visa + $dailySale->afd_mastercard + $dailySale->afd_amex + $dailySale->afd_commercial + 
                                            $dailySale->afd_up_credit + $dailySale->afd_discover + $dailySale->afd_interac_debit;
        $dailySale->total_loyalty_discounts = $dailySale->journey_discount + $dailySale->aeroplan_discount;
        $dailySale->total_low_margin_items = $dailySale->tobacco_25 + $dailySale->tobacco_20 + $dailySale->lottery + $dailySale->prepay;
        $dailySale->store_sale_calculated = $dailySale->getStoreSaleCalculatedAttribute();
        $dailySale->approximate_profit = $dailySale->getApproximateProfitAttribute();
        
        // Legacy fields for backward compatibility
        $dailySale->total_product_sale = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->gst;
        $dailySale->total_counter_sale = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery;
        $dailySale->grand_total = $dailySale->total_product_sale + $dailySale->total_counter_sale;
        
        // Ensure reported_total is not null
        $dailySale->reported_total = $dailySale->reported_total ?? 0;

        return response()->json([
            'message' => 'Daily sale created successfully',
            'data' => $dailySale
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, DailySale $dailySale)
    {
        $user = $request->user();
        
        // Check if user can view this specific sale
        if ($user->isEditor() && $dailySale->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Add calculated fields
        $dailySale->daily_total = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->store_discount + $dailySale->gst + $dailySale->penny_rounding;
        $dailySale->breakdown_total = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery + $dailySale->lottery_payout;
        $dailySale->total_pos_transactions = $dailySale->pos_visa + $dailySale->pos_mastercard + $dailySale->pos_amex + $dailySale->pos_commercial + 
                                            $dailySale->pos_up_credit + $dailySale->pos_discover + $dailySale->pos_interac_debit;
        $dailySale->total_afd_transactions = $dailySale->afd_visa + $dailySale->afd_mastercard + $dailySale->afd_amex + $dailySale->afd_commercial + 
                                            $dailySale->afd_up_credit + $dailySale->afd_discover + $dailySale->afd_interac_debit;
        $dailySale->total_loyalty_discounts = $dailySale->journey_discount + $dailySale->aeroplan_discount;
        $dailySale->total_low_margin_items = $dailySale->tobacco_25 + $dailySale->tobacco_20 + $dailySale->lottery + $dailySale->prepay;
        $dailySale->store_sale_calculated = $dailySale->getStoreSaleCalculatedAttribute();
        $dailySale->approximate_profit = $dailySale->getApproximateProfitAttribute();
        
        // Legacy fields for backward compatibility
        $dailySale->total_product_sale = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->gst;
        $dailySale->total_counter_sale = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery;
        $dailySale->grand_total = $dailySale->total_product_sale + $dailySale->total_counter_sale;
        
        // Ensure reported_total is not null
        $dailySale->reported_total = $dailySale->reported_total ?? 0;
        
        return response()->json($dailySale);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailySale $dailySale)
    {
        $user = $request->user();
        
        if (!$user->canUpdateDailySale($dailySale)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date' => ['required', 'date', Rule::unique('daily_sales')->ignore($dailySale->id)],
            'fuel_sale' => 'required|numeric|min:0',
            'store_sale' => 'required|numeric|min:0',
            'gst' => 'required|numeric|min:0',
            'store_discount' => 'required|numeric|min:0',
            'penny_rounding' => 'required|numeric|min:0',
            'card' => 'required|numeric|min:0',
            'cash' => 'required|numeric|min:0',
            'coupon' => 'required|numeric|min:0',
            'delivery' => 'required|numeric|min:0',
            'lottery_payout' => 'required|numeric|min:0',
            'reported_total' => 'required|numeric|min:0',
            'number_of_safedrops' => 'required|integer|min:0',
            'safedrops_amount' => 'required|numeric|min:0',
            'cash_on_hand' => 'required|numeric',
            'pos_visa' => 'required|numeric|min:0',
            'pos_mastercard' => 'required|numeric|min:0',
            'pos_amex' => 'required|numeric|min:0',
            'pos_commercial' => 'required|numeric|min:0',
            'pos_up_credit' => 'required|numeric|min:0',
            'pos_discover' => 'required|numeric|min:0',
            'pos_interac_debit' => 'required|numeric|min:0',
            'pos_debit_transaction_count' => 'required|integer|min:0',
            'afd_visa' => 'required|numeric|min:0',
            'afd_mastercard' => 'required|numeric|min:0',
            'afd_amex' => 'required|numeric|min:0',
            'afd_commercial' => 'required|numeric|min:0',
            'afd_up_credit' => 'required|numeric|min:0',
            'afd_discover' => 'required|numeric|min:0',
            'afd_interac_debit' => 'required|numeric|min:0',
            'afd_debit_transaction_count' => 'required|integer|min:0',
            'journey_discount' => 'required|numeric|min:0',
            'aeroplan_discount' => 'required|numeric|min:0',
            'tobacco_25' => 'required|numeric|min:0',
            'tobacco_20' => 'required|numeric|min:0',
            'lottery' => 'required|numeric|min:0',
            'prepay' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $dailySale->update($request->all());
        
        // Add calculated fields
        $dailySale->daily_total = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->store_discount + $dailySale->gst + $dailySale->penny_rounding;
        $dailySale->breakdown_total = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery + $dailySale->lottery_payout;
        $dailySale->total_pos_transactions = $dailySale->pos_visa + $dailySale->pos_mastercard + $dailySale->pos_amex + $dailySale->pos_commercial + 
                                            $dailySale->pos_up_credit + $dailySale->pos_discover + $dailySale->pos_interac_debit;
        $dailySale->total_afd_transactions = $dailySale->afd_visa + $dailySale->afd_mastercard + $dailySale->afd_amex + $dailySale->afd_commercial + 
                                            $dailySale->afd_up_credit + $dailySale->afd_discover + $dailySale->afd_interac_debit;
        $dailySale->total_loyalty_discounts = $dailySale->journey_discount + $dailySale->aeroplan_discount;
        $dailySale->total_low_margin_items = $dailySale->tobacco_25 + $dailySale->tobacco_20 + $dailySale->lottery + $dailySale->prepay;
        $dailySale->store_sale_calculated = $dailySale->getStoreSaleCalculatedAttribute();
        $dailySale->approximate_profit = $dailySale->getApproximateProfitAttribute();
        
        // Legacy fields for backward compatibility
        $dailySale->total_product_sale = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->gst;
        $dailySale->total_counter_sale = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery;
        $dailySale->grand_total = $dailySale->total_product_sale + $dailySale->total_counter_sale;
        
        // Ensure reported_total is not null
        $dailySale->reported_total = $dailySale->reported_total ?? 0;

        return response()->json([
            'message' => 'Daily sale updated successfully',
            'data' => $dailySale
        ]);
    }

    /**
     * Get sales for a specific month
     */
    public function getByMonth(Request $request, $year = null, $month = null)
    {
        $user = $request->user();
        $year = $year ?: TimezoneUtil::now()->format('Y');
        $month = $month ?: TimezoneUtil::now()->format('n');
        
        // Build query based on user role
        $query = DailySale::with('user');
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        $dailySales = $query->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get();
        
        // Add calculated fields to each sale
        $dailySales->transform(function ($sale) {
            $sale->daily_total = $sale->fuel_sale + $sale->store_sale + $sale->store_discount + $sale->gst + $sale->penny_rounding;
            $sale->breakdown_total = $sale->card + $sale->cash + $sale->coupon + $sale->delivery + $sale->lottery_payout;
            $sale->total_pos_transactions = $sale->pos_visa + $sale->pos_mastercard + $sale->pos_amex + $sale->pos_commercial + 
                                           $sale->pos_up_credit + $sale->pos_discover + $sale->pos_interac_debit;
            $sale->total_afd_transactions = $sale->afd_visa + $sale->afd_mastercard + $sale->afd_amex + $sale->afd_commercial + 
                                           $sale->afd_up_credit + $sale->afd_discover + $sale->afd_interac_debit;
            $sale->total_loyalty_discounts = $sale->journey_discount + $sale->aeroplan_discount;
            $sale->total_low_margin_items = $sale->tobacco_25 + $sale->tobacco_20 + $sale->lottery + $sale->prepay;
            $sale->store_sale_calculated = $sale->getStoreSaleCalculatedAttribute();
            $sale->approximate_profit = $sale->getApproximateProfitAttribute();
            
            // Legacy fields for backward compatibility
            $sale->total_product_sale = $sale->fuel_sale + $sale->store_sale + $sale->gst;
            $sale->total_counter_sale = $sale->card + $sale->cash + $sale->coupon + $sale->delivery;
            $sale->grand_total = $sale->total_product_sale + $sale->total_counter_sale;
            
            // Ensure reported_total is not null
            $sale->reported_total = $sale->reported_total ?? 0;
            return $sale;
        });
        
        return response()->json([
            'data' => $dailySales,
            'year' => $year,
            'month' => $month
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, DailySale $dailySale)
    {
        $user = $request->user();
        
        if (!$user->canDelete()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dailySale->delete();

        return response()->json([
            'message' => 'Daily sale deleted successfully'
        ]);
    }

    /**
     * Generate settlement report for a date range
     */
    public function generateSettlementReport(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'specific_dates' => 'nullable|array',
            'specific_dates.*' => 'date',
            'include_debit' => 'nullable|boolean',
            'include_credit' => 'nullable|boolean',
        ]);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $specificDates = $request->input('specific_dates', []);
        $includeDebit = $request->input('include_debit', true); // Default to true for backward compatibility
        $includeCredit = $request->input('include_credit', true); // Default to true for backward compatibility

        // Build query based on user role
        $query = DailySale::query();
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        // Get sales for the date range
        $dailySales = $query->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date', 'asc')
            ->get();

        // If specific dates are provided, also get those sales
        $specificDateSales = collect();
        if (!empty($specificDates)) {
            $specificDateSales = DailySale::query();
            
            // Apply user role filter
            if ($user->isEditor()) {
                $specificDateSales->where('user_id', $user->id);
            }
            
            $specificDateSales = $specificDateSales->whereIn('date', $specificDates)
                ->orderBy('date', 'asc')
                ->get();
        }

        // Merge and deduplicate sales (in case specific dates overlap with date range)
        $allSales = $dailySales->merge($specificDateSales)->unique('id')->sortBy('date');

        $settlementData = [];
        
        // Get fee percentages from environment
        $visaFeePercentage = env('VISA_FEE_PERCENTAGE', 1.75);
        $mastercardFeePercentage = env('MASTERCARD_FEE_PERCENTAGE', 1.75);
        $amexFeePercentage = env('AMEX_FEE_PERCENTAGE', 2.22);
        $interacFeePerTransaction = env('INTERAC_FEE_PER_TRANSACTION', 0.07);

        foreach ($allSales as $sale) {
            // Fix timezone issue by using the date directly without formatting
            $date = $sale->date->toDateString();
            
            // Define all payment types with their corresponding fee types
            $allPaymentTypes = [
                'POS VISA' => ['amount' => $sale->pos_visa, 'fee_type' => 'visa', 'transaction_type' => 'credit'],
                'POS MASTERCARD' => ['amount' => $sale->pos_mastercard, 'fee_type' => 'mastercard', 'transaction_type' => 'credit'],
                'POS AMEX' => ['amount' => $sale->pos_amex, 'fee_type' => 'amex', 'transaction_type' => 'credit'],
                'POS COMMERCIAL' => ['amount' => $sale->pos_commercial, 'fee_type' => null, 'transaction_type' => 'credit'],
                'POS UP CREDIT' => ['amount' => $sale->pos_up_credit, 'fee_type' => null, 'transaction_type' => 'credit'],
                'POS DISCOVER' => ['amount' => $sale->pos_discover, 'fee_type' => null, 'transaction_type' => 'credit'],
                'POS INTERAC' => ['amount' => $sale->pos_interac_debit, 'fee_type' => 'interac', 'transaction_count' => $sale->pos_debit_transaction_count, 'transaction_type' => 'debit'],
                'AFD VISA' => ['amount' => $sale->afd_visa, 'fee_type' => 'visa', 'transaction_type' => 'credit'],
                'AFD MASTERCARD' => ['amount' => $sale->afd_mastercard, 'fee_type' => 'mastercard', 'transaction_type' => 'credit'],
                'AFD AMEX' => ['amount' => $sale->afd_amex, 'fee_type' => 'amex', 'transaction_type' => 'credit'],
                'AFD COMMERCIAL' => ['amount' => $sale->afd_commercial, 'fee_type' => null, 'transaction_type' => 'credit'],
                'AFD UP CREDIT' => ['amount' => $sale->afd_up_credit, 'fee_type' => null, 'transaction_type' => 'credit'],
                'AFD DISCOVER' => ['amount' => $sale->afd_discover, 'fee_type' => null, 'transaction_type' => 'credit'],
                'AFD INTERAC' => ['amount' => $sale->afd_interac_debit, 'fee_type' => 'interac', 'transaction_count' => $sale->afd_debit_transaction_count, 'transaction_type' => 'debit'],
            ];

            // Filter payment types based on transaction type selection
            $paymentTypes = [];
            foreach ($allPaymentTypes as $type => $data) {
                if (($includeDebit && $data['transaction_type'] === 'debit') || 
                    ($includeCredit && $data['transaction_type'] === 'credit')) {
                    $paymentTypes[$type] = $data;
                }
            }

            // Add payment amounts (Credit entries) and their corresponding fees
            foreach ($paymentTypes as $type => $data) {
                $amount = $data['amount'];
                $feeType = $data['fee_type'];
                $transactionCount = $data['transaction_count'] ?? 0;
                
                if ($amount > 0) {
                    // Add the payment amount
                    $settlementData[] = [
                        'date' => $date,
                        'remarks' => $type,
                        'debit' => 0,
                        'credit' => $amount,
                    ];
                    
                    // Add the corresponding fee if applicable
                    if ($feeType === 'visa') {
                        $fee = ($amount * $visaFeePercentage) / 100;
                        $settlementData[] = [
                            'date' => $date,
                            'remarks' => "VISA Fee ({$visaFeePercentage}%)",
                            'debit' => $fee,
                            'credit' => 0,
                        ];
                    } elseif ($feeType === 'mastercard') {
                        $fee = ($amount * $mastercardFeePercentage) / 100;
                        $settlementData[] = [
                            'date' => $date,
                            'remarks' => "Mastercard Fee ({$mastercardFeePercentage}%)",
                            'debit' => $fee,
                            'credit' => 0,
                        ];
                    } elseif ($feeType === 'amex') {
                        $fee = ($amount * $amexFeePercentage) / 100;
                        $settlementData[] = [
                            'date' => $date,
                            'remarks' => "AMEX Fee ({$amexFeePercentage}%)",
                            'debit' => $fee,
                            'credit' => 0,
                        ];
                    } elseif ($feeType === 'interac') {
                        $fee = $transactionCount * $interacFeePerTransaction;
                        $settlementData[] = [
                            'date' => $date,
                            'remarks' => "Interac Fee (\${$interacFeePerTransaction} per transaction, {$transactionCount} transactions)",
                            'debit' => $fee,
                            'credit' => 0,
                        ];
                    }
                }
            }
        }

        return response()->json([
            'data' => $settlementData,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'specific_dates' => $specificDates,
            'include_debit' => $includeDebit,
            'include_credit' => $includeCredit,
            'total_entries' => count($settlementData),
        ]);
    }

    /**
     * Get last settled dates
     */
    public function getSettlementDates()
    {
        $debitDate = \App\Models\Option::get('last_settled_debit_date');
        $creditDate = \App\Models\Option::get('last_settled_credit_date');

        return response()->json([
            'debit_date' => $debitDate,
            'credit_date' => $creditDate,
        ]);
    }

    /**
     * Update last settled dates
     */
    public function updateSettlementDates(Request $request)
    {
        $validated = $request->validate([
            'debit_date' => 'required|date',
            'credit_date' => 'required|date',
        ]);

        \App\Models\Option::set('last_settled_debit_date', $validated['debit_date']);
        \App\Models\Option::set('last_settled_credit_date', $validated['credit_date']);

        return response()->json([
            'message' => 'Settlement dates updated successfully',
            'debit_date' => $validated['debit_date'],
            'credit_date' => $validated['credit_date'],
        ]);
    }

    /**
     * Get monthly trend data
     */
    public function getMonthlyTrends(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = DailySale::query();

        // Add date range filter if provided
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        // Get all sales data
        $sales = $query->orderBy('date', 'asc')->get();

        // Group data by month
        $monthlyData = [];
        
        foreach ($sales as $sale) {
            $date = new \DateTime($sale->date);
            $monthKey = $date->format('Y-m'); // e.g., "2024-12"
            
            if (!isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey] = [
                    'month' => $monthKey,
                    'month_name' => $date->format('F Y'),
                    'total_sales' => 0,
                    'total_profits' => 0,
                    'tobacco_25' => 0,
                    'tobacco_20' => 0,
                    'lottery' => 0,
                ];
            }
            
            // Accumulate values
            $monthlyData[$monthKey]['total_sales'] += $sale->reported_total;
            $monthlyData[$monthKey]['total_profits'] += $sale->approximate_profit;
            $monthlyData[$monthKey]['tobacco_25'] += $sale->tobacco_25;
            $monthlyData[$monthKey]['tobacco_20'] += $sale->tobacco_20;
            $monthlyData[$monthKey]['lottery'] += $sale->lottery;
        }

        // Get fuel data
        $fuelQuery = \App\Models\DailyFuel::query();

        if ($startDate) {
            $fuelQuery->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $fuelQuery->where('date', '<=', $endDate);
        }

        $fuels = $fuelQuery->orderBy('date', 'asc')->get();

        // Add fuel data to monthly aggregates
        foreach ($fuels as $fuel) {
            $date = new \DateTime($fuel->date);
            $monthKey = $date->format('Y-m');
            
            if (!isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey] = [
                    'month' => $monthKey,
                    'month_name' => $date->format('F Y'),
                    'total_sales' => 0,
                    'total_profits' => 0,
                    'tobacco_25' => 0,
                    'tobacco_20' => 0,
                    'lottery' => 0,
                    'fuel_quantity' => 0,
                ];
            }
            
            if (!isset($monthlyData[$monthKey]['fuel_quantity'])) {
                $monthlyData[$monthKey]['fuel_quantity'] = 0;
            }
            
            $monthlyData[$monthKey]['fuel_quantity'] += $fuel->total_quantity;
        }

        // Convert to array and sort by month
        $result = array_values($monthlyData);
        usort($result, function($a, $b) {
            return strcmp($a['month'], $b['month']);
        });

        return response()->json([
            'data' => $result,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
