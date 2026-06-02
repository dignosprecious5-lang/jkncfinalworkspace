<?php

use App\Models\Employee;
use App\Models\FinanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function financeAssetDepreciationDependencies(User $owner): array
{
    seedFinanceOfficialApprovers($owner);

    $supplier = FinanceRecord::query()->create([
        'module_key' => 'supplier',
        'record_number' => 'SUP-00011',
        'record_title' => 'Approved Supplier',
        'record_date' => now()->toDateString(),
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $owner->id,
        'approved_at' => now(),
        'data' => [
            'trade_name' => 'Supplier Trading',
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $chartAccount = FinanceRecord::query()->create([
        'module_key' => 'chart_account',
        'record_number' => 'CA-00011',
        'record_title' => 'Asset Account',
        'record_date' => now()->toDateString(),
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $owner->id,
        'approved_at' => now(),
        'data' => [],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $po = FinanceRecord::query()->create([
        'module_key' => 'po',
        'record_number' => 'PO-00011',
        'record_title' => 'Approved Asset Purchase Order',
        'record_date' => now()->toDateString(),
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $owner->id,
        'approved_at' => now(),
        'data' => [
            'supplier_id' => $supplier->id,
            'coa_id' => $chartAccount->id,
            'total_amount' => 1000,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $custodian = Employee::query()->create([
        'first_name' => 'Asset',
        'last_name' => 'Custodian',
        'email' => 'custodian2@example.com',
        'work_email' => 'custodian2@example.com',
        'payroll_type' => 'Monthly Paid',
    ]);

    return compact('supplier', 'po', 'chartAccount', 'custodian');
}

it('calculates straight line depreciation only for fixed assets', function () {
    Storage::fake('public');

    $owner = User::factory()->create([
        'name' => 'Asset Owner',
        'email' => 'owner2@example.com',
        'role' => 'employee',
    ]);

    $deps = financeAssetDepreciationDependencies($owner);
    $response = $this->actingAs($owner)->post(route('finance.store'), [
        'module_key' => 'arf',
        'record_number' => 'ARF-00001',
        'record_title' => 'Laptop Asset',
        'record_date' => now()->toDateString(),
        'amount' => 10000,
        'status' => 'Active',
        'data' => [
            'item_classification' => 'Fixed Asset',
            'asset_code' => 'FA-LAPTOP-001',
            'asset_description' => 'Office laptop',
            'linked_po_id' => $deps['po']->id,
            'custodian' => $deps['custodian']->id,
            'useful_life' => 5,
            'residual_value' => 100,
            'remarks' => 'Depreciation regression test.',
        ],
    ]);

    $response->assertCreated();

    $record = FinanceRecord::query()->findOrFail($response->json('data.id'));
    $data = $record->data ?? [];

    expect(data_get($data, 'useful_life'))->toBe(5);
    expect(data_get($data, 'depreciable_amount'))->toBe('900.00');
    expect(data_get($data, 'annual_depreciation'))->toBe('180.00');
    expect(data_get($data, 'monthly_depreciation'))->toBe('15.00');
    expect(data_get($data, 'accumulated_depreciation'))->toBe('0.00');
    expect(data_get($data, 'net_book_value'))->toBe('1000.00');
});

it('hides depreciation values for consumable inventory', function () {
    Storage::fake('public');

    $owner = User::factory()->create([
        'name' => 'Asset Owner',
        'email' => 'owner3@example.com',
        'role' => 'employee',
    ]);

    $deps = financeAssetDepreciationDependencies($owner);

    $response = $this->actingAs($owner)->post(route('finance.store'), [
        'module_key' => 'arf',
        'record_number' => 'ARF-00002',
        'record_title' => 'Bond Paper Stock',
        'record_date' => now()->toDateString(),
        'amount' => 500,
        'status' => 'Active',
        'data' => [
            'item_classification' => 'Consumable Inventory',
            'asset_code' => 'INV-PAPER-001',
            'asset_description' => 'Bond paper for office use',
            'linked_po_id' => $deps['po']->id,
            'custodian' => $deps['custodian']->id,
            'useful_life' => 3,
            'residual_value' => 50,
            'remarks' => 'Consumable inventory regression test.',
        ],
    ]);

    $response->assertCreated();

    $record = FinanceRecord::query()->findOrFail($response->json('data.id'));
    $data = $record->data ?? [];

    expect(data_get($data, 'useful_life'))->toBeNull();
    expect(data_get($data, 'residual_value'))->toBeNull();
    expect(data_get($data, 'depreciable_amount'))->toBeNull();
    expect(data_get($data, 'annual_depreciation'))->toBeNull();
    expect(data_get($data, 'monthly_depreciation'))->toBeNull();
    expect(data_get($data, 'accumulated_depreciation'))->toBeNull();
    expect(data_get($data, 'net_book_value'))->toBeNull();
});
