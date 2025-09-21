<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\VendorInvoice;
use App\Models\Transaction;
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

        // Total unpaid invoices
        $totalUnpaidInvoices = $this->getTotalUnpaidInvoices($user);

        // Total payments made in last 30 days
        $totalPaymentsLast30Days = $this->getTotalPaymentsLast30Days($user);

        // Last 10 payments
        $lastPayments = $this->getLastPayments($user);

        return response()->json([
            'total_money_in_banks' => $totalMoneyInBanks,
            'total_unpaid_invoices' => $totalUnpaidInvoices,
            'total_payments_last_30_days' => $totalPaymentsLast30Days,
            'last_payments' => $lastPayments,
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
}
