<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\DailySale;
use App\Models\BankAccount;
use App\Models\SafedropResolution;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if resolutions already exist (migration already ran)
        $existingResolutions = SafedropResolution::count();
        if ($existingResolutions > 0) {
            echo "Historical resolutions already exist ({$existingResolutions} found). Skipping migration.\n";
            return;
        }
        
        // Use the first active bank account for historical resolutions
        $defaultBankAccount = BankAccount::where('is_active', true)->first();
        
        if (!$defaultBankAccount) {
            echo "\n❌ ERROR: No active bank accounts found!\n";
            echo "Please run the setup script first:\n";
            echo "   php setup_live_server.php\n";
            echo "Or create bank accounts manually through the admin interface.\n\n";
            
            echo "Available bank accounts:\n";
            $allBankAccounts = BankAccount::all(['id', 'account_name', 'is_active']);
            if ($allBankAccounts->count() > 0) {
                foreach ($allBankAccounts as $account) {
                    $status = $account->is_active ? 'Active' : 'Inactive';
                    echo "- ID: {$account->id}, Name: {$account->account_name}, Status: {$status}\n";
                }
            } else {
                echo "- No bank accounts found in database\n";
            }
            
            throw new Exception('No active bank accounts found. Please create a bank account first.');
        }

        // Get the first admin user to assign as the resolver
        $adminUser = User::where('role', 'admin')->first();
        if (!$adminUser) {
            // If no admin exists, use the first user
            $adminUser = User::first();
        }

        if (!$adminUser) {
            throw new Exception('No users found in the system. Cannot proceed with historical resolution.');
        }

        // Get all daily sales with safedrops or cash in hand amounts
        $dailySales = DailySale::where(function($query) {
            $query->where('safedrops_amount', '>', 0)
                  ->orWhere('cash_on_hand', '>', 0);
        })->get();

        $resolutionsCreated = 0;
        $totalSafedropsResolved = 0;
        $totalCashResolved = 0;

        foreach ($dailySales as $sale) {
            // Resolve safedrops if amount > 0
            if ($sale->safedrops_amount > 0) {
                SafedropResolution::create([
                    'daily_sale_id' => $sale->id,
                    'bank_account_id' => $defaultBankAccount->id,
                    'user_id' => $adminUser->id,
                    'amount' => $sale->safedrops_amount,
                    'type' => 'safedrops',
                    'notes' => 'Historical resolution - automatically resolved during system migration'
                ]);
                
                $totalSafedropsResolved += $sale->safedrops_amount;
                $resolutionsCreated++;
            }

            // Resolve cash in hand if amount > 0 (or < 0 for negative amounts)
            if ($sale->cash_on_hand != 0) {
                SafedropResolution::create([
                    'daily_sale_id' => $sale->id,
                    'bank_account_id' => $defaultBankAccount->id,
                    'user_id' => $adminUser->id,
                    'amount' => abs($sale->cash_on_hand), // Use absolute value since we track amounts as positive
                    'type' => 'cash_in_hand',
                    'notes' => 'Historical resolution - automatically resolved during system migration' . 
                              ($sale->cash_on_hand < 0 ? ' (was negative amount)' : '')
                ]);
                
                $totalCashResolved += abs($sale->cash_on_hand);
                $resolutionsCreated++;
            }
        }

        // Update the bank account balance with the total resolved amounts
        $defaultBankAccount->increment('balance', $totalSafedropsResolved + $totalCashResolved);

        // Log the results
        echo "Historical Resolution Complete:\n";
        echo "- Resolutions created: {$resolutionsCreated}\n";
        echo "- Total safedrops resolved: $" . number_format($totalSafedropsResolved, 2) . "\n";
        echo "- Total cash in hand resolved: $" . number_format($totalCashResolved, 2) . "\n";
        echo "- Bank account '{$defaultBankAccount->account_name}' balance updated by: $" . 
             number_format($totalSafedropsResolved + $totalCashResolved, 2) . "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all historical resolutions
        SafedropResolution::where('notes', 'like', '%Historical resolution%')->delete();
        
        // Note: We don't reverse the bank account balance changes as they may have been 
        // modified by other transactions after the migration
        echo "Historical resolutions removed. Note: Bank account balances were not adjusted.\n";
    }
};
