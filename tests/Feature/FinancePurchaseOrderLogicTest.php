<?php

use App\Models\FinanceRecord;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function financePurchaseOrderFixtures(): array
{
    $owner = User::factory()->create([
        'name' => 'PO Requester',
        'email' => 'po.requester@example.com',
        'role' => 'employee',
    ]);
    UserPermission::query()->updateOrCreate(
        ['user_id' => $owner->id],
        [
            'access_finance_pr' => true,
            'access_finance_po' => true,
            'access_finance_dv' => true,
            'access_finance_supplier' => true,
            'access_finance_service' => true,
            'access_finance_chart_account' => true,
        ]
    );

    $president = User::factory()->create([
        'name' => 'PO President',
        'email' => 'po.president@example.com',
        'role' => 'admin',
    ]);
    \App\Models\Employee::query()->create([
        'user_id' => $president->id,
        'first_name' => 'PO',
        'last_name' => 'President',
        'email' => 'po.president@example.com',
        'position' => 'President',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $treasurer = User::factory()->create([
        'name' => 'PO Treasurer',
        'email' => 'po.treasurer@example.com',
        'role' => 'admin',
    ]);
    \App\Models\Employee::query()->create([
        'user_id' => $treasurer->id,
        'first_name' => 'PO',
        'last_name' => 'Treasurer',
        'email' => 'po.treasurer@example.com',
        'position' => 'Treasurer',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $gisRecord = \App\Models\GisRecord::query()->create([
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

    \App\Models\DirectorOfficer::query()->create([
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

    \App\Models\DirectorOfficer::query()->create([
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
        'record_title' => 'Approved Supplier',
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

    $chartAccount = FinanceRecord::query()->create([
        'module_key' => 'chart_account',
        'record_number' => 'COA-00001',
        'record_title' => 'Procurement Expense',
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

    $bankAccount = FinanceRecord::query()->create([
        'module_key' => 'bank_account',
        'record_number' => 'BA-00001',
        'record_title' => 'Procurement Bank Account',
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

    $service = FinanceRecord::query()->create([
        'module_key' => 'service',
        'record_number' => 'SRV-00001',
        'record_title' => 'Approved Service Item',
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

    $approvedPr = FinanceRecord::query()->create([
        'module_key' => 'pr',
        'record_number' => 'PR-00001',
        'record_title' => 'Office Supplies Request',
        'record_date' => now()->toDateString(),
        'amount' => 1500,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $president->id,
        'approved_at' => now(),
        'data' => [
            'requester_mode' => 'own_request',
            'requestor' => $owner->name,
            'request_type' => 'Service',
            'priority' => 'Urgent',
            'needed_date' => now()->addDays(7)->toDateString(),
            'purpose' => 'Need support for upcoming office procurement.',
            'master_item_type' => 'service',
            'master_item_id' => $service->id,
            'estimated_total_cost' => 1500,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $draftPr = FinanceRecord::query()->create([
        'module_key' => 'pr',
        'record_number' => 'PR-00002',
        'record_title' => 'Draft Office Supplies Request',
        'record_date' => now()->toDateString(),
        'amount' => 500,
        'status' => 'Active',
        'workflow_status' => 'Uploaded',
        'approval_status' => 'Pending',
        'submitted_by' => $owner->id,
        'submitted_at' => null,
        'approved_by' => null,
        'approved_at' => null,
        'data' => [
            'requestor' => $owner->name,
            'master_item_type' => 'service',
            'master_item_id' => $service->id,
            'estimated_total_cost' => 500,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    return compact('owner', 'president', 'treasurer', 'supplier', 'chartAccount', 'bankAccount', 'service', 'approvedPr', 'draftPr', 'gisRecord');
}

test('purchase orders must link to an approved purchase request and promote the request into procurement', function () {
    $fixtures = financePurchaseOrderFixtures();

    $invalidResponse = $this->actingAs($fixtures['owner'])->postJson(route('finance.store'), [
        'module_key' => 'po',
        'record_number' => 'PO-00001',
        'record_title' => 'Draft-linked PO',
        'record_date' => now()->toDateString(),
        'amount' => 1500,
        'status' => 'Active',
        'data' => [
            'linked_pr_id' => $fixtures['draftPr']->id,
            'supplier_id' => $fixtures['supplier']->id,
            'linked_item_type' => 'service',
            'linked_item_id' => $fixtures['service']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);

    $invalidResponse->assertStatus(422);
    $invalidResponse->assertJsonValidationErrors(['data.linked_pr_id']);

    $createResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'po',
        'record_number' => 'PO-00002',
        'record_title' => 'Approved PR Purchase Order',
        'record_date' => now()->toDateString(),
        'amount' => 1500,
        'status' => 'Active',
        'data' => [
            'linked_pr_id' => $fixtures['approvedPr']->id,
            'supplier_id' => $fixtures['supplier']->id,
            'linked_item_type' => 'service',
            'linked_item_id' => $fixtures['service']->id,
            'coa_id' => $fixtures['chartAccount']->id,
            'quantity' => 3,
            'unit_cost' => 500,
            'total_amount' => 1500,
        ],
    ]);

    $createResponse->assertCreated();

    $po = FinanceRecord::query()->findOrFail($createResponse->json('data.id'));
    $fixtures['approvedPr']->refresh();
    expect(data_get($po->data, 'linked_pr_id'))->toBe($fixtures['approvedPr']->id);
    expect(data_get($fixtures['approvedPr']->data, 'relationship_status'))->toBe('Converted to Purchase Order');
    expect(data_get($fixtures['approvedPr']->data, 'next_action'))->toBe('Approve Purchase Order');

    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $po))->assertOk();
    $this->actingAs($fixtures['president'])->postJson(route('finance.approve', $po))->assertOk();
    $this->actingAs($fixtures['treasurer'])->postJson(route('finance.approve', $po))->assertOk();

    $po->refresh();
    $fixtures['approvedPr']->refresh();

    expect($po->workflow_status)->toBe('Accepted');
    expect($po->approval_status)->toBe('Approved');
    expect(data_get($po->data, 'relationship_status'))->toBe('Awaiting Disbursement');
    expect(data_get($po->data, 'next_action'))->toBe('Create Disbursement Voucher');
    expect(data_get($fixtures['approvedPr']->data, 'relationship_status'))->toBe('Purchase Order Approved');
    expect(data_get($fixtures['approvedPr']->data, 'next_action'))->toBe('Create Disbursement Voucher');

    $dvResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-00001',
        'record_title' => 'PO Payment Voucher',
        'record_date' => now()->toDateString(),
        'amount' => 1500,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'po',
            'source_document_id' => $po->id,
            'amount' => 1500,
            'payment_type' => 'Cash',
            'disbursement_type' => 'Cash',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
            'supplier_id' => $fixtures['supplier']->id,
            'purpose' => 'Payment for approved purchase order.',
        ],
    ]);

    $dvResponse->assertCreated();

    $dv = FinanceRecord::query()->findOrFail($dvResponse->json('data.id'));
    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $dv))->assertOk();
    $this->actingAs($fixtures['president'])->postJson(route('finance.approve', $dv))->assertOk();
    $this->actingAs($fixtures['treasurer'])->postJson(route('finance.approve', $dv))->assertOk();

    $dv->refresh();
    expect($dv->workflow_status)->toBe('Accepted');
    expect($dv->approval_status)->toBe('Approved');
    expect(data_get($dv->data, 'relationship_status'))->toBe('Approved');
    expect(data_get($dv->data, 'next_action'))->toBe('Release Funds');

    $dvUpdateResponse = $this->actingAs($fixtures['president'])->post(route('finance.update', $dv), [
        'module_key' => 'dv',
        '_method' => 'PUT',
        'record_number' => $dv->record_number,
        'record_title' => $dv->record_title,
        'record_date' => $dv->record_date?->toDateString() ?: now()->toDateString(),
        'amount' => $dv->amount,
        'status' => 'Released',
        'existing_attachments_json' => json_encode($dv->attachments ?? [], JSON_THROW_ON_ERROR),
        'data' => array_merge((array) $dv->data, [
            'source_document_type' => 'po',
            'source_document_id' => $po->id,
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
            'supplier_id' => $fixtures['supplier']->id,
            'line_items' => [[
                'description' => 'Payment for approved purchase order.',
                'account_code' => '7010',
                'debit' => 1500,
                'credit' => 0,
            ]],
        ]),
    ]);

    $dvUpdateResponse->assertOk();

    $dv->refresh();
    $po->refresh();
    $fixtures['approvedPr']->refresh();

    expect($dv->status)->toBe('Disbursed');
    expect(data_get($dv->data, 'relationship_status'))->toBe('Disbursed');
    expect(data_get($dv->data, 'next_action'))->toBe('No further action');
    expect(data_get($po->data, 'relationship_status'))->toBe('Disbursed');
    expect(data_get($po->data, 'next_action'))->toBe('No further action');
    expect(data_get($fixtures['approvedPr']->data, 'relationship_status'))->toBe('Disbursed');
    expect(data_get($fixtures['approvedPr']->data, 'next_action'))->toBe('No further action');
});

test('status input is ignored and purchase orders use system-controlled status values', function () {
    $fixtures = financePurchaseOrderFixtures();

    $response = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'po',
        'record_number' => 'PO-09001',
        'record_title' => 'Status Control Purchase Order',
        'record_date' => now()->toDateString(),
        'amount' => 1500,
        'status' => 'Released',
        'data' => [
            'relationship_status' => 'Completed',
            'linked_pr_id' => $fixtures['approvedPr']->id,
            'supplier_id' => $fixtures['supplier']->id,
            'linked_item_type' => 'service',
            'linked_item_id' => $fixtures['service']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);

    $response->assertCreated();

    $po = FinanceRecord::query()->findOrFail($response->json('data.id'));

    expect($response->json('data.status'))->toBe('Draft');
    expect($response->json('data.relationship_status'))->toBe('Draft');
    expect($po->status)->toBe('Draft');
    expect($po->workflow_status)->toBe('Uploaded');
    expect(data_get($po->data, 'relationship_status'))->toBe('Draft');
});
