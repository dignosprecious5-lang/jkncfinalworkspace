<?php

use App\Models\Employee;
use App\Models\FinanceRecord;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function financeGoodsReceivingDependencies(User $owner): array
{
    seedFinanceOfficialApprovers($owner);

    $supplier = FinanceRecord::query()->create([
        'module_key' => 'supplier',
        'record_number' => 'SUP-00021',
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
            'trade_name' => 'Supply Depot',
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $chartAccount = FinanceRecord::query()->create([
        'module_key' => 'chart_account',
        'record_number' => 'CA-00021',
        'record_title' => 'Inventory Asset Account',
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
        'record_number' => 'PO-00021',
        'record_title' => 'Office Supplies Purchase Order',
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
            'total_amount' => 2500,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $custodian = Employee::query()->create([
        'first_name' => 'Inventory',
        'last_name' => 'Keeper',
        'email' => 'keeper@example.com',
        'work_email' => 'keeper@example.com',
        'payroll_type' => 'Monthly Paid',
    ]);

    return compact('supplier', 'po', 'chartAccount', 'custodian');
}

it('records goods receiving quantities and supplier details for consumable inventory', function () {
    $owner = User::factory()->create([
        'name' => 'Goods Receiving Owner',
        'email' => 'goods-receiving@example.com',
        'role' => 'employee',
    ]);
    UserPermission::query()->create([
        'user_id' => $owner->id,
        'access_finance_arf' => true,
    ]);

    $deps = financeGoodsReceivingDependencies($owner);

    $response = $this->actingAs($owner)->post(route('finance.store'), [
        'module_key' => 'arf',
        'record_number' => 'ARF-03001',
        'record_title' => 'Bond Paper Receiving',
        'record_date' => now()->toDateString(),
        'amount' => 2500,
        'status' => 'Active',
        'data' => [
            'item_classification' => 'Consumable Inventory',
            'asset_code' => 'INV-BOND-001',
            'asset_description' => 'Bond paper receiving entry',
            'goods_receiving_reference' => 'GRN-2026-001',
            'linked_po_id' => $deps['po']->id,
            'supplier_id' => $deps['supplier']->id,
            'custodian' => $deps['custodian']->id,
            'location' => 'Main Storage',
            'ordered_quantity' => 100,
            'delivered_quantity' => 95,
            'accepted_quantity' => 90,
            'rejected_quantity' => 5,
            'beginning_quantity' => 30,
            'current_quantity' => 120,
            'reserved_quantity' => 10,
            'reorder_level' => 25,
            'minimum_stock_level' => 20,
            'maximum_stock_level' => 200,
            'safety_stock_level' => 15,
            'unit_cost' => 25,
            'acquisition_cost' => 2500,
            'acquisition_date' => now()->toDateString(),
            'asset_coa_id' => $deps['chartAccount']->id,
            'remarks' => 'Goods receiving regression test.',
        ],
    ]);

    $response->assertCreated();

    $record = FinanceRecord::query()->findOrFail($response->json('data.id'));
    $data = $record->data ?? [];

    expect(data_get($data, 'supplier_id'))->toBe($deps['supplier']->id);
    expect(data_get($data, 'goods_receiving_reference'))->toBe('GRN-2026-001');
    expect(data_get($data, 'ordered_quantity'))->toBe(100);
    expect(data_get($data, 'delivered_quantity'))->toBe(95);
    expect(data_get($data, 'accepted_quantity'))->toBe(90);
    expect(data_get($data, 'rejected_quantity'))->toBe(5);
    expect(data_get($data, 'current_quantity'))->toBe(120);
    expect(data_get($data, 'available_quantity'))->toBe('110.00');
    expect(data_get($data, 'reorder_level'))->toBe(25);
    expect(data_get($data, 'minimum_stock_level'))->toBe(20);
    expect(data_get($data, 'maximum_stock_level'))->toBe(200);
    expect(data_get($data, 'safety_stock_level'))->toBe(15);
    expect(data_get($data, 'unit_cost'))->toBe('25.00');
    expect(data_get($data, 'average_cost'))->toBe('25.00');
    expect(data_get($data, 'last_purchase_cost'))->toBe('25.00');
    expect(data_get($data, 'depreciable_amount'))->toBeNull();
    expect(data_get($data, 'annual_depreciation'))->toBeNull();
    expect(data_get($data, 'monthly_depreciation'))->toBeNull();
});
