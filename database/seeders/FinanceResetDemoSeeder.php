<?php

namespace Database\Seeders;

use App\Models\FinanceRecord;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceResetDemoSeeder extends Seeder
{
    private User $actor;

    private array $records = [];

    private array $workflowScenarios = [
        ['Accepted', 'Approved', 'Active'],
        ['Accepted', 'Approved', 'Active'],
        ['Submitted', 'Approved', 'Active'],
        ['Uploaded', 'Approved', 'Active'],
        ['Shared', 'Approved', 'Active'],
        ['Accepted', 'Approved', 'Active'],
        ['Accepted', 'Approved', 'Inactive'],
        ['Reverted', 'Pending', 'Active'],
        ['Uploaded', 'Pending', 'Active'],
        ['Archived', 'Approved', 'Inactive'],
    ];

    public function run(): void
    {
        $this->actor = User::where('email', 'superadmin@jknc.com')->first() ?? User::query()->first();

        if (!$this->actor) {
            $this->command?->warn('FinanceResetDemoSeeder skipped: no user found to attach seed records.');
            return;
        }

        DB::transaction(function () {
            FinanceRecord::query()->delete();

            $payrollPeriods = $this->seedPayrollPeriods();
            $chartAccounts = $this->seedChartAccounts();
            $bankAccounts = $this->seedBankAccounts($chartAccounts);
            $suppliers = $this->seedSuppliers();
            $services = $this->seedServices($suppliers, $chartAccounts);
            $products = $this->seedProducts($suppliers, $chartAccounts);
            $purchaseRequests = $this->seedPurchaseRequests($suppliers, $services, $products, $chartAccounts);
            $purchaseOrders = $this->seedPurchaseOrders($purchaseRequests, $suppliers, $chartAccounts);
            $cashAdvances = $this->seedCashAdvances($bankAccounts, $chartAccounts);
            $liquidations = $this->seedLiquidations($cashAdvances, $products, $services, $chartAccounts);
            $expenseReimbursements = $this->seedExpenseReimbursements($liquidations, $bankAccounts);
            $cashReturns = $this->seedCashReturns($liquidations, $bankAccounts, $chartAccounts);
            $fundTransfers = $this->seedFundTransfers($bankAccounts);
            $payrollDisbursements = $this->seedPayrollDisbursements($payrollPeriods, $bankAccounts, $chartAccounts);
            $assets = $this->seedAssets($purchaseOrders, $suppliers, $chartAccounts);
            $this->seedDisbursementVouchers(
                $purchaseRequests,
                $purchaseOrders,
                $cashAdvances,
                $liquidations,
                $expenseReimbursements,
                $payrollDisbursements,
                $cashReturns,
                $fundTransfers,
                $assets,
                $suppliers,
                $bankAccounts,
                $chartAccounts
            );
        });

        $this->command?->info('Finance records reset and seeded with 10 demo records per finance section.');
    }

    private function scenario(int $index): array
    {
        return $this->workflowScenarios[($index - 1) % count($this->workflowScenarios)];
    }

    private function createRecord(string $moduleKey, int $index, string $title, array $data = [], ?float $amount = null): FinanceRecord
    {
        [$workflowStatus, $approvalStatus, $status] = $this->scenario($index);
        $date = Carbon::create(2026, 5, 1)->addDays($index - 1);
        $recordNumber = $this->recordPrefix($moduleKey).'-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);

        $record = FinanceRecord::create([
            'module_key' => $moduleKey,
            'record_number' => $recordNumber,
            'record_title' => $title,
            'record_date' => $date->toDateString(),
            'amount' => $amount,
            'status' => $status,
            'workflow_status' => $workflowStatus,
            'approval_status' => $approvalStatus,
            'submitted_by' => $this->actor->id,
            'submitted_at' => $date->copy()->setTime(9, 0),
            'approved_by' => $approvalStatus === 'Approved' ? $this->actor->id : null,
            'approved_at' => $approvalStatus === 'Approved' ? $date->copy()->setTime(14, 0) : null,
            'review_note' => 'Finance reset demo scenario '.$index.' for '.$moduleKey.'.',
            'data' => $data,
            'attachments' => [],
            'user' => $this->actor->name,
        ]);

        $this->records[$moduleKey][] = $record;

        return $record;
    }

    private function recordPrefix(string $moduleKey): string
    {
        return [
            'supplier' => 'SUP',
            'service' => 'SRV',
            'product' => 'PRD',
            'chart_account' => 'COA',
            'bank_account' => 'BANK',
            'pr' => 'PR',
            'po' => 'PO',
            'ca' => 'CA',
            'lr' => 'LR',
            'err' => 'ERR',
            'dv' => 'DV',
            'pda' => 'PDA',
            'crf' => 'CRF',
            'ibtf' => 'IBTF',
            'arf' => 'ARF',
        ][$moduleKey] ?? strtoupper($moduleKey);
    }

    private function seedPayrollPeriods(): array
    {
        if (!Schema::hasTable('payroll_periods')) {
            return [];
        }

        return collect(range(1, 10))->map(function (int $index) {
            $start = Carbon::create(2026, 5, 1)->addDays(($index - 1) * 14);
            $end = $start->copy()->addDays(13);

            return PayrollPeriod::updateOrCreate(
                ['name' => 'Finance Test Payroll '.$index],
                [
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'payroll_start' => $start->toDateString(),
                    'payroll_end' => $end->toDateString(),
                    'payroll_date' => $end->copy()->addDay()->toDateString(),
                    'pay_date' => $end->copy()->addDays(3)->toDateString(),
                    'dispute_start' => $end->copy()->addDay()->toDateString(),
                    'dispute_end' => $end->copy()->addDays(2)->toDateString(),
                    'date_created' => $start->toDateString(),
                    'policy_number' => 'PAY-POL-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                    'status' => ['draft', 'open', 'processed'][$index % 3],
                ]
            );
        })->all();
    }

    private function seedChartAccounts(): array
    {
        $types = ['Asset', 'Liability', 'Equity', 'Income', 'Expense', 'Cash'];
        $groups = ['Cash and Cash Equivalents', 'Bank Accounts', 'Operating Expenses', 'Revenue', 'Payroll', 'Fixed Assets'];
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $parentId = $i > 6 ? $records[$i - 7]->id : null;
            $records[] = $this->createRecord('chart_account', $i, 'Finance Test Account '.$i, [
                'account_description' => 'Scenario account for '.$groups[($i - 1) % count($groups)].'.',
                'is_sub_account' => $parentId ? '1' : '',
                'parent_account_id' => $parentId,
                'account_type' => $types[($i - 1) % count($types)],
                'account_group' => $groups[($i - 1) % count($groups)],
                'normal_balance' => in_array($types[($i - 1) % count($types)], ['Asset', 'Expense', 'Cash'], true) ? 'Debit' : 'Credit',
                'account_status' => $i === 7 ? 'Inactive' : 'Active',
                'remarks' => $parentId ? 'Sub-account test scenario.' : 'Main account test scenario.',
            ]);
        }

        return $records;
    }

    private function seedBankAccounts(array $chartAccounts): array
    {
        $banks = ['BPI', 'BDO', 'Metrobank', 'Security Bank', 'UnionBank'];
        $accountTypes = ['Checking', 'Savings', 'Payroll', 'Petty Cash'];
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $records[] = $this->createRecord('bank_account', $i, 'Finance Test '.$banks[($i - 1) % count($banks)].' Account '.$i, [
                'bank_name' => $banks[($i - 1) % count($banks)],
                'branch' => 'Test Branch '.$i,
                'currency' => $i % 4 === 0 ? 'USD' : 'PHP',
                'bank_status' => $i === 8 ? 'Inactive' : 'Active',
                'account_type' => $accountTypes[($i - 1) % count($accountTypes)],
                'linked_coa_id' => $chartAccounts[($i - 1) % count($chartAccounts)]->id,
                'signatory_notes' => 'Seeded bank scenario '.$i.'.',
                'remarks' => $i % 2 === 0 ? 'Used for checks and transfers.' : 'Used for cash source testing.',
            ]);
        }

        return $records;
    }

    private function seedSuppliers(): array
    {
        $types = ['Local Vendor', 'Foreign Vendor', 'Service Provider', 'Contractor'];
        $vatStatuses = ['VAT', 'Non-VAT'];
        $accreditation = ['Pending', 'For Accreditation', 'Accredited', 'Blacklisted'];
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $records[] = $this->createRecord('supplier', $i, 'Finance Test Supplier '.$i, [
                'completion_mode' => $i === 9 ? 'send_to_supplier' : 'complete_internally',
                'trade_name' => 'FT Supplier '.$i,
                'supplier_type' => $types[($i - 1) % count($types)],
                'representative_full_name' => 'Supplier Rep '.$i,
                'designation' => ['Owner', 'Sales Manager', 'Account Officer'][$i % 3],
                'email_address' => 'supplier'.$i.'@finance-test.local',
                'phone_number' => '091700000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'alternate_contact_number' => '092700000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'business_address' => 'Business Address '.$i.', Makati City',
                'billing_address' => 'Billing Address '.$i.', Taguig City',
                'tin' => '900-000-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'-000',
                'vat_status' => $vatStatuses[$i % 2],
                'payment_terms' => ['COD', 'Net 15', 'Net 30', 'Upon Completion'][$i % 4],
                'accreditation_status' => $accreditation[($i - 1) % count($accreditation)],
                'bank_name' => ['BPI', 'BDO', 'Metrobank'][$i % 3],
                'bank_account_name' => 'Finance Test Supplier '.$i,
                'bank_account_number' => '100200300'.$i,
                'remarks' => 'Supplier scenario '.$i.' for finance testing.',
            ]);
        }

        return $records;
    }

    private function seedServices(array $suppliers, array $chartAccounts): array
    {
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $cost = 1800 + ($i * 350);
            $records[] = $this->createRecord('service', $i, 'Finance Test Service '.$i, [
                'service_description' => 'Service scenario '.$i.' for operations support.',
                'products_services_provided' => 'Consulting / maintenance package '.$i,
                'supplier_id' => $suppliers[($i - 1) % count($suppliers)]->id,
                'coa_id' => $chartAccounts[($i + 1) % count($chartAccounts)]->id,
                'category' => ['Facilities', 'Professional Fees', 'IT Support', 'Logistics'][$i % 4],
                'unit_of_measure' => ['per service/job', 'per month', 'per hour'][$i % 3],
                'default_cost' => $cost,
                'tax_type' => ['VAT', 'Non-VAT', 'N/A'][$i % 3],
                'service_status' => $i === 10 ? 'Inactive' : 'Active',
                'remarks' => 'Service master scenario '.$i.'.',
            ], $cost);
        }

        return $records;
    }

    private function seedProducts(array $suppliers, array $chartAccounts): array
    {
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $cost = 250 + ($i * 125);
            $records[] = $this->createRecord('product', $i, 'Finance Test Product '.$i, [
                'product_description' => 'Consumable or equipment product scenario '.$i.'.',
                'supplier_id' => $suppliers[($i + 1) % count($suppliers)]->id,
                'coa_id' => $chartAccounts[($i + 2) % count($chartAccounts)]->id,
                'category' => ['Office Supplies', 'Equipment', 'Software', 'Pantry'][$i % 4],
                'unit_of_measure' => ['per unit', 'per box', 'per pack'][$i % 3],
                'default_cost' => $cost,
                'tax_type' => ['VAT', 'Non-VAT', 'N/A'][$i % 3],
                'product_status' => $i === 9 ? 'Inactive' : 'Active',
                'remarks' => 'Product master scenario '.$i.'.',
            ], $cost);
        }

        return $records;
    }

    private function seedPurchaseRequests(array $suppliers, array $services, array $products, array $chartAccounts): array
    {
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $isService = $i % 2 === 0;
            $item = $isService ? $services[($i - 1) % count($services)] : $products[($i - 1) % count($products)];
            $quantity = ($i % 4) + 1;
            $unitAmount = (float) data_get($item->data, 'default_cost', 500);
            $total = $quantity * $unitAmount;

            $records[] = $this->createRecord('pr', $i, 'Finance Test PR '.$i, [
                'requester_mode' => $i % 3 === 0 ? 'request_for_another' : 'own_request',
                'requesting_department' => ['Finance', 'Operations', 'Human Capital', 'Sales'][$i % 4],
                'requestor' => 'Requestor '.$i,
                'request_type' => $isService ? 'Service' : 'Product',
                'priority' => ['Low', 'Medium', 'High', 'Urgent'][$i % 4],
                'needed_date' => Carbon::create(2026, 5, 15)->addDays($i)->toDateString(),
                'for_client' => $i % 2 === 0 ? 'Yes' : 'No',
                'pr_reason_categories' => ['Operational Need', 'Client Requirement'],
                'purpose' => 'Purchase request test scenario '.$i.'.',
                'supplier_id' => $suppliers[($i - 1) % count($suppliers)]->id,
                'master_item_type' => $isService ? 'service' : 'product',
                'master_item_id' => $item->id,
                'coa_id' => $chartAccounts[($i - 1) % count($chartAccounts)]->id,
                'line_items' => [[
                    'item_module' => $isService ? 'service' : 'product',
                    'item_record_id' => $item->id,
                    'item_id' => $item->record_title,
                    'description' => $isService ? data_get($item->data, 'service_description') : data_get($item->data, 'product_description'),
                    'category' => data_get($item->data, 'category'),
                    'quantity' => $quantity,
                    'amount' => number_format($unitAmount, 2, '.', ''),
                    'subtotal' => number_format($total, 2, '.', ''),
                    'discount' => $i % 3 === 0 ? '5%' : '0%',
                    'discount_amount' => $i % 3 === 0 ? number_format($total * 0.05, 2, '.', '') : '0.00',
                    'shipping_amount' => $i % 2 === 0 ? '150.00' : '0.00',
                    'tax_type' => data_get($item->data, 'tax_type'),
                    'tax_amount' => data_get($item->data, 'tax_type') === 'VAT' ? number_format($total * 0.12, 2, '.', '') : '0.00',
                    'wht_amount' => $isService ? number_format($total * 0.02, 2, '.', '') : '0.00',
                    'total' => number_format($total, 2, '.', ''),
                    'supplier_id' => $suppliers[($i - 1) % count($suppliers)]->id,
                    'client_id' => '',
                ]],
                'subtotal' => number_format($total, 2, '.', ''),
                'grand_total' => number_format($total, 2, '.', ''),
                'remarks' => $i % 2 === 0 ? 'Client-facing request.' : 'Internal department request.',
            ], $total);
        }

        return $records;
    }

    private function seedPurchaseOrders(array $purchaseRequests, array $suppliers, array $chartAccounts): array
    {
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $pr = $purchaseRequests[($i - 1) % count($purchaseRequests)];
            $amount = (float) ($pr->amount ?: data_get($pr->data, 'grand_total', 0));
            $records[] = $this->createRecord('po', $i, 'Finance Test PO '.$i, [
                'linked_pr_id' => $pr->id,
                'supplier_id' => $suppliers[($i - 1) % count($suppliers)]->id,
                'expected_delivery_date' => Carbon::create(2026, 6, 1)->addDays($i)->toDateString(),
                'delivery_address' => 'Delivery site '.$i.', BGC',
                'terms_and_conditions' => ['Standard delivery', 'Rush delivery', 'Partial delivery allowed'][$i % 3],
                'line_items' => data_get($pr->data, 'line_items', []),
                'purpose' => 'PO created from PR scenario '.$i.'.',
                'coa_id' => $chartAccounts[($i + 1) % count($chartAccounts)]->id,
                'remarks' => $i % 2 === 0 ? 'For staggered delivery.' : 'For single delivery.',
            ], $amount);
        }

        return $records;
    }

    private function seedCashAdvances(array $bankAccounts, array $chartAccounts): array
    {
        $records = [];
        $releaseSchedules = ['One-time', 'Weekly', 'Monthly'];
        $releaseModes = ['Cash', 'Check', 'Bank Transfer', 'E-Wallet'];

        for ($i = 1; $i <= 10; $i++) {
            $amount = 4000 + ($i * 1200);
            $releaseCount = ($i % 3) + 1;
            $records[] = $this->createRecord('ca', $i, 'Cash Advance Requestor '.$i, [
                'requester_mode' => $i % 2 === 0 ? 'own_request' : 'request_for_another',
                'requestor' => 'CA Requestor '.$i,
                'department' => ['Finance', 'Operations', 'Sales'][$i % 3],
                'purpose' => 'Cash advance scenario '.$i.' for field expense testing.',
                'needed_date' => Carbon::create(2026, 5, 20)->addDays($i)->toDateString(),
                'priority' => ['Low', 'Medium', 'High', 'Urgent'][$i % 4],
                'cash_advance_type' => ['Project', 'Official Business', 'Emergency'][$i % 3],
                'mode_of_release' => $releaseModes[($i - 1) % count($releaseModes)],
                'amount_requested' => number_format($amount, 2, '.', ''),
                'release_schedule' => $releaseSchedules[($i - 1) % count($releaseSchedules)],
                'release_count' => $releaseCount,
                'amount_per_release' => number_format($amount / $releaseCount, 2, '.', ''),
                'cash_release_date' => Carbon::create(2026, 5, 22)->addDays($i)->toDateString(),
                'cash_release_time' => '09:30',
                'paid_through' => $chartAccounts[($i - 1) % count($chartAccounts)]->id,
                'bank_account_id' => $bankAccounts[($i - 1) % count($bankAccounts)]->id,
                'for_client' => $i % 2 === 0 ? 'Yes' : 'No',
                'client_names' => $i % 2 === 0 ? 'Client '.$i : '',
                'remarks' => 'Cash advance scenario '.$i.'.',
            ], $amount);
        }

        return $records;
    }

    private function seedLiquidations(array $cashAdvances, array $products, array $services, array $chartAccounts): array
    {
        $records = [];
        $variancePattern = ['Shortage', 'Overage', 'Balanced', 'Shortage', 'Overage', 'Balanced', 'Shortage', 'Overage', 'Shortage', 'Overage'];

        for ($i = 1; $i <= 10; $i++) {
            $ca = $cashAdvances[($i - 1) % count($cashAdvances)];
            $caAmount = (float) data_get($ca->data, 'amount_requested', $ca->amount);
            $indicator = $variancePattern[$i - 1];
            $variance = match ($indicator) {
                'Shortage' => -1 * (350 + ($i * 75)),
                'Overage' => 250 + ($i * 50),
                default => 0,
            };
            $actual = $caAmount - $variance;
            $item = $i % 2 === 0 ? $services[($i - 1) % count($services)] : $products[($i - 1) % count($products)];

            $records[] = $this->createRecord('lr', $i, 'Liquidation Report '.$i, [
                'requester_mode' => data_get($ca->data, 'requester_mode'),
                'linked_ca_id' => $ca->id,
                'linked_dv_id' => '',
                'total_cash_advance' => number_format($caAmount, 2, '.', ''),
                'purpose' => 'Liquidation scenario '.$i.' linked to '.$ca->record_number.'.',
                'employee_id' => 'EMP-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'employee_name' => 'Liquidating Employee '.$i,
                'employee_email' => 'employee'.$i.'@finance-test.local',
                'contact_number' => '093900000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'position' => ['Associate', 'Supervisor', 'Manager'][$i % 3],
                'department' => data_get($ca->data, 'department'),
                'superior' => 'Approver '.$i,
                'superior_email' => 'approver'.$i.'@finance-test.local',
                'for_client' => data_get($ca->data, 'for_client'),
                'client_names' => data_get($ca->data, 'client_names'),
                'line_items' => [[
                    'item_module' => $i % 2 === 0 ? 'service' : 'product',
                    'item_record_id' => $item->id,
                    'item_id' => $item->record_title,
                    'description' => 'Liquidated expense item '.$i,
                    'category' => data_get($item->data, 'category'),
                    'quantity' => 1,
                    'amount' => number_format($actual, 2, '.', ''),
                    'subtotal' => number_format($actual, 2, '.', ''),
                    'total' => number_format($actual, 2, '.', ''),
                    'supplier_id' => data_get($item->data, 'supplier_id'),
                    'tax_type' => data_get($item->data, 'tax_type'),
                ]],
                'subtotal' => number_format($actual, 2, '.', ''),
                'grand_total' => number_format($actual, 2, '.', ''),
                'actual_expenses' => number_format($actual, 2, '.', ''),
                'variance' => number_format($variance, 2, '.', ''),
                'variance_indicator' => $indicator,
                'coa_id' => $chartAccounts[($i - 1) % count($chartAccounts)]->id,
                'remarks' => 'Liquidation '.$indicator.' scenario.',
            ], $actual);
        }

        return $records;
    }

    private function seedExpenseReimbursements(array $liquidations, array $bankAccounts): array
    {
        $shortages = array_values(array_filter($liquidations, fn ($record) => data_get($record->data, 'variance_indicator') === 'Shortage'));
        $records = [];
        $modes = ['Cash', 'Bank Transfer', 'Check'];

        for ($i = 1; $i <= 10; $i++) {
            $lr = $shortages[($i - 1) % count($shortages)];
            $mode = $modes[($i - 1) % count($modes)];
            $amount = abs((float) data_get($lr->data, 'variance', 0));
            $data = [
                'requester_mode' => 'own_request',
                'linked_lr_id' => $lr->id,
                'requestor' => data_get($lr->data, 'employee_name'),
                'expense_details' => 'ERR shortage reimbursement scenario '.$i.'.',
                'amount' => number_format($amount, 2, '.', ''),
                'reimbursement_payment_details' => 'Reimburse shortage from '.$lr->record_number.'.',
                'manual_liquidation_entry' => $i % 4 === 0 ? '1' : '',
                'reimbursement_mode' => $mode,
                'remarks' => 'ERR '.$mode.' scenario.',
            ];

            if ($mode === 'Cash') {
                $data['cash_receiver_name'] = 'Cash Receiver '.$i;
            } elseif ($mode === 'Bank Transfer') {
                $data['recipient_bank_account'] = 'Recipient Account '.$i;
                $data['recipient_bank_number'] = 'RCPT-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT);
            } else {
                $data['bank_account_id'] = $bankAccounts[($i - 1) % count($bankAccounts)]->id;
            }

            $records[] = $this->createRecord('err', $i, 'ERR Requestor '.$i, $data, $amount);
        }

        return $records;
    }

    private function seedCashReturns(array $liquidations, array $bankAccounts, array $chartAccounts): array
    {
        $overages = array_values(array_filter($liquidations, fn ($record) => data_get($record->data, 'variance_indicator') === 'Overage'));
        $records = [];
        $modes = ['Cash', 'Bank Transfer', 'Check'];

        for ($i = 1; $i <= 10; $i++) {
            $lr = $overages[($i - 1) % count($overages)];
            $amount = abs((float) data_get($lr->data, 'variance', 0));
            $records[] = $this->createRecord('crf', $i, 'Cash Return '.$i, [
                'requester_mode' => 'own_request',
                'requestor' => data_get($lr->data, 'employee_name'),
                'linked_lr_id' => $lr->id,
                'amount_returned' => number_format($amount, 2, '.', ''),
                'manual_liquidation_entry' => $i % 3 === 0 ? '1' : '',
                'mode_of_return' => $modes[($i - 1) % count($modes)],
                'receiving_bank_account_id' => $bankAccounts[($i - 1) % count($bankAccounts)]->id,
                'coa_id' => $chartAccounts[($i - 1) % count($chartAccounts)]->id,
                'reference_number' => 'CRF-REF-'.$i,
                'remarks' => 'Cash return scenario '.$i.'.',
            ], $amount);
        }

        return $records;
    }

    private function seedFundTransfers(array $bankAccounts): array
    {
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $source = $bankAccounts[($i - 1) % count($bankAccounts)];
            $destination = $bankAccounts[$i % count($bankAccounts)];
            $amount = 5000 + ($i * 1000);
            $records[] = $this->createRecord('ibtf', $i, 'Fund Transfer '.$i, [
                'source_bank_account_id' => $source->id,
                'destination_bank_account_id' => $destination->id,
                'amount' => number_format($amount, 2, '.', ''),
                'reason' => ['Payroll funding', 'Supplier funding', 'Petty cash replenishment'][$i % 3],
                'source_account_code' => $source->record_number,
                'destination_account_code' => $destination->record_number,
                'transfer_reference_number' => 'IBTF-REF-'.$i,
                'remarks' => 'Interbank transfer scenario '.$i.'.',
            ], $amount);
        }

        return $records;
    }

    private function seedPayrollDisbursements(array $payrollPeriods, array $bankAccounts, array $chartAccounts): array
    {
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $period = $payrollPeriods[($i - 1) % max(count($payrollPeriods), 1)] ?? null;
            $gross = 120000 + ($i * 7500);
            $deductions = 8000 + ($i * 450);
            $total = $gross - $deductions;
            $records[] = $this->createRecord('pda', $i, 'Payroll Disbursement '.$i, [
                'payroll_period_id' => $period?->id,
                'period_start' => $period?->period_start?->format('Y-m-d') ?? Carbon::create(2026, 5, 1)->toDateString(),
                'period_end' => $period?->period_end?->format('Y-m-d') ?? Carbon::create(2026, 5, 15)->toDateString(),
                'payroll_start' => $period?->payroll_start?->format('Y-m-d') ?? Carbon::create(2026, 5, 1)->toDateString(),
                'payroll_end' => $period?->payroll_end?->format('Y-m-d') ?? Carbon::create(2026, 5, 15)->toDateString(),
                'pay_date' => $period?->pay_date?->format('Y-m-d') ?? Carbon::create(2026, 5, 18)->toDateString(),
                'employee_count' => 20 + $i,
                'basic_salary_total' => number_format($gross * 0.75, 2, '.', ''),
                'yearly_basic_total' => number_format($gross * 9, 2, '.', ''),
                'daily_rate_total' => number_format($gross / 22, 2, '.', ''),
                'hourly_rate_total' => number_format($gross / 176, 2, '.', ''),
                'minute_rate_total' => number_format($gross / 10560, 2, '.', ''),
                'gross_pay_total' => number_format($gross, 2, '.', ''),
                'benefits_total' => number_format(5000 + ($i * 200), 2, '.', ''),
                'allowances_total' => number_format(3000 + ($i * 150), 2, '.', ''),
                'deductions_total' => number_format($deductions, 2, '.', ''),
                'night_differential_total' => number_format($i * 120, 2, '.', ''),
                'holiday_pay_total' => number_format($i * 350, 2, '.', ''),
                'total_payroll_amount' => number_format($total, 2, '.', ''),
                'department' => ['All Departments', 'Operations', 'Finance'][$i % 3],
                'funding_bank_account_id' => $bankAccounts[($i - 1) % count($bankAccounts)]->id,
                'payroll_expense_coa_id' => $chartAccounts[($i + 4) % count($chartAccounts)]->id,
                'supporting_payroll_summary' => 'Payroll summary scenario '.$i.'.',
                'employee_payroll_breakdown' => 'Employee breakdown scenario '.$i.'.',
                'remarks' => 'PDA scenario '.$i.'.',
            ], $total);
        }

        return $records;
    }

    private function seedAssets(array $purchaseOrders, array $suppliers, array $chartAccounts): array
    {
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            $po = $purchaseOrders[($i - 1) % count($purchaseOrders)];
            $cost = 15000 + ($i * 2250);
            $records[] = $this->createRecord('arf', $i, 'Registered Asset '.$i, [
                'linked_po_id' => $po->id,
                'linked_dv_id' => '',
                'supplier_id' => $suppliers[($i - 1) % count($suppliers)]->id,
                'asset_code' => 'AST-2026-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'asset_description' => ['Laptop', 'Printer', 'Office Chair', 'Network Switch'][$i % 4].' scenario '.$i,
                'asset_category' => ['IT Equipment', 'Furniture', 'Office Equipment'][$i % 3],
                'serial_number' => 'SN-FT-'.$i.'-'.random_int(1000, 9999),
                'model' => 'Model-'.$i,
                'acquisition_cost' => number_format($cost, 2, '.', ''),
                'acquisition_date' => Carbon::create(2026, 6, 10)->addDays($i)->toDateString(),
                'asset_coa_id' => $chartAccounts[($i + 5) % count($chartAccounts)]->id,
                'location' => ['HQ', 'Warehouse', 'Client Site'][$i % 3],
                'custodian' => 'Custodian '.$i,
                'useful_life' => (string) (3 + ($i % 5)),
                'residual_value' => number_format($cost * 0.1, 2, '.', ''),
                'remarks' => 'Asset scenario '.$i.'.',
            ], $cost);
        }

        return $records;
    }

    private function seedDisbursementVouchers(
        array $purchaseRequests,
        array $purchaseOrders,
        array $cashAdvances,
        array $liquidations,
        array $expenseReimbursements,
        array $payrollDisbursements,
        array $cashReturns,
        array $fundTransfers,
        array $assets,
        array $suppliers,
        array $bankAccounts,
        array $chartAccounts
    ): array {
        $sourceSets = [
            ['pr', $purchaseRequests],
            ['po', $purchaseOrders],
            ['ca', $cashAdvances],
            ['lr', $liquidations],
            ['err', $expenseReimbursements],
            ['pda', $payrollDisbursements],
            ['crf', $cashReturns],
            ['ibtf', $fundTransfers],
            ['arf', $assets],
            ['po', $purchaseOrders],
        ];
        $records = [];

        for ($i = 1; $i <= 10; $i++) {
            [$sourceType, $sourceRecords] = $sourceSets[$i - 1];
            $source = $sourceRecords[($i - 1) % count($sourceRecords)];
            $amount = (float) ($source->amount ?: data_get($source->data, 'amount') ?: data_get($source->data, 'amount_returned') ?: data_get($source->data, 'total_payroll_amount') ?: data_get($source->data, 'acquisition_cost') ?: 1000);
            $paymentType = ['Cash', 'Check', 'Bank Transfer', 'E-Wallet'][$i % 4];

            $records[] = $this->createRecord('dv', $i, 'Disbursement Voucher '.$i, [
                'source_document_type' => $sourceType,
                'source_document_id' => $source->id,
                'supplier_id' => data_get($source->data, 'supplier_id') ?: $suppliers[($i - 1) % count($suppliers)]->id,
                'amount' => number_format($amount, 2, '.', ''),
                'payment_type' => $paymentType,
                'disbursement_type' => $paymentType === 'E-Wallet' ? 'Cash' : $paymentType,
                'bank_account_id' => $bankAccounts[($i - 1) % count($bankAccounts)]->id,
                'coa_id' => data_get($source->data, 'coa_id') ?: $chartAccounts[($i - 1) % count($chartAccounts)]->id,
                'fund_source' => ['Operations', 'Payroll', 'Project Fund'][$i % 3],
                'department' => data_get($source->data, 'department') ?: 'Finance',
                'reference_number' => 'DV-REF-'.$i,
                'purpose' => data_get($source->data, 'purpose') ?: data_get($source->data, 'expense_details') ?: 'DV scenario '.$i.'.',
                'payment_date' => Carbon::create(2026, 7, 1)->addDays($i)->toDateString(),
                'due_date' => Carbon::create(2026, 7, 8)->addDays($i)->toDateString(),
                'withholding_tax' => number_format($amount * 0.02, 2, '.', ''),
                'vat_amount' => $i % 2 === 0 ? number_format($amount * 0.12, 2, '.', '') : '0.00',
                'net_amount' => number_format($amount + ($i % 2 === 0 ? $amount * 0.12 : 0) - ($amount * 0.02), 2, '.', ''),
                'currency' => 'PHP',
                'exchange_rate' => '1.00',
                'received_by_name' => 'Receiver '.$i,
                'received_by_signature' => 'Receiver '.$i.' Signature',
                'date_received' => Carbon::create(2026, 7, 10)->addDays($i)->toDateString(),
                'line_items' => [[
                    'description' => $source->record_number.' - '.$source->record_title,
                    'account_code' => $chartAccounts[($i - 1) % count($chartAccounts)]->record_number,
                    'debit' => number_format($amount, 2, '.', ''),
                    'credit' => $i % 3 === 0 ? number_format($amount * 0.25, 2, '.', '') : '',
                ], [
                    'description' => 'Cash / bank offset for '.$source->record_number,
                    'account_code' => $bankAccounts[($i - 1) % count($bankAccounts)]->record_number,
                    'debit' => '',
                    'credit' => number_format($amount, 2, '.', ''),
                ]],
                'remarks' => 'DV source '.$sourceType.' scenario '.$i.'.',
            ], $amount);
        }

        return $records;
    }
}
