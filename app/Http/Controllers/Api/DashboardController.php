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

        // Last month's income
        $lastMonthIncome = $this->getLastMonthIncome($user);

        // Last month's expenses
        $lastMonthExpenses = $this->getLastMonthExpenses($user);

        return response()->json([
            'total_money_in_banks' => $totalMoneyInBanks,
            'total_unpaid_invoices' => $totalUnpaidInvoices,
            'yesterday_data' => $yesterdayData,
            'last_week_data' => $lastWeekData,
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
        $dailyFuel = DailyFuel::whereDate('date', $yesterday)->first();
        $fuelVolume = FuelVolume::whereDate('date', $yesterday)->first();

        if (!$dailySale) {
            return [
                'has_data' => false,
                'date' => $yesterday,
                'formatted_date' => Carbon::yesterday()->format('M j, Y'),
            ];
        }

        return [
            'has_data' => true,
            'date' => $yesterday,
            'formatted_date' => Carbon::yesterday()->format('M j, Y'),
            'profit' => $dailySale->profit ?? 0,
            'formatted_profit' => '$' . number_format($dailySale->profit ?? 0, 2),
            'total_sale' => $dailySale->reported_total ?? 0,
            'formatted_total_sale' => '$' . number_format($dailySale->reported_total ?? 0, 2),
            'debit_sale' => $dailySale->card ?? 0,
            'formatted_debit_sale' => '$' . number_format($dailySale->card ?? 0, 2),
            'credit_sale' => $dailySale->card ?? 0,
            'formatted_credit_sale' => '$' . number_format($dailySale->card ?? 0, 2),
            'cash_sale' => $dailySale->cash ?? 0,
            'formatted_cash_sale' => '$' . number_format($dailySale->cash ?? 0, 2),
            'safedrops' => $dailySale->safedrops ?? 0,
            'formatted_safedrops' => '$' . number_format($dailySale->safedrops ?? 0, 2),
            'cash_in_hand' => $dailySale->cash_in_hand ?? 0,
            'formatted_cash_in_hand' => '$' . number_format($dailySale->cash_in_hand ?? 0, 2),
            'fuel_sale_liters' => $dailyFuel ? (
                ($dailyFuel->regular_total ?? 0) +
                ($dailyFuel->plus_total ?? 0) +
                ($dailyFuel->sup_plus_total ?? 0) +
                ($dailyFuel->diesel_total ?? 0)
            ) : 0,
            'fuel_sale_amount' => $dailySale->fuel_sale ?? 0,
            'formatted_fuel_sale_amount' => '$' . number_format($dailySale->fuel_sale ?? 0, 2),
            'latest_fuel_volume' => $fuelVolume ? [
                'regular' => $fuelVolume->regular ?? 0,
                'plus' => $fuelVolume->plus ?? 0,
                'sup_plus' => $fuelVolume->sup_plus ?? 0,
                'diesel' => $fuelVolume->diesel ?? 0,
            ] : null,
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

        $totalProfit = $dailySales->sum('profit');
        $totalSale = $dailySales->sum('reported_total');
        $totalDebit = $dailySales->sum('card');
        $totalCash = $dailySales->sum('cash');
        $totalSafedrops = $dailySales->sum('safedrops');
        $totalCashInHand = $dailySales->sum('cash_in_hand');
        $totalFuelSaleAmount = $dailySales->sum('fuel_sale');
        
        $totalFuelLiters = $dailyFuels->sum(function ($fuel) {
            return ($fuel->regular_total ?? 0) +
                   ($fuel->plus_total ?? 0) +
                   ($fuel->sup_plus_total ?? 0) +
                   ($fuel->diesel_total ?? 0);
        });

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
            'credit_sale' => $totalDebit,
            'formatted_credit_sale' => '$' . number_format($totalDebit, 2),
            'cash_sale' => $totalCash,
            'formatted_cash_sale' => '$' . number_format($totalCash, 2),
            'safedrops' => $totalSafedrops,
            'formatted_safedrops' => '$' . number_format($totalSafedrops, 2),
            'cash_in_hand' => $totalCashInHand,
            'formatted_cash_in_hand' => '$' . number_format($totalCashInHand, 2),
            'fuel_sale_liters' => $totalFuelLiters,
            'fuel_sale_amount' => $totalFuelSaleAmount,
            'formatted_fuel_sale_amount' => '$' . number_format($totalFuelSaleAmount, 2),
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
