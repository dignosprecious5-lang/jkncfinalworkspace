<?php

use App\Models\DirectorOfficer;
use App\Models\Employee;
use App\Models\FinanceRecord;
use App\Models\GisRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function financePurchaseRequestFixtures(): array
{
    $owner = User::factory()->create([
        'name' => 'PR Requester',
        'email' => 'pr.requester@example.com',
        'role' => 'employee',
    ]);

    $president = User::factory()->create([
        'name' => 'PR President',
        'email' => 'pr.president@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $president->id,
        'first_name' => 'PR',
        'last_name' => 'President',
        'email' => 'pr.president@example.com',
        'position' => 'President',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $treasurer = User::factory()->create([
        'name' => 'PR Treasurer',
        'email' => 'pr.treasurer@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $treasurer->id,
        'first_name' => 'PR',
        'last_name' => 'Treasurer',
        'email' => 'pr.treasurer@example.com',
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

    $service = FinanceRecord::query()->create([
        'module_key' => 'service',
        'record_number' => 'SRV-00001',
        'record_title' => 'Approved Support Service',
        'record_date' => now()->toDateString(),
        'amount' => 0,
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

    return compact('owner', 'president', 'treasurer', 'gisRecord', 'service');
}

test('purchase requests require the business need fields and stay separate from payment authorization', function () {
    $fixtures = financePurchaseRequestFixtures();

    $invalidResponse = $this->actingAs($fixtures['owner'])->postJson(route('finance.store'), [
        'module_key' => 'pr',
        'record_number' => 'PR-00001',
        'record_title' => 'Office Supplies Request',
        'record_date' => now()->toDateString(),
        'amount' => 1500,
        'status' => 'Active',
        'data' => [
            'requester_mode' => 'own_request',
            'requestor' => $fixtures['owner']->name,
        ],
    ]);

    $invalidResponse->assertStatus(422);
    $invalidResponse->assertJsonValidationErrors([
        'data.master_item_type',
        'data.master_item_id',
        'data.estimated_total_cost',
        'data.priority',
        'data.needed_date',
        'data.purpose',
    ]);

    $validResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'pr',
        'record_number' => 'PR-00002',
        'record_title' => 'Office Supplies Request',
        'record_date' => now()->toDateString(),
        'amount' => 1500,
        'status' => 'Active',
        'data' => [
            'requesting_department' => 'Operations',
            'requester_mode' => 'own_request',
            'requestor' => $fixtures['owner']->name,
            'department' => 'Operations',
            'request_type' => 'Service',
            'priority' => 'Urgent',
            'needed_date' => now()->addWeek()->toDateString(),
            'purpose' => 'Need support for office printing supplies.',
            'master_item_type' => 'service',
            'master_item_id' => $fixtures['service']->id,
            'estimated_total_cost' => 1500,
            'quantity' => 3,
            'unit_cost' => 500,
        ],
    ]);

    $validResponse->assertCreated();

    $record = FinanceRecord::query()->findOrFail($validResponse->json('data.id'));
    expect($record->module_key)->toBe('pr');
    expect(data_get($record->data, 'priority'))->toBe('Urgent');
    expect(data_get($record->data, 'needed_date'))->toBe(now()->addWeek()->toDateString());
    expect(data_get($record->data, 'purpose'))->toBe('Need support for office printing supplies.');
    expect((float) data_get($record->data, 'estimated_total_cost'))->toBe(1500.0);
    expect(data_get($record->data, 'master_item_type'))->toBe('service');
    expect((int) data_get($record->data, 'master_item_id'))->toBe((int) $fixtures['service']->id);
    expect(data_get($record->data, 'first_approver_user_id'))->toBe($fixtures['president']->id);
    expect(data_get($record->data, 'second_approver_user_id'))->toBe($fixtures['treasurer']->id);
    expect(data_get($record->data, 'approval_steps.0.user_id'))->toBe($fixtures['president']->id);
    expect(data_get($record->data, 'approval_steps.1.user_id'))->toBe($fixtures['treasurer']->id);
    expect(data_get($record->data, 'payment_type'))->toBeNull();
    expect(data_get($record->data, 'ca_payment_entries'))->toBeNull();
    expect(data_get($record->data, 'linked_po_id'))->toBeNull();
});
