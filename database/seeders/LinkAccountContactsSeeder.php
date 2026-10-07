<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Seeder;

class LinkAccountContactsSeeder extends Seeder
{
    /**
     * Run database seeder to ensure EVERY account has a primary contact linked.
     */
    public function run(): void
    {
        // 1. Ensure all Companies have a corresponding Business Account and a linked Primary Contact
        $companies = Company::all();

        foreach ($companies as $index => $company) {
            // Check or create primary contact for this company
            $contact = Contact::where('company_name', $company->company_name)
                ->orWhere('email', 'like', '%' . strtolower(explode(' ', $company->company_name)[0]) . '%')
                ->first();

            if (! $contact) {
                $contact = Contact::create([
                    'first_name' => 'Primary Contact',
                    'last_name' => '(' . $company->company_name . ')',
                    'email' => 'contact.' . $company->id . '@' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $company->company_name)) . '.ph',
                    'phone' => '+63 917 888 ' . str_pad((string) ($company->id * 111), 4, '0', STR_PAD_LEFT),
                    'position' => 'Authorized Representative',
                    'company_name' => $company->company_name,
                    'company_address' => $company->address,
                ]);
            }

            // Link primary contact on company
            $company->update(['primary_contact_id' => $contact->id]);

            // Sync pivot if method exists
            if (method_exists($company, 'contacts')) {
                $company->contacts()->syncWithoutDetaching([$contact->id]);
            }

            // Create or update Business Account
            $accountCode = sprintf('ACC-BUS-%03d', $company->id);

            Account::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'account_code' => $accountCode,
                    'account_type' => 'Business',
                    'account_name' => $company->company_name,
                    'status' => 'Active',
                ]
            );
        }

        // 2. Ensure all Contacts have an Individual Account record if not belonging to a company account
        $contacts = Contact::all();

        foreach ($contacts as $contact) {
            $accountCode = sprintf('ACC-IND-%03d', $contact->id);

            Account::firstOrCreate(
                ['individual_contact_id' => $contact->id],
                [
                    'account_code' => $accountCode,
                    'account_type' => 'Individual',
                    'account_name' => trim($contact->first_name . ' ' . $contact->last_name) ?: 'Individual Client',
                    'status' => 'Active',
                ]
            );
        }

        // 3. Verify all existing Accounts have contacts associated
        $accounts = Account::all();
        foreach ($accounts as $account) {
            if ($account->account_type === 'Business' && $account->company_id) {
                $comp = Company::find($account->company_id);
                if ($comp && ! $comp->primary_contact_id) {
                    $con = Contact::where('company_name', $comp->company_name)->first();
                    if ($con) {
                        $comp->update(['primary_contact_id' => $con->id]);
                    }
                }
            } elseif ($account->account_type === 'Individual' && ! $account->individual_contact_id) {
                $con = Contact::firstOrCreate(
                    ['email' => strtolower(str_replace(' ', '', $account->account_name)) . '@client.ph'],
                    [
                        'first_name' => $account->account_name,
                        'last_name' => 'Client',
                        'position' => 'Individual Client',
                    ]
                );
                $account->update(['individual_contact_id' => $con->id]);
            }
        }
    }
}
