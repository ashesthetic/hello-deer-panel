<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_sales', function (Blueprint $table) {
            $table->decimal('payouts', 10, 2)->default(0)->after('lottery_payout');
            $table->decimal('pos_payout', 10, 2)->default(0)->after('payouts');
            $table->decimal('cashback_payout', 10, 2)->default(0)->after('pos_payout');
            $table->decimal('uhaul_payout', 10, 2)->default(0)->after('cashback_payout');
            $table->decimal('vendor_payout', 10, 2)->default(0)->after('uhaul_payout');
        });

        // Existing lottery_payout held the total payout — move it to the new payouts column.
        DB::statement('UPDATE daily_sales SET payouts = lottery_payout, lottery_payout = 0');
    }

    public function down(): void
    {
        // Restore lottery_payout from payouts before dropping the column.
        DB::statement('UPDATE daily_sales SET lottery_payout = payouts');

        Schema::table('daily_sales', function (Blueprint $table) {
            $table->dropColumn(['payouts', 'pos_payout', 'cashback_payout', 'uhaul_payout', 'vendor_payout']);
        });
    }
};
