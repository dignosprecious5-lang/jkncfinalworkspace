<?php

use App\Models\Employee;
use App\Models\FinanceRecord;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function financeInventoryLogicDependencies(User $owner): array
{
    seedFinanceOfficialApprovers($owner);

    $supplier = FinanceRecord::query()->create([
        'module_key' => 'supplier',
        'record_number' => 'SUP-00031',
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
            'trade_name' => 'Inventory Supply Co.',
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $chartAccount = FinanceRecord::query()->create([
        'module_key' => 'chart_account',
        'record_number' => 'CA-00031',
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
        'record_number' => 'PO-00031',
        'record_title' => 'Inventory Purchase Order',
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
            'total_amount' => 4500,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $custodian = Employee::query()->create([
        'first_name' => 'Inventory',
        'last_name' => 'Custodian',
        'email' => 'inventory-custodian@example.com',
        'work_email' => 'inventory-custodian@example.com',
        'payroll_type' => 'Monthly Paid',
    ]);

    $targetCustodian = Employee::query()->create([
        'first_name' => 'Transferred',
        'last_name' => 'Custodian',
        'email' => 'transfer-custodian@example.com',
        'work_email' => 'transfer-custodian@example.com',
        'payroll_type' => 'Monthly Paid',
    ]);

    return compact('supplier', 'po', 'chartAccount', 'custodian', 'targetCustodian');
}

it('supports inventory movements and keeps the available quantity and total cost formulas in sync', function () {
    $owner = User::factory()->create([
        'name' => 'Inventory Owner',
        'email' => 'inventory-owner@example.com',
        'role' => 'employee',
    ]);
    UserPermission::query()->create([
        'user_id' => $owner->id,
        'access_finance_arf' => true,
    ]);

    $deps = financeInventoryLogicDependencies($owner);

    $createResponse = $this->actingAs($owner)->post(route('finance.store'), [
        'module_key' => 'arf',
        'record_number' => 'ARF-04001',
        'record_title' => 'Cleaning Supplies Stock',
        'record_date' => now()->toDateString(),
        'amount' => 4500,
        'status' => 'Active',
        'data' => [
            'item_classification' => 'Consumable Inventory',
            'asset_code' => 'INV-CLEAN-001',
            'asset_description' => 'Cleaning supplies inventory',
            'linked_po_id' => $deps['po']->id,
            'supplier_id' => $deps['supplier']->id,
            'asset_coa_id' => $deps['chartAccount']->id,
            'custodian' => $deps['custodian']->id,
            'location' => 'Main Warehouse',
            'current_quantity' => 50,
            'reserved_quantity' => 8,
            'unit_cost' => 4.5,
            'reorder_level' => 10,
            'minimum_stock_level' => 6,
            'maximum_stock_level' => 100,
            'safety_stock_level' => 5,
            'ordered_quantity' => 50,
            'delivered_quantity' => 50,
            'accepted_quantity' => 50,
            'rejected_quantity' => 0,
            'goods_receiving_reference' => 'GRN-INV-001',
            'acquisition_cost' => 225,
            'acquisition_date' => now()->toDateString(),
            'remarks' => 'Inventory lifecycle regression test.',
        ],
    ]);

    $createResponse->assertCreated();

    $record = FinanceRecord::query()->findOrFail($createResponse->json('data.id'));
    expect(data_get($record->data, 'available_quantity'))->toBe('42.00');
    expect(data_get($record->data, 'unit_cost'))->toBe('4.50');
    expect(data_get($record->data, 'average_cost'))->toBe('4.50');
    expect(data_get($record->data, 'last_purchase_cost'))->toBe('4.50');
    expect(data_get($record->data, 'total_cost'))->toBe('225.00');

    $stockInResponse = $this->actingAs($owner)->postJson(route('finance.asset.event', $record), [
        'event_type' => 'stock_in',
        'event_reason' => 'Received additional stock',
        'movement_quantity' => 10,
    ]);

    $stockInResponse->assertOk();
    expect(data_get($stockInResponse->json('data.data'), 'current_quantity'))->toBe('60.00');
    $record = $record->fresh();
    expect(data_get($record->data, 'current_quantity'))->toBe('60.00');
    expect(data_get($record->data, 'available_quantity'))->toBe('52.00');
    expect(data_get($record->data, 'unit_cost'))->toBe('4.50');
    expect(data_get($record->data, 'average_cost'))->toBe('4.50');
    expect(data_get($record->data, 'last_purchase_cost'))->toBe('4.50');
    expect(data_get($record->data, 'total_cost'))->toBe('270.00');
    expect(data_get($record->data, 'asset_last_event'))->toBe('Stock In');

    $stockOutResponse = $this->actingAs($owner)->postJson(route('finance.asset.event', $record), [
        'event_type' => 'stock_out',
        'event_reason' => 'Issued stock to operations',
        'movement_quantity' => 12,
    ]);

    $stockOutResponse->assertOk();
    $record = $record->fresh();
    expect(data_get($record->data, 'current_quantity'))->toBe('48.00');
    expect(data_get($record->data, 'available_quantity'))->toBe('40.00');
    expect(data_get($record->data, 'unit_cost'))->toBe('4.50');
    expect(data_get($record->data, 'average_cost'))->toBe('4.50');
    expect(data_get($record->data, 'last_purchase_cost'))->toBe('4.50');
    expect(data_get($record->data, 'total_cost'))->toBe('216.00');
    expect(data_get($record->data, 'asset_last_event'))->toBe('Stock Out');

    $stockTransferResponse = $this->actingAs($owner)->postJson(route('finance.asset.event', $record), [
        'event_type' => 'stock_transfer',
        'event_reason' => 'Moved supplies to branch storage',
        'target_location' => 'Branch Storage',
        'target_department' => 'Operations',
        'target_custodian' => $deps['targetCustodian']->id,
    ]);

    $stockTransferResponse->assertOk();
    $record = $record->fresh();
    expect(data_get($record->data, 'current_quantity'))->toBe('48.00');
    expect(data_get($record->data, 'available_quantity'))->toBe('40.00');
    expect(data_get($record->data, 'location'))->toBe('Branch Storage');
    expect(data_get($record->data, 'department'))->toBe('Operations');
    expect(data_get($record->data, 'custodian'))->toBe($deps['targetCustodian']->id);
    expect(data_get($record->data, 'asset_last_event'))->toBe('Stock Transfer');

    $stockReturnResponse = $this->actingAs($owner)->postJson(route('finance.asset.event', $record), [
        'event_type' => 'stock_return',
        'event_reason' => 'Returned unused supplies',
        'movement_quantity' => 7,
    ]);

    $stockReturnResponse->assertOk();
    $record = $record->fresh();
    expect(data_get($record->data, 'current_quantity'))->toBe('55.00');
    expect(data_get($record->data, 'available_quantity'))->toBe('47.00');
    expect(data_get($record->data, 'unit_cost'))->toBe('4.50');
    expect(data_get($record->data, 'average_cost'))->toBe('4.50');
    expect(data_get($record->data, 'last_purchase_cost'))->toBe('4.50');
    expect(data_get($record->data, 'total_cost'))->toBe('247.50');
    expect(data_get($record->data, 'asset_last_event'))->toBe('Stock Return');

    $stockAdjustmentResponse = $this->actingAs($owner)->postJson(route('finance.asset.event', $record), [
        'event_type' => 'stock_adjustment',
        'event_reason' => 'Adjusted after physical count',
        'movement_quantity' => 42,
    ]);

    $stockAdjustmentResponse->assertOk();
    $record = $record->fresh();
    expect(data_get($record->data, 'current_quantity'))->toBe('42.00');
    expect(data_get($record->data, 'available_quantity'))->toBe('34.00');
    expect(data_get($record->data, 'unit_cost'))->toBe('4.50');
    expect(data_get($record->data, 'average_cost'))->toBe('4.50');
    expect(data_get($record->data, 'last_purchase_cost'))->toBe('4.50');
    expect(data_get($record->data, 'total_cost'))->toBe('189.00');
    expect(data_get($record->data, 'asset_last_event'))->toBe('Stock Adjustment');

    expect(collect((array) data_get($record->data, 'history'))->pluck('action'))->toContain(
        'Stock In',
        'Stock Out',
        'Stock Transfer',
        'Stock Return',
        'Stock Adjustment'
    );
});
