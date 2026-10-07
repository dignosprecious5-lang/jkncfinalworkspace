<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestAccountSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Login User Account
        User::updateOrCreate(
            ['email' => 'admin@testing.com'],
            [
                'name' => 'Testing Admin',
                'password' => Hash::make('Password123!'),
                'role' => 'SuperAdmin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@testing.com'],
            [
                'name' => 'Test User Account',
                'password' => Hash::make('Password123!'),
                'role' => 'Admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        // 2. Create CRM Accounts (Business & Individual)
        $company = Company::first();
        $contact = Contact::first();

        Account::firstOrCreate(
            ['account_code' => 'ACC-2026-001'],
            [
                'account_type' => 'Business',
                'account_name' => $company ? $company->company_name : 'Apex Global Logistics Solutions Inc.',
                'company_id' => $company?->id,
                'status' => 'Active',
            ]
        );

        Account::firstOrCreate(
            ['account_code' => 'ACC-2026-002'],
            [
                'account_type' => 'Individual',
                'account_name' => $contact ? ($contact->first_name . ' ' . $contact->last_name) : 'Maria Garcia',
                'individual_contact_id' => $contact?->id,
                'status' => 'Active',
            ]
        );
    }
}
