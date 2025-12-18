<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VendorInvoice;
use App\Models\Vendor;
use App\Models\User;

class VendorInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user and vendor for seeding
        $user = User::first();
        $vendor = Vendor::first();

        if (!$user || !$vendor) {
            $this->command->info('No users or vendors found. Please run UserSeeder and VendorSeeder first.');
            return;
        }

        $invoices = [
            [
                'vendor_id' => $vendor->id,
                'invoice_date' => now()->subDays(30),
                'status' => 'Paid',
                'type' => 'Expense',
                'payment_date' => now()->subDays(25),
                'payment_method' => 'Card',
                'amount' => 1250.00,
                'description' => 'Monthly fuel supply invoice',
                'user_id' => $user->id,
            ],
            [
                'vendor_id' => $vendor->id,
                'invoice_date' => now()->subDays(15),
                'status' => 'Unpaid',
                'type' => 'Expense',
                'amount' => 850.50,
                'description' => 'Equipment maintenance services',
                'user_id' => $user->id,
            ],
            [
                'vendor_id' => $vendor->id,
                'invoice_date' => now()->subDays(7),
                'status' => 'Paid',
                'type' => 'Income',
                'payment_date' => now()->subDays(5),
                'payment_method' => 'Bank',
                'amount' => 2500.00,
                'description' => 'Consulting services provided',
                'user_id' => $user->id,
            ],
            [
                'vendor_id' => $vendor->id,
                'invoice_date' => now()->subDays(3),
                'status' => 'Unpaid',
                'type' => 'Expense',
                'amount' => 320.75,
                'description' => 'Office supplies and stationery',
                'user_id' => $user->id,
            ],
        ];

        foreach ($invoices as $invoiceData) {
            VendorInvoice::create($invoiceData);
        }

        $this->command->info('Vendor invoices seeded successfully!');
    }
}
