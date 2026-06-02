<?php

use App\Models\Employee;
use App\Models\FinanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function financeCustodianLogicDependencies(User $owner): array
{
    seedFinanceOfficialApprovers($owner);

    $supplier = FinanceRecord::query()->create([
        'module_key' => 'supplier',
        'record_number' => 'SUP-00041',
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
            'trade_name' => 'Custody Supply Co.',
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $chartAccount = FinanceRecord::query()->create([
        'module_key' => 'chart_account',
        'record_number' => 'CA-00041',
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
        'record_number' => 'PO-00041',
        'record_title' => 'Asset Purchase Order',
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
            'total_amount' => 6000,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $custodianUser = User::factory()->create([
        'name' => 'Primary Custodian',
        'email' => 'custodian-primary@example.com',
        'role' => 'employee',
    ]);

    $custodianEmployee = Employee::query()->create([
        'user_id' => $custodianUser->id,
        'first_name' => 'Primary',
        'last_name' => 'Custodian',
        'email' => 'custodian-primary@example.com',
        'work_email' => 'custodian-primary@example.com',
        'payroll_type' => 'Monthly Paid',
    ]);

    $newCustodianUser = User::factory()->create([
        'name' => 'Transfer Custodian',
        'email' => 'custodian-transfer@example.com',
        'role' => 'employee',
    ]);

    $newCustodianEmployee = Employee::query()->create([
        'user_id' => $newCustodianUser->id,
        'first_name' => 'Transfer',
        'last_name' => 'Custodian',
        'email' => 'custodian-transfer@example.com',
        'work_email' => 'custodian-transfer@example.com',
        'payroll_type' => 'Monthly Paid',
    ]);

    return compact('supplier', 'po', 'chartAccount', 'custodianUser', 'custodianEmployee', 'newCustodianUser', 'newCustodianEmployee');
}

it('supports custodian assignment transfer and asset condition events', function () {
    $owner = User::factory()->create([
        'name' => 'Asset Owner',
        'email' => 'asset-owner@example.com',
        'role' => 'employee',
    ]);

    $deps = financeCustodianLogicDependencies($owner);

    $response = $this->actingAs($owner)->post(route('finance.store'), [
        'module_key' => 'arf',
        'record_number' => 'ARF-05001',
        'record_title' => 'Custodied Laptop',
        'record_date' => now()->toDateString(),
        'amount' => 6000,
        'status' => 'Active',
        'data' => [
            'item_classification' => 'Fixed Asset',
            'asset_code' => 'FA-LAP-05001',
            'asset_description' => 'Laptop assigned to employee custodian',
            'linked_po_id' => $deps['po']->id,
            'supplier_id' => $deps['supplier']->id,
            'asset_coa_id' => $deps['chartAccount']->id,
            'custodian' => $deps['custodianEmployee']->id,
            'location' => 'IT Office',
            'acquisition_cost' => 6000,
            'acquisition_date' => now()->toDateString(),
            'useful_life' => 5,
            'residual_value' => 200,
            'remarks' => 'Custodian workflow regression test.',
        ],
    ]);

    $response->assertCreated();
    $record = FinanceRecord::query()->findOrFail($response->json('data.id'));

    $ackResponse = $this->actingAs($deps['custodianUser'])->postJson(route('finance.asset.acknowledge', $record));
    $ackResponse->assertOk();
    $record = $record->fresh();
    expect(data_get($record->data, 'custodian_acknowledged_at'))->not()->toBeNull();
    expect(data_get($record->data, 'custodian_acknowledged_by_name'))->toBe('Primary Custodian');
    expect(data_get($record->data, 'received_by_name'))->toBe('Primary Custodian');
    expect(data_get($record->data, 'asset_last_event'))->toBe('Asset Acknowledged');

    $transferResponse = $this->actingAs($owner)->postJson(route('finance.asset.transfer', $record), [
        'new_custodian_id' => $deps['newCustodianEmployee']->id,
        'transfer_reason' => 'Assigned to the replacement custodian.',
    ]);

    $transferResponse->assertOk();
    $record = $record->fresh();
    expect(data_get($record->data, 'custodian'))->toBe($deps['newCustodianEmployee']->id);
    expect(data_get($record->data, 'custodian_name'))->toBe('Transfer Custodian');
    expect(data_get($record->data, 'custodian_acknowledged_at'))->toBeNull();
    expect(data_get($record->data, 'asset_last_event'))->toBe('Asset Transferred');

    $returnResponse = $this->actingAs($owner)->postJson(route('finance.asset.event', $record), [
        'event_type' => 'return',
        'event_reason' => 'Returned for maintenance review.',
    ]);

    $returnResponse->assertOk();
    $record = $record->fresh();
    expect(data_get($record->data, 'asset_last_event'))->toBe('Asset Returned');
    expect(data_get($record->data, 'asset_status'))->toBe('Returned');

    $damageResponse = $this->actingAs($owner)->postJson(route('finance.asset.event', $record), [
        'event_type' => 'damage',
        'event_reason' => 'Screen cracked during use.',
    ]);

    $damageResponse->assertOk();
    $record = $record->fresh();
    expect(data_get($record->data, 'asset_last_event'))->toBe('Asset Reported Damaged');
    expect(data_get($record->data, 'asset_status'))->toBe('Damaged');

    $lossResponse = $this->actingAs($owner)->postJson(route('finance.asset.event', $record), [
        'event_type' => 'loss',
        'event_reason' => 'Asset could not be located during audit.',
    ]);

    $lossResponse->assertOk();
    $record = $record->fresh();
    expect(data_get($record->data, 'asset_last_event'))->toBe('Asset Reported Lost');
    expect(data_get($record->data, 'asset_status'))->toBe('Lost');

    $disposalResponse = $this->actingAs($owner)->postJson(route('finance.asset.event', $record), [
        'event_type' => 'disposal',
        'event_reason' => 'Disposed after end-of-life approval.',
    ]);

    $disposalResponse->assertOk();
    $record = $record->fresh();
    expect(data_get($record->data, 'asset_last_event'))->toBe('Asset Disposed');
    expect(data_get($record->data, 'asset_status'))->toBe('Disposed');
    expect(collect((array) data_get($record->data, 'history'))->pluck('action'))->toContain(
        'Asset Acknowledged',
        'Asset Transferred',
        'Asset Returned',
        'Asset Reported Damaged',
        'Asset Reported Lost',
        'Asset Disposed'
    );
});
