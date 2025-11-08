<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\VendorInvoice;
use App\Models\Transaction;
use App\Models\DailySale;
use App\Models\DailyFuel;
use App\Models\FuelVolume;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Total money in all banks
        $totalMoneyInBanks = $this->getTotalMoneyInBanks($user);

        // Total unpaid invoices with details
        $totalUnpaidInvoices = $this->getTotalUnpaidInvoicesWithDetails($user);

        // Yesterday's data
        $yesterdayData = $this->getYesterdayData($user);

        // Last week's data
        $lastWeekData = $this->getLastWeekData($user);

        // Current week's data
        $currentWeekData = $this->getCurrentWeekData($user);

        // Last month's performance data
        $lastMonthData = $this->getLastMonthData($user);

        // Current month's data
        $currentMonthData = $this->getCurrentMonthData($user);

        // Last month's income
        $lastMonthIncome = $this->getLastMonthIncome($user);

        // Last month's expenses
        $lastMonthExpenses = $this->getLastMonthExpenses($user);

        return response()->json([
            'total_money_in_banks' => $totalMoneyInBanks,
            'total_unpaid_invoices' => $totalUnpaidInvoices,
            'yesterday_data' => $yesterdayData,
            'last_week_data' => $lastWeekData,
            'current_week_data' => $currentWeekData,
            'last_month_data' => $lastMonthData,
            'current_month_data' => $currentMonthData,
            'last_month_income' => $lastMonthIncome,
            'last_month_expenses' => $lastMonthExpenses,
        ]);
    }

    /**
     * Get total money in all bank accounts
     */
    private function getTotalMoneyInBanks($user): array
    {
        $query = BankAccount::where('is_active', true);

        // Apply user access control
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }

        $bankAccounts = $query->get();
        $totalBalance = $bankAccounts->sum('balance');
        $accountCount = $bankAccounts->count();

        // Get breakdown by account type
        $accountsByType = $bankAccounts->groupBy('account_type')->map(function ($accounts) {
            return [
                'count' => $accounts->count(),
                'total_balance' => $accounts->sum('balance'),
            ];
        });

        // Get individual bank account details
        $bankAccountDetails = $bankAccounts->map(function ($account) {
            return [
                'id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'balance' => $account->balance,
                'formatted_balance' => '$' . number_format($account->balance, 2),
            ];
        });

        return [
            'total_balance' => $totalBalance,
            'formatted_total_balance' => '$' . number_format($totalBalance, 2),
            'account_count' => $accountCount,
            'accounts_by_type' => $accountsByType,
            'bank_accounts' => $bankAccountDetails,
        ];
    }

    /**
     * Get total unpaid invoices
     */
    private function getTotalUnpaidInvoices($user): array
    {
        $query = VendorInvoice::where('status', 'Unpaid');

        // Apply user access control
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }

        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        $unpaidInvoices = $query->get();
        $totalAmount = $unpaidInvoices->sum('total');
        $invoiceCount = $unpaidInvoices->count();

        // Get breakdown by type
        $expenseInvoices = $unpaidInvoices->where('type', 'Expense');
        $incomeInvoices = $unpaidInvoices->where('type', 'Income');

        return [
            'total_amount' => $totalAmount,
            'formatted_total_amount' => '$' . number_format($totalAmount, 2),
            'invoice_count' => $invoiceCount,
            'expense_count' => $expenseInvoices->count(),
            'expense_amount' => $expenseInvoices->sum('total'),
            'income_count' => $incomeInvoices->count(),
            'income_amount' => $incomeInvoices->sum('total'),
        ];
    }

    /**
     * Get total payments made in last 30 days
     */
    private function getTotalPaymentsLast30Days($user): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $query = VendorInvoice::where('status', 'Paid')
            ->where('payment_date', '>=', $thirtyDaysAgo);

        // Apply user access control
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }

        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        $paidInvoices = $query->get();
        $totalAmount = $paidInvoices->sum('total');
        $paymentCount = $paidInvoices->count();

        // Get breakdown by payment method
        $paymentMethods = $paidInvoices->groupBy('payment_method')->map(function ($payments) {
            return [
                'count' => $payments->count(),
                'total_amount' => $payments->sum('total'),
            ];
        });

        // Get breakdown by type
        $expensePayments = $paidInvoices->where('type', 'Expense');
        $incomePayments = $paidInvoices->where('type', 'Income');

        return [
            'total_amount' => $totalAmount,
            'formatted_total_amount' => '$' . number_format($totalAmount, 2),
            'payment_count' => $paymentCount,
            'payment_methods' => $paymentMethods,
            'expense_count' => $expensePayments->count(),
            'expense_amount' => $expensePayments->sum('total'),
            'income_count' => $incomePayments->count(),
            'income_amount' => $incomePayments->sum('total'),
            'period_start' => $thirtyDaysAgo->format('Y-m-d'),
            'period_end' => Carbon::now()->format('Y-m-d'),
        ];
    }

    /**
     * Get last 10 payments
     */
    private function getLastPayments($user, int $limit = 10): array
    {
        $query = VendorInvoice::with(['vendor', 'user', 'bankAccount'])
            ->where('status', 'Paid')
            ->whereNotNull('payment_date')
            ->orderBy('payment_date', 'desc')
            ->orderBy('updated_at', 'desc');

        // Apply user access control
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }

        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        $lastPayments = $query->limit($limit)->get();

        $payments = $lastPayments->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'vendor_name' => $invoice->vendor?->name,
                'amount' => $invoice->total,
                'formatted_amount' => '$' . number_format($invoice->total, 2),
                'payment_date' => $invoice->payment_date?->format('Y-m-d'),
                'formatted_payment_date' => $invoice->payment_date?->format('M j, Y'),
                'payment_method' => $invoice->payment_method,
                'type' => $invoice->type,
                'description' => $invoice->description,
                'bank_account' => $invoice->bankAccount ? [
                    'id' => $invoice->bankAccount->id,
                    'bank_name' => $invoice->bankAccount->bank_name,
                    'account_name' => $invoice->bankAccount->account_name,
                ] : null,
                'user_name' => $invoice->user?->name,
            ];
        });

        return [
            'payments' => $payments,
            'total_count' => $lastPayments->count(),
        ];
    }

    /**
     * Get total unpaid invoices with detailed list
     */
    private function getTotalUnpaidInvoicesWithDetails($user): array
    {
        $query = VendorInvoice::with(['vendor'])
            ->where('status', 'Unpaid')
            ->orderBy('invoice_date', 'desc');

        // Apply user access control
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }

        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        $unpaidInvoices = $query->get();
        $totalAmount = $unpaidInvoices->sum('total');
        $invoiceCount = $unpaidInvoices->count();

        $invoicesList = $unpaidInvoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'vendor_name' => $invoice->vendor?->name ?? 'N/A',
                'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                'formatted_date' => $invoice->invoice_date?->format('M j, Y'),
                'amount' => $invoice->total,
                'formatted_amount' => '$' . number_format($invoice->total, 2),
                'type' => $invoice->type,
            ];
        });

        return [
            'total_amount' => $totalAmount,
            'formatted_total_amount' => '$' . number_format($totalAmount, 2),
            'invoice_count' => $invoiceCount,
            'invoices' => $invoicesList,
        ];
    }

    /**
     * Get yesterday's sales and profit data
     */
    private function getYesterdayData($user): array
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        
        $dailySale = DailySale::whereDate('date', $yesterday)->first();
        $dailyFuels = DailyFuel::whereDate('date', $yesterday)->get();
        
        // Get the most recent fuel volume entry
        $latestFuelVolume = FuelVolume::orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$dailySale) {
            return [
                'has_data' => false,
                'date' => $yesterday,
                'formatted_date' => Carbon::yesterday()->format('M j, Y'),
            ];
        }

        // Calculate debit (only interac debit)
        $debitAmount = ($dailySale->pos_interac_debit ?? 0) + ($dailySale->afd_interac_debit ?? 0);
        
        // Calculate credit (visa, mastercard, amex, commercial, up_credit, discover)
        $creditAmount = ($dailySale->pos_visa ?? 0) + ($dailySale->afd_visa ?? 0) +
                       ($dailySale->pos_mastercard ?? 0) + ($dailySale->afd_mastercard ?? 0) +
                       ($dailySale->pos_amex ?? 0) + ($dailySale->afd_amex ?? 0) +
                       ($dailySale->pos_commercial ?? 0) + ($dailySale->afd_commercial ?? 0) +
                       ($dailySale->pos_up_credit ?? 0) + ($dailySale->afd_up_credit ?? 0) +
                       ($dailySale->pos_discover ?? 0) + ($dailySale->afd_discover ?? 0);

        // Calculate fuel data from daily_fuels table
        $fuelLiters = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_quantity ?? 0) + ($fuel->plus_quantity ?? 0) + 
                   ($fuel->sup_plus_quantity ?? 0) + ($fuel->diesel_quantity ?? 0);
        });
        $fuelAmount = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_total_sale ?? 0) + ($fuel->plus_total_sale ?? 0) + 
                   ($fuel->sup_plus_total_sale ?? 0) + ($fuel->diesel_total_sale ?? 0);
        });

        // Calculate profit breakdown
        $fuelProfitPercentage = config('profit.fuel_percentage', 4);
        $fuelSaleAmount = $dailySale->fuel_sale ?? 0;
        $fuelProfit = ($fuelSaleAmount * $fuelProfitPercentage) / 100;

        $tobacco25Percentage = config('profit.tobacco_25_percentage', 8);
        $tobacco25Amount = $dailySale->tobacco_25 ?? 0;
        $tobacco25Profit = ($tobacco25Amount * $tobacco25Percentage) / 100;

        $tobacco20Percentage = config('profit.tobacco_20_percentage', 8);
        $tobacco20Amount = $dailySale->tobacco_20 ?? 0;
        $tobacco20Profit = ($tobacco20Amount * $tobacco20Percentage) / 100;

        $lotteryPercentage = config('profit.lottery_percentage', 2);
        $lotteryAmount = $dailySale->lottery ?? 0;
        $lotteryProfit = ($lotteryAmount * $lotteryPercentage) / 100;

        $prepayPercentage = config('profit.prepay_percentage', 1);
        $prepayAmount = $dailySale->prepay ?? 0;
        $prepayProfit = ($prepayAmount * $prepayPercentage) / 100;

        $storeSalePercentage = config('profit.store_sale_percentage', 50);
        $storeSaleAmount = $dailySale->store_sale_calculated ?? 0;
        $storeSaleProfit = ($storeSaleAmount * $storeSalePercentage) / 100;

        return [
            'has_data' => true,
            'date' => $yesterday,
            'formatted_date' => Carbon::yesterday()->format('M j, Y'),
            'profit' => $dailySale->approximate_profit ?? 0,
            'formatted_profit' => '$' . number_format($dailySale->approximate_profit ?? 0, 2),
            'total_sale' => $dailySale->reported_total ?? 0,
            'formatted_total_sale' => '$' . number_format($dailySale->reported_total ?? 0, 2),
            'debit_sale' => $debitAmount,
            'formatted_debit_sale' => '$' . number_format($debitAmount, 2),
            'credit_sale' => $creditAmount,
            'formatted_credit_sale' => '$' . number_format($creditAmount, 2),
            'cash_sale' => $dailySale->cash ?? 0,
            'formatted_cash_sale' => '$' . number_format($dailySale->cash ?? 0, 2),
            'safedrops' => $dailySale->safedrops_amount ?? 0,
            'formatted_safedrops' => '$' . number_format($dailySale->safedrops_amount ?? 0, 2),
            'cash_in_hand' => $dailySale->cash_on_hand ?? 0,
            'formatted_cash_in_hand' => '$' . number_format($dailySale->cash_on_hand ?? 0, 2),
            'fuel_sale_liters' => $fuelLiters,
            'fuel_sale_amount' => $fuelAmount,
            'formatted_fuel_sale_amount' => '$' . number_format($fuelAmount, 2),
            'latest_fuel_volume' => $latestFuelVolume ? [
                'date' => $latestFuelVolume->date->format('Y-m-d'),
                'formatted_date' => $latestFuelVolume->date->format('M j, Y'),
                'regular' => $latestFuelVolume->regular_tc_volume ?? 0,
                'premium' => $latestFuelVolume->premium_tc_volume ?? 0,
                'diesel' => $latestFuelVolume->diesel_tc_volume ?? 0,
            ] : null,
            'profit_breakdown' => [
                'fuel' => [
                    'percentage' => $fuelProfitPercentage,
                    'amount' => $fuelSaleAmount,
                    'formatted_amount' => '$' . number_format($fuelSaleAmount, 2),
                    'profit' => $fuelProfit,
                    'formatted_profit' => '$' . number_format($fuelProfit, 2),
                ],
                'tobacco_25' => [
                    'percentage' => $tobacco25Percentage,
                    'amount' => $tobacco25Amount,
                    'formatted_amount' => '$' . number_format($tobacco25Amount, 2),
                    'profit' => $tobacco25Profit,
                    'formatted_profit' => '$' . number_format($tobacco25Profit, 2),
                ],
                'tobacco_20' => [
                    'percentage' => $tobacco20Percentage,
                    'amount' => $tobacco20Amount,
                    'formatted_amount' => '$' . number_format($tobacco20Amount, 2),
                    'profit' => $tobacco20Profit,
                    'formatted_profit' => '$' . number_format($tobacco20Profit, 2),
                ],
                'lottery' => [
                    'percentage' => $lotteryPercentage,
                    'amount' => $lotteryAmount,
                    'formatted_amount' => '$' . number_format($lotteryAmount, 2),
                    'profit' => $lotteryProfit,
                    'formatted_profit' => '$' . number_format($lotteryProfit, 2),
                ],
                'prepay' => [
                    'percentage' => $prepayPercentage,
                    'amount' => $prepayAmount,
                    'formatted_amount' => '$' . number_format($prepayAmount, 2),
                    'profit' => $prepayProfit,
                    'formatted_profit' => '$' . number_format($prepayProfit, 2),
                ],
                'store_sale' => [
                    'percentage' => $storeSalePercentage,
                    'amount' => $storeSaleAmount,
                    'formatted_amount' => '$' . number_format($storeSaleAmount, 2),
                    'profit' => $storeSaleProfit,
                    'formatted_profit' => '$' . number_format($storeSaleProfit, 2),
                ],
            ],
        ];
    }

    /**
     * Get last week's aggregated data
     */
    private function getLastWeekData($user): array
    {
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();
        
        $dailySales = DailySale::whereBetween('date', [$lastWeekStart, $lastWeekEnd])->get();
        $dailyFuels = DailyFuel::whereBetween('date', [$lastWeekStart, $lastWeekEnd])->get();

        if ($dailySales->isEmpty()) {
            return [
                'has_data' => false,
                'period_start' => $lastWeekStart->format('Y-m-d'),
                'period_end' => $lastWeekEnd->format('Y-m-d'),
                'formatted_period' => $lastWeekStart->format('M j') . ' - ' . $lastWeekEnd->format('M j, Y'),
            ];
        }

        $totalProfit = $dailySales->sum('approximate_profit');
        $totalSale = $dailySales->sum('reported_total');
        
        // Calculate debit (only interac debit)
        $totalDebit = $dailySales->sum(function ($sale) {
            return ($sale->pos_interac_debit ?? 0) + ($sale->afd_interac_debit ?? 0);
        });
        
        // Calculate credit (visa, mastercard, amex, commercial, up_credit, discover)
        $totalCredit = $dailySales->sum(function ($sale) {
            return ($sale->pos_visa ?? 0) + ($sale->afd_visa ?? 0) +
                   ($sale->pos_mastercard ?? 0) + ($sale->afd_mastercard ?? 0) +
                   ($sale->pos_amex ?? 0) + ($sale->afd_amex ?? 0) +
                   ($sale->pos_commercial ?? 0) + ($sale->afd_commercial ?? 0) +
                   ($sale->pos_up_credit ?? 0) + ($sale->afd_up_credit ?? 0) +
                   ($sale->pos_discover ?? 0) + ($sale->afd_discover ?? 0);
        });
        
        $totalCash = $dailySales->sum('cash');
        $totalSafedrops = $dailySales->sum('safedrops_amount');
        $totalCashInHand = $dailySales->sum('cash_on_hand');
        
        // Calculate fuel data from daily_fuels table
        $totalFuelLiters = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_quantity ?? 0) + ($fuel->plus_quantity ?? 0) + 
                   ($fuel->sup_plus_quantity ?? 0) + ($fuel->diesel_quantity ?? 0);
        });
        $totalFuelSaleAmount = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_total_sale ?? 0) + ($fuel->plus_total_sale ?? 0) + 
                   ($fuel->sup_plus_total_sale ?? 0) + ($fuel->diesel_total_sale ?? 0);
        });

        // Calculate profit breakdown for last week
        $fuelProfitPercentage = config('profit.fuel_percentage', 4);
        $totalFuelSale = $dailySales->sum('fuel_sale');
        $totalFuelProfit = ($totalFuelSale * $fuelProfitPercentage) / 100;

        $tobacco25Percentage = config('profit.tobacco_25_percentage', 8);
        $totalTobacco25 = $dailySales->sum('tobacco_25');
        $totalTobacco25Profit = ($totalTobacco25 * $tobacco25Percentage) / 100;

        $tobacco20Percentage = config('profit.tobacco_20_percentage', 8);
        $totalTobacco20 = $dailySales->sum('tobacco_20');
        $totalTobacco20Profit = ($totalTobacco20 * $tobacco20Percentage) / 100;

        $lotteryPercentage = config('profit.lottery_percentage', 2);
        $totalLottery = $dailySales->sum('lottery');
        $totalLotteryProfit = ($totalLottery * $lotteryPercentage) / 100;

        $prepayPercentage = config('profit.prepay_percentage', 1);
        $totalPrepay = $dailySales->sum('prepay');
        $totalPrepayProfit = ($totalPrepay * $prepayPercentage) / 100;

        $storeSalePercentage = config('profit.store_sale_percentage', 50);
        $totalStoreSaleCalculated = $dailySales->sum('store_sale_calculated');
        $totalStoreSaleProfit = ($totalStoreSaleCalculated * $storeSalePercentage) / 100;

        return [
            'has_data' => true,
            'period_start' => $lastWeekStart->format('Y-m-d'),
            'period_end' => $lastWeekEnd->format('Y-m-d'),
            'formatted_period' => $lastWeekStart->format('M j') . ' - ' . $lastWeekEnd->format('M j, Y'),
            'profit' => $totalProfit,
            'formatted_profit' => '$' . number_format($totalProfit, 2),
            'total_sale' => $totalSale,
            'formatted_total_sale' => '$' . number_format($totalSale, 2),
            'debit_sale' => $totalDebit,
            'formatted_debit_sale' => '$' . number_format($totalDebit, 2),
            'credit_sale' => $totalCredit,
            'formatted_credit_sale' => '$' . number_format($totalCredit, 2),
            'cash_sale' => $totalCash,
            'formatted_cash_sale' => '$' . number_format($totalCash, 2),
            'safedrops' => $totalSafedrops,
            'formatted_safedrops' => '$' . number_format($totalSafedrops, 2),
            'cash_in_hand' => $totalCashInHand,
            'formatted_cash_in_hand' => '$' . number_format($totalCashInHand, 2),
            'fuel_sale_liters' => $totalFuelLiters,
            'fuel_sale_amount' => $totalFuelSaleAmount,
            'formatted_fuel_sale_amount' => '$' . number_format($totalFuelSaleAmount, 2),
            'profit_breakdown' => [
                'fuel' => [
                    'percentage' => $fuelProfitPercentage,
                    'amount' => $totalFuelSale,
                    'formatted_amount' => '$' . number_format($totalFuelSale, 2),
                    'profit' => $totalFuelProfit,
                    'formatted_profit' => '$' . number_format($totalFuelProfit, 2),
                ],
                'tobacco_25' => [
                    'percentage' => $tobacco25Percentage,
                    'amount' => $totalTobacco25,
                    'formatted_amount' => '$' . number_format($totalTobacco25, 2),
                    'profit' => $totalTobacco25Profit,
                    'formatted_profit' => '$' . number_format($totalTobacco25Profit, 2),
                ],
                'tobacco_20' => [
                    'percentage' => $tobacco20Percentage,
                    'amount' => $totalTobacco20,
                    'formatted_amount' => '$' . number_format($totalTobacco20, 2),
                    'profit' => $totalTobacco20Profit,
                    'formatted_profit' => '$' . number_format($totalTobacco20Profit, 2),
                ],
                'lottery' => [
                    'percentage' => $lotteryPercentage,
                    'amount' => $totalLottery,
                    'formatted_amount' => '$' . number_format($totalLottery, 2),
                    'profit' => $totalLotteryProfit,
                    'formatted_profit' => '$' . number_format($totalLotteryProfit, 2),
                ],
                'prepay' => [
                    'percentage' => $prepayPercentage,
                    'amount' => $totalPrepay,
                    'formatted_amount' => '$' . number_format($totalPrepay, 2),
                    'profit' => $totalPrepayProfit,
                    'formatted_profit' => '$' . number_format($totalPrepayProfit, 2),
                ],
                'store_sale' => [
                    'percentage' => $storeSalePercentage,
                    'amount' => $totalStoreSaleCalculated,
                    'formatted_amount' => '$' . number_format($totalStoreSaleCalculated, 2),
                    'profit' => $totalStoreSaleProfit,
                    'formatted_profit' => '$' . number_format($totalStoreSaleProfit, 2),
                ],
            ],
        ];
    }

    /**
     * Get current week's aggregated data
     */
    private function getCurrentWeekData($user): array
    {
        $currentWeekStart = Carbon::now()->startOfWeek();
        $currentWeekEnd = Carbon::now()->endOfWeek();
        
        $dailySales = DailySale::whereBetween('date', [$currentWeekStart, $currentWeekEnd])->get();
        $dailyFuels = DailyFuel::whereBetween('date', [$currentWeekStart, $currentWeekEnd])->get();

        if ($dailySales->isEmpty()) {
            return [
                'has_data' => false,
                'period_start' => $currentWeekStart->format('Y-m-d'),
                'period_end' => $currentWeekEnd->format('Y-m-d'),
                'formatted_period' => $currentWeekStart->format('M j') . ' - ' . $currentWeekEnd->format('M j, Y'),
            ];
        }

        $totalProfit = $dailySales->sum('approximate_profit');
        $totalSale = $dailySales->sum('reported_total');
        
        // Calculate debit (only interac debit)
        $totalDebit = $dailySales->sum(function ($sale) {
            return ($sale->pos_interac_debit ?? 0) + ($sale->afd_interac_debit ?? 0);
        });
        
        // Calculate credit (visa, mastercard, amex, commercial, up_credit, discover)
        $totalCredit = $dailySales->sum(function ($sale) {
            return ($sale->pos_visa ?? 0) + ($sale->afd_visa ?? 0) +
                   ($sale->pos_mastercard ?? 0) + ($sale->afd_mastercard ?? 0) +
                   ($sale->pos_amex ?? 0) + ($sale->afd_amex ?? 0) +
                   ($sale->pos_commercial ?? 0) + ($sale->afd_commercial ?? 0) +
                   ($sale->pos_up_credit ?? 0) + ($sale->afd_up_credit ?? 0) +
                   ($sale->pos_discover ?? 0) + ($sale->afd_discover ?? 0);
        });
        
        $totalCash = $dailySales->sum('cash');
        $totalSafedrops = $dailySales->sum('safedrops_amount');
        $totalCashInHand = $dailySales->sum('cash_on_hand');
        
        // Calculate fuel data from daily_fuels table
        $totalFuelLiters = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_quantity ?? 0) + ($fuel->plus_quantity ?? 0) + 
                   ($fuel->sup_plus_quantity ?? 0) + ($fuel->diesel_quantity ?? 0);
        });
        $totalFuelSaleAmount = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_total_sale ?? 0) + ($fuel->plus_total_sale ?? 0) + 
                   ($fuel->sup_plus_total_sale ?? 0) + ($fuel->diesel_total_sale ?? 0);
        });

        // Calculate profit breakdown for current week
        $fuelProfitPercentage = config('profit.fuel_percentage', 4);
        $totalFuelSale = $dailySales->sum('fuel_sale');
        $totalFuelProfit = ($totalFuelSale * $fuelProfitPercentage) / 100;

        $tobacco25Percentage = config('profit.tobacco_25_percentage', 8);
        $totalTobacco25 = $dailySales->sum('tobacco_25');
        $totalTobacco25Profit = ($totalTobacco25 * $tobacco25Percentage) / 100;

        $tobacco20Percentage = config('profit.tobacco_20_percentage', 8);
        $totalTobacco20 = $dailySales->sum('tobacco_20');
        $totalTobacco20Profit = ($totalTobacco20 * $tobacco20Percentage) / 100;

        $lotteryPercentage = config('profit.lottery_percentage', 2);
        $totalLottery = $dailySales->sum('lottery');
        $totalLotteryProfit = ($totalLottery * $lotteryPercentage) / 100;

        $prepayPercentage = config('profit.prepay_percentage', 1);
        $totalPrepay = $dailySales->sum('prepay');
        $totalPrepayProfit = ($totalPrepay * $prepayPercentage) / 100;

        $storeSalePercentage = config('profit.store_sale_percentage', 50);
        $totalStoreSaleCalculated = $dailySales->sum('store_sale_calculated');
        $totalStoreSaleProfit = ($totalStoreSaleCalculated * $storeSalePercentage) / 100;

        return [
            'has_data' => true,
            'period_start' => $currentWeekStart->format('Y-m-d'),
            'period_end' => $currentWeekEnd->format('Y-m-d'),
            'formatted_period' => $currentWeekStart->format('M j') . ' - ' . $currentWeekEnd->format('M j, Y'),
            'profit' => $totalProfit,
            'formatted_profit' => '$' . number_format($totalProfit, 2),
            'total_sale' => $totalSale,
            'formatted_total_sale' => '$' . number_format($totalSale, 2),
            'debit_sale' => $totalDebit,
            'formatted_debit_sale' => '$' . number_format($totalDebit, 2),
            'credit_sale' => $totalCredit,
            'formatted_credit_sale' => '$' . number_format($totalCredit, 2),
            'cash_sale' => $totalCash,
            'formatted_cash_sale' => '$' . number_format($totalCash, 2),
            'safedrops' => $totalSafedrops,
            'formatted_safedrops' => '$' . number_format($totalSafedrops, 2),
            'cash_in_hand' => $totalCashInHand,
            'formatted_cash_in_hand' => '$' . number_format($totalCashInHand, 2),
            'fuel_sale_liters' => $totalFuelLiters,
            'fuel_sale_amount' => $totalFuelSaleAmount,
            'formatted_fuel_sale_amount' => '$' . number_format($totalFuelSaleAmount, 2),
            'profit_breakdown' => [
                'fuel' => [
                    'percentage' => $fuelProfitPercentage,
                    'amount' => $totalFuelSale,
                    'formatted_amount' => '$' . number_format($totalFuelSale, 2),
                    'profit' => $totalFuelProfit,
                    'formatted_profit' => '$' . number_format($totalFuelProfit, 2),
                ],
                'tobacco_25' => [
                    'percentage' => $tobacco25Percentage,
                    'amount' => $totalTobacco25,
                    'formatted_amount' => '$' . number_format($totalTobacco25, 2),
                    'profit' => $totalTobacco25Profit,
                    'formatted_profit' => '$' . number_format($totalTobacco25Profit, 2),
                ],
                'tobacco_20' => [
                    'percentage' => $tobacco20Percentage,
                    'amount' => $totalTobacco20,
                    'formatted_amount' => '$' . number_format($totalTobacco20, 2),
                    'profit' => $totalTobacco20Profit,
                    'formatted_profit' => '$' . number_format($totalTobacco20Profit, 2),
                ],
                'lottery' => [
                    'percentage' => $lotteryPercentage,
                    'amount' => $totalLottery,
                    'formatted_amount' => '$' . number_format($totalLottery, 2),
                    'profit' => $totalLotteryProfit,
                    'formatted_profit' => '$' . number_format($totalLotteryProfit, 2),
                ],
                'prepay' => [
                    'percentage' => $prepayPercentage,
                    'amount' => $totalPrepay,
                    'formatted_amount' => '$' . number_format($totalPrepay, 2),
                    'profit' => $totalPrepayProfit,
                    'formatted_profit' => '$' . number_format($totalPrepayProfit, 2),
                ],
                'store_sale' => [
                    'percentage' => $storeSalePercentage,
                    'amount' => $totalStoreSaleCalculated,
                    'formatted_amount' => '$' . number_format($totalStoreSaleCalculated, 2),
                    'profit' => $totalStoreSaleProfit,
                    'formatted_profit' => '$' . number_format($totalStoreSaleProfit, 2),
                ],
            ],
        ];
    }

    /**
     * Get last month's aggregated data
     */
    private function getLastMonthData($user): array
    {
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        
        $dailySales = DailySale::whereBetween('date', [$lastMonthStart, $lastMonthEnd])->get();
        $dailyFuels = DailyFuel::whereBetween('date', [$lastMonthStart, $lastMonthEnd])->get();

        if ($dailySales->isEmpty()) {
            return [
                'has_data' => false,
                'period_start' => $lastMonthStart->format('Y-m-d'),
                'period_end' => $lastMonthEnd->format('Y-m-d'),
                'formatted_period' => $lastMonthStart->format('F Y'),
            ];
        }

        $totalProfit = $dailySales->sum('approximate_profit');
        $totalSale = $dailySales->sum('reported_total');
        
        // Calculate debit (only interac debit)
        $totalDebit = $dailySales->sum(function ($sale) {
            return ($sale->pos_interac_debit ?? 0) + ($sale->afd_interac_debit ?? 0);
        });
        
        // Calculate credit (visa, mastercard, amex, commercial, up_credit, discover)
        $totalCredit = $dailySales->sum(function ($sale) {
            return ($sale->pos_visa ?? 0) + ($sale->afd_visa ?? 0) +
                   ($sale->pos_mastercard ?? 0) + ($sale->afd_mastercard ?? 0) +
                   ($sale->pos_amex ?? 0) + ($sale->afd_amex ?? 0) +
                   ($sale->pos_commercial ?? 0) + ($sale->afd_commercial ?? 0) +
                   ($sale->pos_up_credit ?? 0) + ($sale->afd_up_credit ?? 0) +
                   ($sale->pos_discover ?? 0) + ($sale->afd_discover ?? 0);
        });
        
        $totalCash = $dailySales->sum('cash');
        $totalSafedrops = $dailySales->sum('safedrops_amount');
        $totalCashInHand = $dailySales->sum('cash_on_hand');
        
        // Calculate fuel data from daily_fuels table
        $totalFuelLiters = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_quantity ?? 0) + ($fuel->plus_quantity ?? 0) + 
                   ($fuel->sup_plus_quantity ?? 0) + ($fuel->diesel_quantity ?? 0);
        });
        $totalFuelSaleAmount = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_total_sale ?? 0) + ($fuel->plus_total_sale ?? 0) + 
                   ($fuel->sup_plus_total_sale ?? 0) + ($fuel->diesel_total_sale ?? 0);
        });

        // Calculate profit breakdown for last month
        $fuelProfitPercentage = config('profit.fuel_percentage', 4);
        $totalFuelSale = $dailySales->sum('fuel_sale');
        $totalFuelProfit = ($totalFuelSale * $fuelProfitPercentage) / 100;

        $tobacco25Percentage = config('profit.tobacco_25_percentage', 8);
        $totalTobacco25 = $dailySales->sum('tobacco_25');
        $totalTobacco25Profit = ($totalTobacco25 * $tobacco25Percentage) / 100;

        $tobacco20Percentage = config('profit.tobacco_20_percentage', 8);
        $totalTobacco20 = $dailySales->sum('tobacco_20');
        $totalTobacco20Profit = ($totalTobacco20 * $tobacco20Percentage) / 100;

        $lotteryPercentage = config('profit.lottery_percentage', 2);
        $totalLottery = $dailySales->sum('lottery');
        $totalLotteryProfit = ($totalLottery * $lotteryPercentage) / 100;

        $prepayPercentage = config('profit.prepay_percentage', 1);
        $totalPrepay = $dailySales->sum('prepay');
        $totalPrepayProfit = ($totalPrepay * $prepayPercentage) / 100;

        $storeSalePercentage = config('profit.store_sale_percentage', 50);
        $totalStoreSaleCalculated = $dailySales->sum('store_sale_calculated');
        $totalStoreSaleProfit = ($totalStoreSaleCalculated * $storeSalePercentage) / 100;

        return [
            'has_data' => true,
            'period_start' => $lastMonthStart->format('Y-m-d'),
            'period_end' => $lastMonthEnd->format('Y-m-d'),
            'formatted_period' => $lastMonthStart->format('F Y'),
            'profit' => $totalProfit,
            'formatted_profit' => '$' . number_format($totalProfit, 2),
            'total_sale' => $totalSale,
            'formatted_total_sale' => '$' . number_format($totalSale, 2),
            'debit_sale' => $totalDebit,
            'formatted_debit_sale' => '$' . number_format($totalDebit, 2),
            'credit_sale' => $totalCredit,
            'formatted_credit_sale' => '$' . number_format($totalCredit, 2),
            'cash_sale' => $totalCash,
            'formatted_cash_sale' => '$' . number_format($totalCash, 2),
            'safedrops' => $totalSafedrops,
            'formatted_safedrops' => '$' . number_format($totalSafedrops, 2),
            'cash_in_hand' => $totalCashInHand,
            'formatted_cash_in_hand' => '$' . number_format($totalCashInHand, 2),
            'fuel_sale_liters' => $totalFuelLiters,
            'fuel_sale_amount' => $totalFuelSaleAmount,
            'formatted_fuel_sale_amount' => '$' . number_format($totalFuelSaleAmount, 2),
            'profit_breakdown' => [
                'fuel' => [
                    'percentage' => $fuelProfitPercentage,
                    'amount' => $totalFuelSale,
                    'formatted_amount' => '$' . number_format($totalFuelSale, 2),
                    'profit' => $totalFuelProfit,
                    'formatted_profit' => '$' . number_format($totalFuelProfit, 2),
                ],
                'tobacco_25' => [
                    'percentage' => $tobacco25Percentage,
                    'amount' => $totalTobacco25,
                    'formatted_amount' => '$' . number_format($totalTobacco25, 2),
                    'profit' => $totalTobacco25Profit,
                    'formatted_profit' => '$' . number_format($totalTobacco25Profit, 2),
                ],
                'tobacco_20' => [
                    'percentage' => $tobacco20Percentage,
                    'amount' => $totalTobacco20,
                    'formatted_amount' => '$' . number_format($totalTobacco20, 2),
                    'profit' => $totalTobacco20Profit,
                    'formatted_profit' => '$' . number_format($totalTobacco20Profit, 2),
                ],
                'lottery' => [
                    'percentage' => $lotteryPercentage,
                    'amount' => $totalLottery,
                    'formatted_amount' => '$' . number_format($totalLottery, 2),
                    'profit' => $totalLotteryProfit,
                    'formatted_profit' => '$' . number_format($totalLotteryProfit, 2),
                ],
                'prepay' => [
                    'percentage' => $prepayPercentage,
                    'amount' => $totalPrepay,
                    'formatted_amount' => '$' . number_format($totalPrepay, 2),
                    'profit' => $totalPrepayProfit,
                    'formatted_profit' => '$' . number_format($totalPrepayProfit, 2),
                ],
                'store_sale' => [
                    'percentage' => $storeSalePercentage,
                    'amount' => $totalStoreSaleCalculated,
                    'formatted_amount' => '$' . number_format($totalStoreSaleCalculated, 2),
                    'profit' => $totalStoreSaleProfit,
                    'formatted_profit' => '$' . number_format($totalStoreSaleProfit, 2),
                ],
            ],
        ];
    }

    /**
     * Get current month's aggregated data
     */
    private function getCurrentMonthData($user): array
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        
        $dailySales = DailySale::whereBetween('date', [$currentMonthStart, $currentMonthEnd])->get();
        $dailyFuels = DailyFuel::whereBetween('date', [$currentMonthStart, $currentMonthEnd])->get();

        if ($dailySales->isEmpty()) {
            return [
                'has_data' => false,
                'period_start' => $currentMonthStart->format('Y-m-d'),
                'period_end' => $currentMonthEnd->format('Y-m-d'),
                'formatted_period' => $currentMonthStart->format('F Y'),
            ];
        }

        $totalProfit = $dailySales->sum('approximate_profit');
        $totalSale = $dailySales->sum('reported_total');
        
        // Calculate debit (only interac debit)
        $totalDebit = $dailySales->sum(function ($sale) {
            return ($sale->pos_interac_debit ?? 0) + ($sale->afd_interac_debit ?? 0);
        });
        
        // Calculate credit (visa, mastercard, amex, commercial, up_credit, discover)
        $totalCredit = $dailySales->sum(function ($sale) {
            return ($sale->pos_visa ?? 0) + ($sale->afd_visa ?? 0) +
                   ($sale->pos_mastercard ?? 0) + ($sale->afd_mastercard ?? 0) +
                   ($sale->pos_amex ?? 0) + ($sale->afd_amex ?? 0) +
                   ($sale->pos_commercial ?? 0) + ($sale->afd_commercial ?? 0) +
                   ($sale->pos_up_credit ?? 0) + ($sale->afd_up_credit ?? 0) +
                   ($sale->pos_discover ?? 0) + ($sale->afd_discover ?? 0);
        });
        
        $totalCash = $dailySales->sum('cash');
        $totalSafedrops = $dailySales->sum('safedrops_amount');
        $totalCashInHand = $dailySales->sum('cash_on_hand');
        
        // Calculate fuel data from daily_fuels table
        $totalFuelLiters = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_quantity ?? 0) + ($fuel->plus_quantity ?? 0) + 
                   ($fuel->sup_plus_quantity ?? 0) + ($fuel->diesel_quantity ?? 0);
        });
        $totalFuelSaleAmount = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_total_sale ?? 0) + ($fuel->plus_total_sale ?? 0) + 
                   ($fuel->sup_plus_total_sale ?? 0) + ($fuel->diesel_total_sale ?? 0);
        });

        // Calculate profit breakdown for current month
        $fuelProfitPercentage = config('profit.fuel_percentage', 4);
        $totalFuelSale = $dailySales->sum('fuel_sale');
        $totalFuelProfit = ($totalFuelSale * $fuelProfitPercentage) / 100;

        $tobacco25Percentage = config('profit.tobacco_25_percentage', 8);
        $totalTobacco25 = $dailySales->sum('tobacco_25');
        $totalTobacco25Profit = ($totalTobacco25 * $tobacco25Percentage) / 100;

        $tobacco20Percentage = config('profit.tobacco_20_percentage', 8);
        $totalTobacco20 = $dailySales->sum('tobacco_20');
        $totalTobacco20Profit = ($totalTobacco20 * $tobacco20Percentage) / 100;

        $lotteryPercentage = config('profit.lottery_percentage', 2);
        $totalLottery = $dailySales->sum('lottery');
        $totalLotteryProfit = ($totalLottery * $lotteryPercentage) / 100;

        $prepayPercentage = config('profit.prepay_percentage', 1);
        $totalPrepay = $dailySales->sum('prepay');
        $totalPrepayProfit = ($totalPrepay * $prepayPercentage) / 100;

        $storeSalePercentage = config('profit.store_sale_percentage', 50);
        $totalStoreSaleCalculated = $dailySales->sum('store_sale_calculated');
        $totalStoreSaleProfit = ($totalStoreSaleCalculated * $storeSalePercentage) / 100;

        return [
            'has_data' => true,
            'period_start' => $currentMonthStart->format('Y-m-d'),
            'period_end' => $currentMonthEnd->format('Y-m-d'),
            'formatted_period' => $currentMonthStart->format('F Y'),
            'profit' => $totalProfit,
            'formatted_profit' => '$' . number_format($totalProfit, 2),
            'total_sale' => $totalSale,
            'formatted_total_sale' => '$' . number_format($totalSale, 2),
            'debit_sale' => $totalDebit,
            'formatted_debit_sale' => '$' . number_format($totalDebit, 2),
            'credit_sale' => $totalCredit,
            'formatted_credit_sale' => '$' . number_format($totalCredit, 2),
            'cash_sale' => $totalCash,
            'formatted_cash_sale' => '$' . number_format($totalCash, 2),
            'safedrops' => $totalSafedrops,
            'formatted_safedrops' => '$' . number_format($totalSafedrops, 2),
            'cash_in_hand' => $totalCashInHand,
            'formatted_cash_in_hand' => '$' . number_format($totalCashInHand, 2),
            'fuel_sale_liters' => $totalFuelLiters,
            'fuel_sale_amount' => $totalFuelSaleAmount,
            'formatted_fuel_sale_amount' => '$' . number_format($totalFuelSaleAmount, 2),
            'profit_breakdown' => [
                'fuel' => [
                    'percentage' => $fuelProfitPercentage,
                    'amount' => $totalFuelSale,
                    'formatted_amount' => '$' . number_format($totalFuelSale, 2),
                    'profit' => $totalFuelProfit,
                    'formatted_profit' => '$' . number_format($totalFuelProfit, 2),
                ],
                'tobacco_25' => [
                    'percentage' => $tobacco25Percentage,
                    'amount' => $totalTobacco25,
                    'formatted_amount' => '$' . number_format($totalTobacco25, 2),
                    'profit' => $totalTobacco25Profit,
                    'formatted_profit' => '$' . number_format($totalTobacco25Profit, 2),
                ],
                'tobacco_20' => [
                    'percentage' => $tobacco20Percentage,
                    'amount' => $totalTobacco20,
                    'formatted_amount' => '$' . number_format($totalTobacco20, 2),
                    'profit' => $totalTobacco20Profit,
                    'formatted_profit' => '$' . number_format($totalTobacco20Profit, 2),
                ],
                'lottery' => [
                    'percentage' => $lotteryPercentage,
                    'amount' => $totalLottery,
                    'formatted_amount' => '$' . number_format($totalLottery, 2),
                    'profit' => $totalLotteryProfit,
                    'formatted_profit' => '$' . number_format($totalLotteryProfit, 2),
                ],
                'prepay' => [
                    'percentage' => $prepayPercentage,
                    'amount' => $totalPrepay,
                    'formatted_amount' => '$' . number_format($totalPrepay, 2),
                    'profit' => $totalPrepayProfit,
                    'formatted_profit' => '$' . number_format($totalPrepayProfit, 2),
                ],
                'store_sale' => [
                    'percentage' => $storeSalePercentage,
                    'amount' => $totalStoreSaleCalculated,
                    'formatted_amount' => '$' . number_format($totalStoreSaleCalculated, 2),
                    'profit' => $totalStoreSaleProfit,
                    'formatted_profit' => '$' . number_format($totalStoreSaleProfit, 2),
                ],
            ],
        ];
    }

    /**
     * Get last month's income with details
     */
    private function getLastMonthIncome($user): array
    {
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $query = VendorInvoice::with(['vendor'])
            ->where('type', 'Income')
            ->where('status', 'Paid')
            ->whereBetween('payment_date', [$lastMonthStart, $lastMonthEnd])
            ->orderBy('payment_date', 'desc');

        // Apply user access control
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }

        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        $incomes = $query->get();
        $totalIncome = $incomes->sum('total');

        $incomeList = $incomes->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'vendor_name' => $invoice->vendor?->name ?? 'N/A',
                'payment_date' => $invoice->payment_date?->format('Y-m-d'),
                'formatted_date' => $invoice->payment_date?->format('M j, Y'),
                'amount' => $invoice->total,
                'formatted_amount' => '$' . number_format($invoice->total, 2),
                'description' => $invoice->description,
            ];
        });

        return [
            'total_income' => $totalIncome,
            'formatted_total_income' => '$' . number_format($totalIncome, 2),
            'income_count' => $incomes->count(),
            'period_start' => $lastMonthStart->format('Y-m-d'),
            'period_end' => $lastMonthEnd->format('Y-m-d'),
            'formatted_period' => $lastMonthStart->format('F Y'),
            'incomes' => $incomeList,
        ];
    }

    /**
     * Get last month's expenses with details
     */
    private function getLastMonthExpenses($user): array
    {
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $query = VendorInvoice::with(['vendor'])
            ->where('type', 'Expense')
            ->where('status', 'Paid')
            ->whereBetween('payment_date', [$lastMonthStart, $lastMonthEnd])
            ->orderBy('payment_date', 'desc');

        // Apply user access control
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }

        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        $expenses = $query->get();
        $totalExpense = $expenses->sum('total');

        $expenseList = $expenses->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'vendor_name' => $invoice->vendor?->name ?? 'N/A',
                'payment_date' => $invoice->payment_date?->format('Y-m-d'),
                'formatted_date' => $invoice->payment_date?->format('M j, Y'),
                'amount' => $invoice->total,
                'formatted_amount' => '$' . number_format($invoice->total, 2),
                'description' => $invoice->description,
            ];
        });

        return [
            'total_expense' => $totalExpense,
            'formatted_total_expense' => '$' . number_format($totalExpense, 2),
            'expense_count' => $expenses->count(),
            'period_start' => $lastMonthStart->format('Y-m-d'),
            'period_end' => $lastMonthEnd->format('Y-m-d'),
            'formatted_period' => $lastMonthStart->format('F Y'),
            'expenses' => $expenseList,
        ];
    }
}
