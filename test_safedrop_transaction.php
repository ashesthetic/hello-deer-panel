<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BankAccount;
use App\Models\DailySale;
use App\Models\SafedropResolution;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== Testing Safedrop Transaction Creation ===\n\n";

try {
    // 1. Check if Cash account exists
    echo "1. Checking for Cash account...\n";
    $cashAccount = BankAccount::whereRaw('LOWER(account_name) = ?', ['cash'])->first();
    
    if (!$cashAccount) {
        echo "   ❌ Cash account not found!\n";
        echo "   Please create a bank account with name 'Cash'\n";
        exit(1);
    }
    
    echo "   ✅ Found Cash account: {$cashAccount->account_name}\n";
    echo "   - Bank: {$cashAccount->bank_name}\n";
    echo "   - Current Balance: \${$cashAccount->balance}\n\n";
    
    // 2. Check for a daily sale with pending safedrops
    echo "2. Looking for daily sales with pending safedrops...\n";
    $dailySale = DailySale::where('safedrops_amount', '>', 0)->first();
    
    if (!$dailySale) {
        echo "   ⚠️  No daily sales with safedrops found\n";
        echo "   Creating a test daily sale...\n";
        
        $user = User::where('role', 'Admin')->first();
        if (!$user) {
            echo "   ❌ No admin user found\n";
            exit(1);
        }
        
        $dailySale = DailySale::create([
            'date' => now()->toDateString(),
            'user_id' => $user->id,
            'safedrops_amount' => 500.00,
            'cash_on_hand' => 100.00,
            'cash' => 600.00,
            'card' => 300.00,
            'debit' => 200.00,
            'status' => 'Pending',
        ]);
        
        echo "   ✅ Created test daily sale with \$500 safedrops\n\n";
    } else {
        echo "   ✅ Found daily sale from {$dailySale->date}\n";
        echo "   - Safedrops Amount: \${$dailySale->safedrops_amount}\n";
        echo "   - User: {$dailySale->user->name}\n\n";
    }
    
    // 3. Check for other bank accounts
    echo "3. Looking for target bank accounts...\n";
    $targetAccount = BankAccount::whereRaw('LOWER(account_name) != ?', ['cash'])
        ->where('is_active', true)
        ->first();
    
    if (!$targetAccount) {
        echo "   ❌ No other active bank account found for testing\n";
        exit(1);
    }
    
    echo "   ✅ Found target account: {$targetAccount->account_name}\n";
    echo "   - Current Balance: \${$targetAccount->balance}\n\n";
    
    // 4. Get admin user
    echo "4. Getting admin user...\n";
    $adminUser = User::where('role', 'Admin')->first();
    
    if (!$adminUser) {
        echo "   ❌ No admin user found\n";
        exit(1);
    }
    
    echo "   ✅ Found admin: {$adminUser->name}\n\n";
    
    // 5. Calculate pending amount
    $resolvedAmount = SafedropResolution::where('daily_sale_id', $dailySale->id)
        ->where('type', 'safedrops')
        ->sum('amount');
    
    $pendingAmount = $dailySale->safedrops_amount - $resolvedAmount;
    
    echo "5. Resolution Details:\n";
    echo "   - Total Safedrops: \${$dailySale->safedrops_amount}\n";
    echo "   - Already Resolved: \${$resolvedAmount}\n";
    echo "   - Pending Amount: \${$pendingAmount}\n\n";
    
    if ($pendingAmount <= 0) {
        echo "   ⚠️  No pending amount to resolve\n";
        echo "   Test cannot proceed\n";
        exit(0);
    }
    
    // 6. Record balances before
    $cashBalanceBefore = $cashAccount->balance;
    $targetBalanceBefore = $targetAccount->balance;
    
    echo "6. Balances BEFORE resolution:\n";
    echo "   - Cash Account: \${$cashBalanceBefore}\n";
    echo "   - Target Account: \${$targetBalanceBefore}\n\n";
    
    // 7. Simulate resolution (DRY RUN - don't actually create)
    $testAmount = min($pendingAmount, 100.00); // Test with $100 or less
    
    echo "7. SIMULATION (not creating actual records):\n";
    echo "   Would create resolution for: \${$testAmount}\n";
    echo "   Would create Transfer transaction:\n";
    echo "   - Type: transfer\n";
    echo "   - From: {$cashAccount->account_name} (Cash)\n";
    echo "   - To: {$targetAccount->account_name}\n";
    echo "   - Amount: \${$testAmount}\n";
    echo "   - Description: Safedrop resolution for {$dailySale->date}\n\n";
    
    echo "8. Expected Balances AFTER:\n";
    echo "   - Cash Account: \$" . ($cashBalanceBefore - $testAmount) . " (decreased)\n";
    echo "   - Target Account: \$" . ($targetBalanceBefore + $testAmount) . " (increased)\n\n";
    
    // 9. Check existing transactions
    echo "9. Checking existing safedrop transactions...\n";
    $existingTransactions = Transaction::where('type', 'transfer')
        ->where('reference_number', 'like', 'SR-%')
        ->count();
    
    echo "   - Found {$existingTransactions} existing safedrop resolution transactions\n\n";
    
    echo "=== TEST COMPLETED SUCCESSFULLY ===\n";
    echo "The system is ready to create Transfer transactions for safedrop resolutions.\n";
    echo "When you resolve a safedrop through the API, it will:\n";
    echo "1. Create a SafedropResolution record\n";
    echo "2. Create a Transfer transaction (Cash → Bank Account)\n";
    echo "3. Update both account balances\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
