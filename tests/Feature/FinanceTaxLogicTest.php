<?php

use App\Models\DirectorOfficer;
use App\Models\Employee;
use App\Models\FinanceRecord;
use App\Models\GisRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function financeTaxLogicFixtures(): array
{
    $owner = User::factory()->create([
        'name' => 'Tax Requester',
        'email' => 'tax.requester@example.com',
        'role' => 'employee',
    ]);

    $president = User::factory()->create([
        'name' => 'Tax President',
        'email' => 'tax.president@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $president->id,
        'first_name' => 'Tax',
        'last_name' => 'President',
        'email' => 'tax.president@example.com',
        'position' => 'President',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $treasurer = User::factory()->create([
        'name' => 'Tax Treasurer',
        'email' => 'tax.treasurer@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $treasurer->id,
        'first_name' => 'Tax',
        'last_name' => 'Treasurer',
        'email' => 'tax.treasurer@example.com',
        'position' => 'Treasurer',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $gisRecord = GisRecord::query()->create([
        'uploaded_by' => $owner->name,
        'submission_status' => 'Submitted',
        'receive_on' => now()->toDateString(),
        'period_date' => now()->format('Y'),
        'corporation_name' => 'JKNC Holdings, Inc.',
        'trade_name' => 'JKNC',
        'approval_status' => 'Approved',
        'workflow_status' => 'Accepted',
        'submitted_by' => $owner->id,
        'approved_by' => $owner->id,
        'approved_at' => now(),
    ]);

    DirectorOfficer::query()->create([
        'gis_id' => $gisRecord->id,
        'officer_name' => $president->name,
        'address' => 'Test Address',
        'gender' => 'Male',
        'nationality' => 'Filipino',
        'incr' => false,
        'stockholder' => false,
        'board' => 'Board',
        'officer_type' => 'President',
        'committee' => 'N/A',
        'tin' => '000-000-000',
    ]);

    DirectorOfficer::query()->create([
        'gis_id' => $gisRecord->id,
        'officer_name' => $treasurer->name,
        'address' => 'Test Address',
        'gender' => 'Female',
        'nationality' => 'Filipino',
        'incr' => false,
        'stockholder' => false,
        'board' => 'Board',
        'officer_type' => 'Treasurer',
        'committee' => 'N/A',
        'tin' => '111-111-111',
    ]);

    $supplier = FinanceRecord::query()->create([
        'module_key' => 'supplier',
        'record_number' => 'SUP-00001',
        'record_title' => 'Tax Supplier',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $owner->id,
        'approved_at' => now(),
        'data' => [
            'completion_mode' => 'complete_internally',
            'email_address' => 'supplier@example.com',
            'representative_full_name' => 'Supplier Rep',
            'phone_number' => '09170000000',
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $service = FinanceRecord::query()->create([
        'module_key' => 'service',
        'record_number' => 'SRV-00001',
        'record_title' => 'Taxable Service',
        'record_date' => now()->toDateString(),
        'amount' => 1000,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $owner->id,
        'approved_at' => now(),
        'data' => [
            'supplier_id' => $supplier->id,
            'coa_id' => null,
            'default_cost' => 1000,
            'tax_type' => 'VAT',
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $product = FinanceRecord::query()->create([
        'module_key' => 'product',
        'record_number' => 'PRD-00001',
        'record_title' => 'Taxable Product',
        'record_date' => now()->toDateString(),
        'amount' => 1000,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $owner->id,
        'approved_at' => now(),
        'data' => [
            'supplier_id' => $supplier->id,
            'coa_id' => null,
            'default_cost' => 1000,
            'tax_type' => 'VAT',
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    return compact('owner', 'president', 'treasurer', 'service', 'product');
}

test('tax classifications compute VAT, VAT exempt, zero rated, and expanded withholding tax impacts automatically', function () {
    $fixtures = financeTaxLogicFixtures();

    $response = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'pr',
        'record_number' => 'PR-90001',
        'record_title' => 'Mixed Tax Purchase Request',
        'record_date' => now()->toDateString(),
        'amount' => 4110,
        'status' => 'Active',
        'data' => [
            'requesting_department' => 'Operations',
            'requester_mode' => 'own_request',
            'requestor' => $fixtures['owner']->name,
            'department' => 'Operations',
            'request_type' => 'Product',
            'priority' => 'Normal',
            'needed_date' => now()->addWeek()->toDateString(),
            'purpose' => 'Verify automatic tax impacts across supported tax classes.',
            'master_item_type' => 'product',
            'master_item_id' => $fixtures['product']->id,
            'estimated_total_cost' => 4110,
            'line_items' => [
                [
                    'item_module' => 'service',
                    'item_record_id' => $fixtures['service']->id,
                    'item_id' => (string) $fixtures['service']->id,
                    'description' => 'VAT taxable service',
                    'category' => 'Service',
                    'quantity' => 1,
                    'amount' => 1000,
                    'discount' => '0%',
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'tax_type' => 'VAT',
                ],
                [
                    'item_module' => 'product',
                    'item_record_id' => $fixtures['product']->id,
                    'item_id' => (string) $fixtures['product']->id,
                    'description' => 'VAT exempt product',
                    'category' => 'Product',
                    'quantity' => 1,
                    'amount' => 1000,
                    'discount' => '0%',
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'tax_type' => 'VAT Exempt',
                ],
                [
                    'item_module' => 'product',
                    'item_record_id' => $fixtures['product']->id,
                    'item_id' => (string) $fixtures['product']->id,
                    'description' => 'Zero rated product',
                    'category' => 'Product',
                    'quantity' => 1,
                    'amount' => 1000,
                    'discount' => '0%',
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'tax_type' => 'Zero Rated',
                ],
                [
                    'item_module' => 'service',
                    'item_record_id' => $fixtures['service']->id,
                    'item_id' => (string) $fixtures['service']->id,
                    'description' => 'Expanded withholding tax service',
                    'category' => 'Service',
                    'quantity' => 1,
                    'amount' => 1000,
                    'discount' => '0%',
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'tax_type' => 'Expanded Withholding Tax',
                ],
            ],
        ],
    ]);

    $response->assertCreated();

    $record = FinanceRecord::query()->findOrFail($response->json('data.id'));

    expect(data_get($record->data, 'tax_total'))->toBe('120.00');
    expect(data_get($record->data, 'wht_total'))->toBe('10.00');
    expect(data_get($record->data, 'grand_total'))->toBe('4110.00');
    expect(data_get($record->data, 'estimated_total_cost'))->toBe('4110.00');

    expect(data_get($record->data, 'line_items.0.tax_type'))->toBe('VAT');
    expect(data_get($record->data, 'line_items.0.tax_amount'))->toBe('120.00');
    expect(data_get($record->data, 'line_items.0.wht_amount'))->toBe('0.00');
    expect(data_get($record->data, 'line_items.0.tax_impact_label'))->toBe('VAT 12%');
    expect(data_get($record->data, 'line_items.0.total'))->toBe('1120.00');

    expect(data_get($record->data, 'line_items.1.tax_type'))->toBe('VAT Exempt');
    expect(data_get($record->data, 'line_items.1.tax_amount'))->toBe('0.00');
    expect(data_get($record->data, 'line_items.1.wht_amount'))->toBe('0.00');
    expect(data_get($record->data, 'line_items.1.tax_impact_label'))->toBe('VAT Exempt');
    expect(data_get($record->data, 'line_items.1.total'))->toBe('1000.00');

    expect(data_get($record->data, 'line_items.2.tax_type'))->toBe('Zero Rated');
    expect(data_get($record->data, 'line_items.2.tax_amount'))->toBe('0.00');
    expect(data_get($record->data, 'line_items.2.wht_amount'))->toBe('0.00');
    expect(data_get($record->data, 'line_items.2.tax_impact_label'))->toBe('Zero Rated');
    expect(data_get($record->data, 'line_items.2.total'))->toBe('1000.00');

    expect(data_get($record->data, 'line_items.3.tax_type'))->toBe('Expanded Withholding Tax');
    expect(data_get($record->data, 'line_items.3.tax_amount'))->toBe('0.00');
    expect(data_get($record->data, 'line_items.3.wht_amount'))->toBe('10.00');
    expect(data_get($record->data, 'line_items.3.tax_impact_label'))->toBe('Expanded Withholding Tax 1%');
    expect(data_get($record->data, 'line_items.3.total'))->toBe('990.00');
});
