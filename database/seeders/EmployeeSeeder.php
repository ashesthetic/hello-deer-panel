<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user for demo purposes
        $user = User::first();
        
        if (!$user) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        $employees = [
            [
                'full_legal_name' => 'John Smith',
                'preferred_name' => 'Johnny',
                'date_of_birth' => '1990-05-15',
                'address' => '123 Main Street, Toronto, ON',
                'postal_code' => 'M5V 2H1',
                'country' => 'Canada',
                'phone_number' => '(555) 123-4567',
                'alternate_number' => '(555) 987-6543',
                'email' => 'john.smith@company.com',
                'emergency_name' => 'Jane Smith',
                'emergency_relationship' => 'Spouse',
                'emergency_phone' => '(555) 234-5678',
                'emergency_alternate_number' => '(555) 876-5432',
                'emergency_address_line1' => '123 Main Street',
                'emergency_address_line2' => 'Apt 4B',
                'emergency_city' => 'Toronto',
                'emergency_state' => 'ON',
                'emergency_postal_code' => 'M5V 2H1',
                'emergency_country' => 'Canada',
                'status_in_canada' => 'Canadian Citizen',
                'other_status' => null,
                'sin_number' => '123-456-789',
                'position' => 'Manager',
                'department' => 'Sales',
                'hire_date' => '2023-01-15',
                'hourly_rate' => 25.00,
                'facebook' => 'https://facebook.com/johnsmith',
                'linkedin' => 'https://linkedin.com/in/johnsmith',
                'twitter' => 'https://twitter.com/johnsmith',
                'user_id' => $user->id,
                'status' => 'active'
            ],
            [
                'full_legal_name' => 'Sarah Johnson',
                'preferred_name' => null,
                'date_of_birth' => '1988-12-03',
                'address' => '456 Oak Avenue, Vancouver, BC',
                'postal_code' => 'V6B 1A1',
                'country' => 'Canada',
                'phone_number' => '(555) 345-6789',
                'alternate_number' => null,
                'email' => 'sarah.johnson@company.com',
                'emergency_name' => 'Mike Johnson',
                'emergency_relationship' => 'Husband',
                'emergency_phone' => '(555) 456-7890',
                'emergency_alternate_number' => null,
                'emergency_address_line1' => '456 Oak Avenue',
                'emergency_address_line2' => null,
                'emergency_city' => 'Vancouver',
                'emergency_state' => 'BC',
                'emergency_postal_code' => 'V6B 1A1',
                'emergency_country' => 'Canada',
                'status_in_canada' => 'Permanent Resident',
                'other_status' => null,
                'sin_number' => '987-654-321',
                'position' => 'Store Associate',
                'department' => 'Retail',
                'hire_date' => '2023-03-22',
                'hourly_rate' => 18.50,
                'facebook' => null,
                'linkedin' => 'https://linkedin.com/in/sarahjohnson',
                'twitter' => null,
                'user_id' => $user->id,
                'status' => 'active'
            ],
            [
                'full_legal_name' => 'Mike Davis',
                'preferred_name' => null,
                'date_of_birth' => '1992-08-20',
                'address' => '789 Pine Street, Montreal, QC',
                'postal_code' => 'H2Y 1C6',
                'country' => 'Canada',
                'phone_number' => '(555) 567-8901',
                'alternate_number' => null,
                'email' => 'mike.davis@company.com',
                'emergency_name' => 'Lisa Davis',
                'emergency_relationship' => 'Wife',
                'emergency_phone' => '(555) 678-9012',
                'emergency_alternate_number' => null,
                'emergency_address_line1' => '789 Pine Street',
                'emergency_address_line2' => null,
                'emergency_city' => 'Montreal',
                'emergency_state' => 'QC',
                'emergency_postal_code' => 'H2Y 1C6',
                'emergency_country' => 'Canada',
                'status_in_canada' => 'Work Permit',
                'other_status' => null,
                'sin_number' => '456-789-012',
                'position' => 'Director',
                'department' => 'Operations',
                'hire_date' => '2023-02-10',
                'hourly_rate' => 35.00,
                'facebook' => null,
                'linkedin' => 'https://linkedin.com/in/mikedavis',
                'twitter' => null,
                'user_id' => $user->id,
                'status' => 'active'
            ]
        ];

        foreach ($employees as $employeeData) {
            Employee::create($employeeData);
        }

        $this->command->info('Demo employees created successfully!');
    }
}
