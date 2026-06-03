<?php

use App\Models\FinanceRecord;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPermission;
use App\Notifications\FinanceRecordWorkflowNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function financeDisbursementVoucherFixtures(): array
{
    $supportingAttachment = [
        [
            'name' => 'supporting-document.pdf',
            'path' => 'storage/testing/supporting-document.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'category' => 'Supporting Document',
            'status' => 'uploaded',
            'uploaded_at' => now()->format('Y-m-d H:i:s'),
            'uploaded_by' => 'DV Requester',
        ],
    ];

    $owner = User::factory()->create([
        'name' => 'DV Requester',
        'email' => 'dv.requester@example.com',
        'role' => 'employee',
    ]);
    UserPermission::query()->updateOrCreate(
        ['user_id' => $owner->id],
        [
            'access_finance_ca' => true,
            'access_finance_dv' => true,
            'access_finance_bank_account' => true,
            'access_finance_chart_account' => true,
        ]
    );

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

    $sourceSeeds = [
        'po' => [
            'title' => 'Approved Purchase Order',
            'data' => [
                'requestor' => 'Procurement Requester',
                'department' => 'Procurement',
                'project' => 'Project Alpha',
                'cost_center' => 'CC-001',
                'fund_source' => 'Corporate Funds',
                'supplier_name' => 'Approved Supplier Co.',
            ],
        ],
        'ca' => [
            'title' => 'Approved Cash Advance',
            'data' => [
                'requestor' => 'Cash Advance Requester',
                'employee_name' => 'Cash Advance Requester',
                'department' => 'Field Operations',
                'project' => 'Project Beta',
                'cost_center' => 'CC-002',
                'fund_source' => 'Operating Funds',
                'ca_payment_remaining_balance' => '250.00',
                'remaining_balance' => '250.00',
            ],
        ],
        'err' => [
            'title' => 'Approved Expense Reimbursement',
            'data' => [
                'requestor' => 'Reimbursement Requester',
                'employee_name' => 'Reimbursement Requester',
                'department' => 'Finance',
                'project' => 'Project Gamma',
                'cost_center' => 'CC-003',
                'fund_source' => 'Expense Budget',
            ],
        ],
        'pda' => [
            'title' => 'Approved Payroll Disbursement Authorization',
            'data' => [
                'requestor' => 'Payroll Requester',
                'employee_name' => 'Payroll Requester',
                'department' => 'Payroll',
                'project' => 'Project Payroll',
                'cost_center' => 'CC-004',
                'fund_source' => 'Payroll Fund',
                'payroll_period_label' => 'Approved Payroll Group',
            ],
        ],
        'ibtf' => [
            'title' => 'Approved Interbank Fund Transfer',
            'data' => [
                'requestor' => 'Treasury Requester',
                'department' => 'Treasury',
                'project' => 'Project Delta',
                'cost_center' => 'CC-005',
                'fund_source' => 'Treasury Fund',
                'destination_bank_account_id' => $bankAccount->id,
            ],
        ],
    ];

    $sources = collect($sourceSeeds)->mapWithKeys(function (array $seed, string $moduleKey) use ($owner, $supportingAttachment) {
        $record = FinanceRecord::query()->create([
            'module_key' => $moduleKey,
            'record_number' => strtoupper($moduleKey) . '-00001',
            'record_title' => $seed['title'],
            'record_date' => now()->toDateString(),
            'amount' => 1000,
            'status' => 'Active',
            'workflow_status' => 'Accepted',
            'approval_status' => 'Approved',
            'submitted_by' => $owner->id,
            'submitted_at' => now(),
            'approved_by' => $owner->id,
            'approved_at' => now(),
        'data' => array_merge([
                'purpose' => $seed['title'],
            ], $seed['data'] ?? []),
            'attachments' => $supportingAttachment,
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

function financeSetDvInsufficientFundsPolicy(bool $allow): void
{
    Setting::query()->updateOrCreate(
        ['key' => 'finance_allow_dv_approval_with_insufficient_funds'],
        ['value' => json_encode(['allow' => $allow], JSON_UNESCAPED_SLASHES)]
    );
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

test('disbursement vouchers auto-populate source snapshots and ignore conflicting user values', function () {
    $fixtures = financeDisbursementVoucherFixtures();
    $sourceRecord = $fixtures['sources']['ca'];

    $response = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-00101',
        'record_title' => 'DV Snapshot Test',
        'record_date' => now()->toDateString(),
        'amount' => 9999,
        'status' => 'Released',
        'data' => [
            'source_document_type' => 'ca',
            'source_document_id' => $sourceRecord->id,
            'source_record_number' => 'OVERRIDE-SOURCE',
            'source_record_date' => '2000-01-01',
            'source_requester' => 'Override Requester',
            'source_department' => 'Override Department',
            'source_project' => 'Override Project',
            'source_cost_center' => 'OV-001',
            'source_fund_source' => 'Override Funds',
            'source_amount' => '123.00',
            'source_remaining_balance' => '0.00',
            'source_approval_status' => 'Pending',
            'source_approved_by_name' => 'Override Approver',
            'source_approved_at' => '2000-01-01 00:00:00',
            'source_supplier_name' => 'Override Supplier',
            'source_employee_name' => 'Override Employee',
            'source_payee_type' => 'Override Payee Type',
            'source_payee_name' => 'Override Payee Name',
            'source_status' => 'Cancelled',
            'source_workflow_status' => 'Draft',
            'source_relationship_status' => 'Awaiting Disbursement',
            'payee_type' => 'Override Payee Type',
            'payee_name' => 'Override Payee Name',
            'amount' => 9999,
            'payment_type' => 'Cash',
            'disbursement_type' => 'Cash',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);

    $response->assertCreated();

    $record = FinanceRecord::query()->findOrFail($response->json('data.id'));
    expect(data_get($record->data, 'source_record_number'))->toBe('CA-00001');
    expect(data_get($record->data, 'source_record_date'))->toBe(now()->toDateString());
    expect(data_get($record->data, 'source_requester'))->toBe('Cash Advance Requester');
    expect(data_get($record->data, 'source_department'))->toBe('Field Operations');
    expect(data_get($record->data, 'source_project'))->toBe('Project Beta');
    expect(data_get($record->data, 'source_cost_center'))->toBe('CC-002');
    expect(data_get($record->data, 'source_fund_source'))->toBe('Operating Funds');
    expect(data_get($record->data, 'source_amount'))->toBe('1000.00');
    expect(data_get($record->data, 'source_remaining_balance'))->toBe('250.00');
    expect(data_get($record->data, 'source_current_balance'))->toBe('1000.00');
    expect(data_get($record->data, 'source_reserved_balance'))->toBe('750.00');
    expect(data_get($record->data, 'source_available_balance'))->toBe('250.00');
    expect(data_get($record->data, 'projected_balance_after_payment'))->toBe('0.00');
    expect(data_get($record->data, 'source_approval_status'))->toBe('Approved');
    expect(data_get($record->data, 'source_approved_by_name'))->toBe('DV Requester');
    expect(data_get($record->data, 'source_supplier_name'))->toBe('');
    expect(data_get($record->data, 'source_employee_name'))->toBe('Cash Advance Requester');
    expect(data_get($record->data, 'source_payee_type'))->toBe('Employee');
    expect(data_get($record->data, 'source_payee_name'))->toBe('Cash Advance Requester');
    expect(data_get($record->data, 'payee_type'))->toBe('Employee');
    expect(data_get($record->data, 'payee_name'))->toBe('Cash Advance Requester');
    expect(data_get($record->data, 'source_status'))->toBe('Awaiting Disbursement');
    expect(data_get($record->data, 'source_workflow_status'))->toBe('Accepted');
    expect(data_get($record->data, 'source_relationship_status'))->toBe('Awaiting Disbursement');
    expect((float) data_get($record->data, 'amount'))->toBe(9999.0);
    expect((float) data_get($record->data, 'source_amount'))->toBe(1000.0);
});

test('disbursement voucher payees derive from the selected source document', function () {
    $fixtures = financeDisbursementVoucherFixtures();

    $cases = [
        'po' => ['Supplier', 'Approved Supplier Co.', 'Cash'],
        'err' => ['Employee', 'Reimbursement Requester', 'Bank Transfer'],
        'pda' => ['Payroll Group', 'Approved Payroll Group', 'Bank Transfer'],
        'ibtf' => ['Receiving Bank Account', 'Approved Bank Account', 'Bank Transfer'],
    ];

    foreach (array_values($cases) as $index => [$expectedPayeeType, $expectedPayeeName, $paymentType]) {
        $sourceType = array_keys($cases)[$index];
        $sourceRecord = $fixtures['sources'][$sourceType];
        $amount = (float) $sourceRecord->amount;

        $response = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
            'module_key' => 'dv',
            'record_number' => sprintf('DV-%05d', 91001 + $index),
            'record_title' => 'DV Payee ' . strtoupper($sourceType),
            'record_date' => now()->toDateString(),
            'amount' => $amount,
            'status' => 'Released',
            'data' => [
                'source_document_type' => $sourceType,
                'source_document_id' => $sourceRecord->id,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'disbursement_type' => $paymentType,
                'bank_account_id' => $fixtures['bankAccount']->id,
                'coa_id' => $fixtures['chartAccount']->id,
            ],
        ]);

        $response->assertCreated();

        $record = FinanceRecord::query()->findOrFail($response->json('data.id'));
        expect(data_get($record->data, 'payee_type'))->toBe($expectedPayeeType);
        expect(data_get($record->data, 'payee_name'))->toBe($expectedPayeeName);
        expect(data_get($record->data, 'source_payee_type'))->toBe($expectedPayeeType);
        expect(data_get($record->data, 'source_payee_name'))->toBe($expectedPayeeName);
    }
});

test('released disbursement vouchers become read-only and reject field and attachment changes', function () {
    $fixtures = financeDisbursementVoucherFixtures();
    $sourceRecord = $fixtures['sources']['po'];

    $response = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-00150',
        'record_title' => 'Locked DV Regression',
        'record_date' => now()->toDateString(),
        'amount' => 1000,
        'status' => 'Released',
        'data' => [
            'source_document_type' => 'po',
            'source_document_id' => $sourceRecord->id,
            'amount' => 1000,
            'payment_type' => 'Cash',
            'disbursement_type' => 'Cash',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
            'line_items' => [
                [
                    'description' => 'Locked DV expense',
                    'account_code' => '6000',
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'description' => 'Locked DV funding',
                    'account_code' => '1000',
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ],
        'attachments' => [
            UploadedFile::fake()->create('locked-dv-support.pdf', 50, 'application/pdf'),
        ],
    ]);

    $response->assertCreated();
    $dv = FinanceRecord::query()->findOrFail($response->json('data.id'));

    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $dv))->assertOk();
    $this->actingAs($fixtures['president'])->postJson(route('finance.approve', $dv))->assertOk();
    $this->actingAs($fixtures['treasurer'])->postJson(route('finance.approve', $dv))->assertOk();

    $dv->refresh();
    expect($dv->status)->toBe('Approved');
    expect(data_get($dv->data, 'relationship_status'))->toBe('Approved');

    $releaseResponse = $this->actingAs($fixtures['president'])->put(route('finance.update', $dv), [
        'module_key' => 'dv',
        'record_number' => $dv->record_number,
        'record_title' => $dv->record_title,
        'record_date' => optional($dv->record_date)->format('Y-m-d'),
        'amount' => $dv->amount,
        'status' => 'Released',
        'existing_attachments_json' => json_encode($dv->attachments ?? [], JSON_THROW_ON_ERROR),
        'data' => array_merge($dv->data ?? [], [
            'source_document_type' => 'po',
            'source_document_id' => $sourceRecord->id,
            'payee_type' => data_get($dv->data, 'payee_type'),
            'payee_name' => data_get($dv->data, 'payee_name'),
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
            'line_items' => [
                [
                    'description' => 'Locked DV expense',
                    'account_code' => '6000',
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'description' => 'Locked DV funding',
                    'account_code' => '1000',
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ]),
    ]);

    $releaseResponse->assertOk();

    $dv->refresh();
    expect($dv->status)->toBe('Disbursed');
    expect(data_get($dv->data, 'relationship_status'))->toBe('Disbursed');

    $lockedUpdateResponse = $this->actingAs($fixtures['president'])->put(route('finance.update', $dv), [
        'module_key' => 'dv',
        'record_number' => $dv->record_number,
        'record_title' => $dv->record_title,
        'record_date' => optional($dv->record_date)->format('Y-m-d'),
        'amount' => 999999,
        'status' => 'Completed',
        'existing_attachments_json' => json_encode([], JSON_THROW_ON_ERROR),
        'data' => array_merge($dv->data ?? [], [
            'source_document_type' => 'ca',
            'source_document_id' => $fixtures['sources']['ca']->id,
            'payee_type' => 'Employee',
            'payee_name' => 'Changed Payee',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
            'line_items' => [
                [
                    'description' => 'Attempted locked edit',
                    'account_code' => '9999',
                    'debit' => 999999,
                    'credit' => 999999,
                ],
            ],
        ]),
    ]);

    $lockedUpdateResponse->assertForbidden();

    $lockedDeleteResponse = $this->actingAs($fixtures['president'])->post(route('finance.delete.request', $dv));
    $lockedDeleteResponse->assertForbidden();
});

test('dv approval warns and blocks when available balance is insufficient unless policy allows it', function () {
    $fixtures = financeDisbursementVoucherFixtures();
    $sourceRecord = $fixtures['sources']['ca'];

    financeSetDvInsufficientFundsPolicy(false);
    Notification::fake();

    $response = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-99001',
        'record_title' => 'Insufficient Funds DV',
        'record_date' => now()->toDateString(),
        'amount' => 500,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'ca',
            'source_document_id' => $sourceRecord->id,
            'amount' => 500,
            'payment_type' => 'Cash',
            'disbursement_type' => 'Cash',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);

    $response->assertCreated();
    $dv = FinanceRecord::query()->findOrFail($response->json('data.id'));

    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $dv))->assertOk();

    $approveResponse = $this->actingAs($fixtures['president'])->postJson(route('finance.approve', $dv));
    $approveResponse->assertStatus(422);
    $approveResponse->assertJsonValidationErrors(['data.amount']);

    $dv = $dv->fresh();
    expect(data_get($dv->data, 'fund_availability_status'))->toBe('Insufficient Funds');
    expect(data_get($dv->data, 'fund_availability_warning'))->toContain('available balance');
    expect(data_get($dv->data, 'fund_availability_requested_amount'))->toBe('500.00');
    expect(data_get($dv->data, 'fund_availability_available_balance'))->toBe('250.00');
    expect($dv->workflow_status)->toBe('Submitted');
    expect($dv->approval_status)->toBe('Pending');

    Notification::assertSentTo(
        $fixtures['treasurer'],
        FinanceRecordWorkflowNotification::class,
        fn (FinanceRecordWorkflowNotification $notification) => $notification->action === 'insufficient_funds'
    );

    financeSetDvInsufficientFundsPolicy(true);
    Notification::fake();

    $allowedResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-99002',
        'record_title' => 'Allowed Insufficient Funds DV',
        'record_date' => now()->toDateString(),
        'amount' => 500,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'ca',
            'source_document_id' => $sourceRecord->id,
            'amount' => 500,
            'payment_type' => 'Cash',
            'disbursement_type' => 'Cash',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);

    $allowedResponse->assertCreated();
    $allowedDv = FinanceRecord::query()->findOrFail($allowedResponse->json('data.id'));

    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $allowedDv))->assertOk();
    $allowedApproveResponse = $this->actingAs($fixtures['president'])->postJson(route('finance.approve', $allowedDv));
    $allowedApproveResponse->assertOk();

    $allowedDv = $allowedDv->fresh();
    expect(data_get($allowedDv->data, 'fund_availability_status'))->toBe('Insufficient Funds');
    expect(data_get($allowedDv->data, 'fund_availability_policy_allows_approval'))->toBeTrue();
    expect($allowedDv->approval_status)->toBe('Partially Approved');

    Notification::assertSentTo(
        $fixtures['treasurer'],
        FinanceRecordWorkflowNotification::class,
        fn (FinanceRecordWorkflowNotification $notification) => $notification->action === 'insufficient_funds'
    );

});

test('dv approval blocks when debit and credit totals are not equal', function () {
    $fixtures = financeDisbursementVoucherFixtures();
    $sourceRecord = $fixtures['sources']['po'];

    $response = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-99003',
        'record_title' => 'Unbalanced Accounting DV',
        'record_date' => now()->toDateString(),
        'amount' => 1000,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'po',
            'source_document_id' => $sourceRecord->id,
            'amount' => 1000,
            'payment_type' => 'Cash',
            'disbursement_type' => 'Cash',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);

    $response->assertCreated();
    $dv = FinanceRecord::query()->findOrFail($response->json('data.id'));

    $this->actingAs($fixtures['owner'])->put(route('finance.update', $dv), [
        'module_key' => 'dv',
        'record_number' => $dv->record_number,
        'record_title' => $dv->record_title,
        'record_date' => optional($dv->record_date)->format('Y-m-d'),
        'amount' => $dv->amount,
        'status' => $dv->status,
        'data' => array_merge($dv->data ?? [], [
            'line_items' => [
                [
                    'description' => 'Unbalanced debit',
                    'account_code' => '6000',
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'description' => 'Unbalanced credit',
                    'account_code' => '1000',
                    'debit' => 0,
                    'credit' => 500,
                ],
            ],
        ]),
    ])->assertOk();

    $dv = $dv->fresh();
    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $dv))->assertOk();

    $approveResponse = $this->actingAs($fixtures['president'])->postJson(route('finance.approve', $dv));
    $approveResponse->assertStatus(422);
    $approveResponse->assertJsonValidationErrors(['data.line_items']);

    $dv = $dv->fresh();
    expect(data_get($dv->data, 'accounting_balance_status'))->toBe('Unbalanced');
    expect(data_get($dv->data, 'total_debit_amount'))->toBe('1000.00');
    expect(data_get($dv->data, 'total_credit_amount'))->toBe('500.00');
    expect(data_get($dv->data, 'accounting_balance_difference'))->toBe('500.00');
});

test('disqualified source documents do not appear in the dv source selector', function () {
    $fixtures = financeDisbursementVoucherFixtures();

    $blockedSource = FinanceRecord::query()->create([
        'module_key' => 'po',
        'record_number' => 'PO-00999',
        'record_title' => 'Blocked Purchase Order',
        'record_date' => now()->toDateString(),
        'amount' => 1000,
        'status' => 'Cancelled',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $fixtures['owner']->id,
        'submitted_at' => now(),
        'approved_by' => $fixtures['owner']->id,
        'approved_at' => now(),
        'data' => [
            'relationship_status' => 'Completed',
        ],
        'attachments' => [],
        'user' => $fixtures['owner']->name,
    ]);

    $response = $this->actingAs($fixtures['owner'])->get(route('finance'));
    $response->assertOk();

    $content = $response->getContent();
    expect($content)->toContain('window.financeBootstrap =');

    preg_match("/window\\.financeBootstrap = JSON\\.parse\\('(.+)'\\);\\s*<\\/script>/s", $content, $matches);
    expect($matches[1] ?? null)->not->toBeNull();

    $bootstrapJson = json_decode('"' . $matches[1] . '"', true, 512, JSON_THROW_ON_ERROR);
    $bootstrap = json_decode($bootstrapJson, true, 512, JSON_THROW_ON_ERROR);
    $sourceRecordNumbers = collect($bootstrap['sourceRecords'] ?? [])->pluck('record_number')->all();

    expect($sourceRecordNumbers)->not->toContain('PO-00999');

    $blockedDvResponse = $this->actingAs($fixtures['owner'])->postJson(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-00999',
        'record_title' => 'DV From Blocked PO',
        'record_date' => now()->toDateString(),
        'amount' => 1000,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'po',
            'source_document_id' => $blockedSource->id,
            'amount' => 1000,
            'payment_type' => 'Cash',
            'disbursement_type' => 'Cash',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);

    $blockedDvResponse->assertStatus(422);
    $blockedDvResponse->assertJsonValidationErrors(['data.source_document_id']);
});

test('multiple disbursement vouchers roll up against a single source document', function () {
    $fixtures = financeDisbursementVoucherFixtures();

    $po = FinanceRecord::query()->create([
        'module_key' => 'po',
        'record_number' => 'PO-90001',
        'record_title' => 'Partial Disbursement Purchase Order',
        'record_date' => now()->toDateString(),
        'amount' => 100000,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $fixtures['owner']->id,
        'submitted_at' => now(),
        'approved_by' => $fixtures['owner']->id,
        'approved_at' => now(),
        'data' => [
            'requestor' => $fixtures['owner']->name,
            'department' => 'Procurement',
            'project' => 'Project Delta',
            'cost_center' => 'CC-100',
            'fund_source' => 'Operating Funds',
            'supplier_name' => 'Bulk Supplier Inc.',
            'purpose' => 'Large procurement batch',
        ],
        'attachments' => [[
            'name' => 'supporting-document.pdf',
            'path' => 'storage/testing/supporting-document.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'category' => 'Supporting Document',
            'status' => 'uploaded',
            'uploaded_at' => now()->format('Y-m-d H:i:s'),
            'uploaded_by' => $fixtures['owner']->name,
        ]],
        'user' => $fixtures['owner']->name,
    ]);

    $releaseAmounts = [40000, 30000, 30000];
    $expectedStatuses = ['Partially Disbursed', 'Partially Disbursed', 'Fully Disbursed'];
    $expectedTotals = ['40000.00', '70000.00', '100000.00'];
    $expectedRemaining = ['60000.00', '30000.00', '0.00'];
    $expectedPercentages = ['40.00', '70.00', '100.00'];

    foreach ($releaseAmounts as $index => $releaseAmount) {
        $response = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
            'module_key' => 'dv',
            'record_number' => 'DV-9000' . ($index + 1),
            'record_title' => 'DV Release ' . ($index + 1),
            'record_date' => now()->toDateString(),
            'amount' => $releaseAmount,
            'status' => 'Active',
            'data' => [
                'source_document_type' => 'po',
                'source_document_id' => $po->id,
                'amount' => $releaseAmount,
                'payment_type' => 'Cash',
                'disbursement_type' => 'Cash',
                'bank_account_id' => $fixtures['bankAccount']->id,
                'coa_id' => $fixtures['chartAccount']->id,
            ],
        ]);

        $response->assertCreated();

        $dv = FinanceRecord::query()->findOrFail($response->json('data.id'));
        $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $dv))->assertOk();
        $this->actingAs($fixtures['president'])->postJson(route('finance.approve', $dv))->assertOk();
        $this->actingAs($fixtures['treasurer'])->postJson(route('finance.approve', $dv))->assertOk();

        $this->actingAs($fixtures['president'])->put(route('finance.update', $dv), [
            'module_key' => 'dv',
            'record_number' => $dv->record_number,
            'record_title' => $dv->record_title,
            'record_date' => optional($dv->record_date)->format('Y-m-d'),
            'amount' => $dv->amount,
            'status' => 'Released',
            'data' => array_merge($dv->data ?? [], [
                'line_items' => [
                    [
                        'description' => 'Purchase release ' . ($index + 1),
                        'account_code' => '6000',
                        'debit' => $releaseAmount,
                        'credit' => 0,
                    ],
                    [
                        'description' => 'Purchase funding ' . ($index + 1),
                        'account_code' => '1000',
                        'debit' => 0,
                        'credit' => $releaseAmount,
                    ],
                ],
            ]),
        ])->assertOk();

        $po = $po->fresh();
        expect(data_get($po->data, 'total_disbursed_amount'))->toBe($expectedTotals[$index]);
        expect(data_get($po->data, 'remaining_balance'))->toBe($expectedRemaining[$index]);
        expect(data_get($po->data, 'percentage_paid'))->toBe($expectedPercentages[$index]);
        expect(data_get($po->data, 'disbursement_status'))->toBe($index < 2 ? 'Partially Disbursed' : 'Fully Disbursed');
        expect(data_get($po->data, 'relationship_status'))->toBe($expectedStatuses[$index]);
    }
});
