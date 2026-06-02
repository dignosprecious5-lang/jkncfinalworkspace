<?php

use App\Models\FinanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function financeDisbursementVoucherFixtures(): array
{
    $owner = User::factory()->create([
        'name' => 'DV Requester',
        'email' => 'dv.requester@example.com',
        'role' => 'employee',
    ]);

    $president = User::factory()->create([
        'name' => 'DV President',
        'email' => 'dv.president@example.com',
        'role' => 'admin',
    ]);
    \App\Models\Employee::query()->create([
        'user_id' => $president->id,
        'first_name' => 'DV',
        'last_name' => 'President',
        'email' => 'dv.president@example.com',
        'position' => 'President',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $treasurer = User::factory()->create([
        'name' => 'DV Treasurer',
        'email' => 'dv.treasurer@example.com',
        'role' => 'admin',
    ]);
    \App\Models\Employee::query()->create([
        'user_id' => $treasurer->id,
        'first_name' => 'DV',
        'last_name' => 'Treasurer',
        'email' => 'dv.treasurer@example.com',
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

    $bankAccount = FinanceRecord::query()->create([
        'module_key' => 'bank_account',
        'record_number' => 'BA-00001',
        'record_title' => 'Approved Bank Account',
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
        'record_title' => 'Approved Payment Account',
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

    $sources = collect([
        'po' => 'Approved Purchase Order',
        'ca' => 'Approved Cash Advance',
        'err' => 'Approved Expense Reimbursement',
        'pda' => 'Approved Payroll Disbursement Authorization',
        'ibtf' => 'Approved Interbank Fund Transfer',
    ])->mapWithKeys(function (string $title, string $moduleKey) use ($owner) {
        $record = FinanceRecord::query()->create([
            'module_key' => $moduleKey,
            'record_number' => strtoupper($moduleKey) . '-00001',
            'record_title' => $title,
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
                'purpose' => $title,
            ],
            'attachments' => [],
            'user' => $owner->name,
        ]);

        return [$moduleKey => $record];
    })->all();

    $draftPr = FinanceRecord::query()->create([
        'module_key' => 'pr',
        'record_number' => 'PR-00001',
        'record_title' => 'Draft Purchase Request',
        'record_date' => now()->toDateString(),
        'amount' => 1000,
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
            'master_item_id' => 1,
            'estimated_total_cost' => 1000,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    return compact('owner', 'president', 'treasurer', 'gisRecord', 'bankAccount', 'chartAccount', 'sources', 'draftPr');
}

test('disbursement vouchers reject purchase requests and only accept approved source documents', function () {
    $fixtures = financeDisbursementVoucherFixtures();

    $invalidResponse = $this->actingAs($fixtures['owner'])->postJson(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-00001',
        'record_title' => 'DV From PR',
        'record_date' => now()->toDateString(),
        'amount' => 1000,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'pr',
            'source_document_id' => $fixtures['draftPr']->id,
            'amount' => 1000,
            'payment_type' => 'Cash',
            'disbursement_type' => 'Cash',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);

    $invalidResponse->assertStatus(422);
    $invalidResponse->assertJsonValidationErrors(['data.source_document_type']);

    foreach (['po', 'ca', 'err', 'pda', 'ibtf'] as $sourceType) {
        $sourceRecord = $fixtures['sources'][$sourceType];

        $response = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
            'module_key' => 'dv',
            'record_number' => 'DV-1000' . (array_search($sourceType, ['po', 'ca', 'err', 'pda', 'ibtf'], true) + 1),
            'record_title' => 'DV From ' . strtoupper($sourceType),
            'record_date' => now()->toDateString(),
            'amount' => 1000,
            'status' => 'Active',
            'data' => [
                'source_document_type' => $sourceType,
                'source_document_id' => $sourceRecord->id,
                'amount' => 1000,
                'payment_type' => 'Cash',
                'disbursement_type' => 'Cash',
                'bank_account_id' => $fixtures['bankAccount']->id,
                'coa_id' => $fixtures['chartAccount']->id,
                'purpose' => 'Voucher for ' . $sourceType,
            ],
        ]);

        $response->assertCreated();

        $record = FinanceRecord::query()->findOrFail($response->json('data.id'));
        expect(data_get($record->data, 'source_document_type'))->toBe($sourceType);
        expect((string) data_get($record->data, 'source_document_id'))->toBe((string) $sourceRecord->id);
        expect($record->module_key)->toBe('dv');
    }
});

test('disbursement vouchers require an approved source document before they can be created', function () {
    $fixtures = financeDisbursementVoucherFixtures();

    $missingSourceResponse = $this->actingAs($fixtures['owner'])->postJson(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-00099',
        'record_title' => 'DV Without Source',
        'record_date' => now()->toDateString(),
        'amount' => 1000,
        'status' => 'Active',
        'data' => [
            'amount' => 1000,
            'payment_type' => 'Cash',
            'disbursement_type' => 'Cash',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);

    $missingSourceResponse->assertStatus(422);
    $missingSourceResponse->assertJsonValidationErrors(['data.source_document_type', 'data.source_document_id']);

    $draftSourceResponse = $this->actingAs($fixtures['owner'])->postJson(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-00100',
        'record_title' => 'DV From Draft PR',
        'record_date' => now()->toDateString(),
        'amount' => 1000,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'pr',
            'source_document_id' => $fixtures['draftPr']->id,
            'amount' => 1000,
            'payment_type' => 'Cash',
            'disbursement_type' => 'Cash',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);

    $draftSourceResponse->assertStatus(422);
    $draftSourceResponse->assertJsonValidationErrors(['data.source_document_type']);
});
