<?php

use App\Models\DirectorOfficer;
use App\Models\Employee;
use App\Models\FinanceRecord;
use App\Models\GisRecord;
use App\Models\User;
use App\Models\UserPermission;
use App\Notifications\FinanceRecordWorkflowNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function financeNotificationLogicFixtures(): array
{
    $owner = User::factory()->create([
        'name' => 'Notification Owner',
        'email' => 'notification.owner@example.com',
        'role' => 'employee',
    ]);
    UserPermission::query()->updateOrCreate(
        ['user_id' => $owner->id],
        [
            'access_finance' => true,
            'access_finance_chart_account' => true,
            'access_finance_ca' => true,
            'access_finance_dv' => true,
            'access_finance_lr' => true,
            'access_finance_arf' => true,
        ]
    );

    $president = User::factory()->create([
        'name' => 'Rhyss Account',
        'email' => 'rhyss-notification@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $president->id,
        'first_name' => 'Rhyss',
        'last_name' => 'Account',
        'email' => 'rhyss-notification@example.com',
        'position' => 'President',
        'payroll_type' => 'Monthly Paid',
    ]);
    UserPermission::query()->updateOrCreate(
        ['user_id' => $president->id],
        [
            'finance_president' => true,
            'finance_approver' => true,
        ]
    );

    $treasurer = User::factory()->create([
        'name' => 'Notification Treasurer',
        'email' => 'notification.treasurer@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $treasurer->id,
        'first_name' => 'Notification',
        'last_name' => 'Treasurer',
        'email' => 'notification.treasurer@example.com',
        'position' => 'Treasurer',
        'payroll_type' => 'Monthly Paid',
    ]);
    UserPermission::query()->updateOrCreate(
        ['user_id' => $treasurer->id],
        [
            'finance_treasurer' => true,
            'finance_approver' => true,
        ]
    );

    $approver = User::factory()->create([
        'name' => 'Notification Approver',
        'email' => 'notification.approver@example.com',
        'role' => 'employee',
    ]);
    UserPermission::query()->updateOrCreate(
        ['user_id' => $approver->id],
        [
            'finance_approver' => true,
        ]
    );

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

    $custodianUser = User::factory()->create([
        'name' => 'Notification Custodian',
        'email' => 'notification.custodian@example.com',
        'role' => 'employee',
    ]);
    $custodian = Employee::query()->create([
        'user_id' => $custodianUser->id,
        'first_name' => 'Notification',
        'last_name' => 'Custodian',
        'email' => 'notification.custodian@example.com',
        'work_email' => 'notification.custodian@example.com',
        'payroll_type' => 'Monthly Paid',
    ]);

    $supplier = FinanceRecord::query()->create([
        'module_key' => 'supplier',
        'record_number' => 'SUP-00051',
        'record_title' => 'Notification Supplier',
        'record_date' => now()->toDateString(),
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $owner->id,
        'approved_at' => now(),
        'data' => ['trade_name' => 'Notification Supply Co.'],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $chartAccount = FinanceRecord::query()->create([
        'module_key' => 'chart_account',
        'record_number' => 'COA-00051',
        'record_title' => 'Notification Expense Account',
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

    $bankAccount = FinanceRecord::query()->create([
        'module_key' => 'bank_account',
        'record_number' => 'BA-00051',
        'record_title' => 'Notification Bank Account',
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
        'record_number' => 'PO-00051',
        'record_title' => 'Notification Purchase Order',
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
            'total_amount' => 120,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    return compact('owner', 'president', 'treasurer', 'approver', 'custodianUser', 'custodian', 'supplier', 'chartAccount', 'bankAccount', 'po', 'gisRecord');
}

function financeNotificationCreateAndApproveRecord($testCase, array $payload, User $owner, User $president, User $treasurer): FinanceRecord
{
    $payload['data'] = array_merge([
        'first_approver_user_id' => $president->id,
        'second_approver_user_id' => $treasurer->id,
    ], (array) ($payload['data'] ?? []));

    $storeResponse = $testCase->actingAs($owner)->post(route('finance.store'), $payload);
    $storeResponse->assertCreated();

    $record = FinanceRecord::query()->findOrFail($storeResponse->json('data.id'));

    $testCase->actingAs($owner)->postJson(route('finance.submit', $record))->assertOk();
    $testCase->actingAs($president)->postJson(route('finance.approve', $record))->assertOk();
    $testCase->actingAs($treasurer)->postJson(route('finance.approve', $record))->assertOk();

    return $record->fresh();
}

test('workflow notifications are sent for submission approval hold and revert', function () {
    Notification::fake();
    $fixtures = financeNotificationLogicFixtures();

    $submittedRecordResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'chart_account',
        'record_number' => 'COA-51001',
        'record_title' => 'Notification Workflow Account',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'data' => [
            'account_type' => 'Expense',
            'normal_balance' => 'Debit',
            'first_approver_user_id' => $fixtures['president']->id,
            'second_approver_user_id' => $fixtures['treasurer']->id,
        ],
    ]);
    $submittedRecordResponse->assertCreated();

    $submittedRecord = FinanceRecord::query()->findOrFail($submittedRecordResponse->json('data.id'));
    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $submittedRecord))->assertOk();

    Notification::assertSentTo($fixtures['president'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($submittedRecord): bool {
        return $notification->action === 'submitted' && $notification->recordId === $submittedRecord->id;
    });

    Notification::assertSentTo($fixtures['treasurer'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($submittedRecord): bool {
        return $notification->action === 'submitted' && $notification->recordId === $submittedRecord->id;
    });

    Notification::assertSentTo($fixtures['approver'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($submittedRecord): bool {
        return $notification->action === 'submitted' && $notification->recordId === $submittedRecord->id;
    });

    Notification::assertNotSentTo($fixtures['owner'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($submittedRecord): bool {
        return $notification->action === 'submitted' && $notification->recordId === $submittedRecord->id;
    });

    $approvedRecord = financeNotificationCreateAndApproveRecord($this, [
        'module_key' => 'chart_account',
        'record_number' => 'COA-51002',
        'record_title' => 'Notification Workflow Approved Account',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'data' => [
            'account_type' => 'Expense',
            'normal_balance' => 'Debit',
        ],
    ], $fixtures['owner'], $fixtures['president'], $fixtures['treasurer']);

    Notification::assertSentTo($fixtures['owner'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($approvedRecord): bool {
        return $notification->action === 'approved' && $notification->recordId === $approvedRecord->id;
    });

    Notification::assertSentTo($fixtures['president'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($approvedRecord): bool {
        return $notification->action === 'approved' && $notification->recordId === $approvedRecord->id;
    });

    Notification::assertSentTo($fixtures['approver'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($approvedRecord): bool {
        return $notification->action === 'approved' && $notification->recordId === $approvedRecord->id;
    });

    $holdRecordResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'chart_account',
        'record_number' => 'COA-51003',
        'record_title' => 'Notification Workflow Hold Account',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'data' => [
            'account_type' => 'Expense',
            'normal_balance' => 'Debit',
            'first_approver_user_id' => $fixtures['president']->id,
            'second_approver_user_id' => $fixtures['treasurer']->id,
        ],
    ]);
    $holdRecordResponse->assertCreated();
    $holdRecord = FinanceRecord::query()->findOrFail($holdRecordResponse->json('data.id'));
    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $holdRecord))->assertOk();

    $holdResponse = $this->actingAs($fixtures['president'])->postJson(route('finance.hold', $holdRecord), [
        'review_note' => 'Missing supporting attachment.',
    ]);
    $holdResponse->assertOk();

    Notification::assertSentTo($fixtures['owner'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($holdRecord): bool {
        return $notification->action === 'held' && $notification->recordId === $holdRecord->id;
    });

    Notification::assertSentTo($fixtures['treasurer'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($holdRecord): bool {
        return $notification->action === 'held' && $notification->recordId === $holdRecord->id;
    });

    Notification::assertSentTo($fixtures['approver'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($holdRecord): bool {
        return $notification->action === 'held' && $notification->recordId === $holdRecord->id;
    });

    $revertResponse = $this->actingAs($fixtures['president'])->postJson(route('finance.revert', $holdRecord), [
        'reason' => 'Please revise the backup documents.',
    ]);
    $revertResponse->assertOk();

    Notification::assertSentTo($fixtures['owner'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($holdRecord): bool {
        return $notification->action === 'reverted' && $notification->recordId === $holdRecord->id;
    });

    Notification::assertSentTo($fixtures['treasurer'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($holdRecord): bool {
        return $notification->action === 'reverted' && $notification->recordId === $holdRecord->id;
    });

    Notification::assertSentTo($fixtures['approver'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($holdRecord): bool {
        return $notification->action === 'reverted' && $notification->recordId === $holdRecord->id;
    });
});

it('notifies supplier approvers and admins when a supplier completion form is submitted', function () {
    Notification::fake();
    $fixtures = financeNotificationLogicFixtures();

    $supplierRecord = FinanceRecord::query()->create([
        'module_key' => 'supplier',
        'record_number' => 'SUP-51001',
        'record_title' => 'Supplier Completion Notification Test',
        'record_date' => now()->toDateString(),
        'status' => 'Draft',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $fixtures['owner']->id,
        'submitted_at' => now(),
        'approved_by' => $fixtures['owner']->id,
        'approved_at' => now(),
        'share_token' => (string) \Illuminate\Support\Str::uuid(),
        'data' => [
            'completion_mode' => 'send_to_supplier',
            'approval_steps' => [
                [
                    'step' => 1,
                    'role' => 'Treasurer',
                    'label' => 'Treasurer',
                    'required' => true,
                    'user_id' => $fixtures['treasurer']->id,
                    'user_name' => $fixtures['treasurer']->name,
                    'user_email' => $fixtures['treasurer']->email,
                    'official_name' => $fixtures['treasurer']->name,
                    'source' => 'GIS',
                ],
                [
                    'step' => 2,
                    'role' => 'President',
                    'label' => 'President',
                    'required' => true,
                    'user_id' => $fixtures['president']->id,
                    'user_name' => $fixtures['president']->name,
                    'user_email' => $fixtures['president']->email,
                    'official_name' => $fixtures['president']->name,
                    'source' => 'GIS',
                ],
            ],
            'approval_required_count' => 2,
            'approval_completed_count' => 0,
            'approval_remaining_count' => 2,
            'first_approver_user_id' => $fixtures['treasurer']->id,
            'second_approver_user_id' => $fixtures['president']->id,
            'representative_full_name' => 'Notification Supplier Rep',
            'email_address' => 'supplier.rep@example.com',
            'phone_number' => '09171234567',
            'legal_acknowledgment' => true,
            'electronic_signature_consent' => true,
            'data_privacy_consent' => true,
            'confidentiality_undertaking' => true,
            'company_policy_compliance' => true,
            'false_information_penalty' => true,
        ],
        'attachments' => [],
        'user' => $fixtures['owner']->name,
    ]);

    $response = $this->actingAs($fixtures['owner'])->post(route('finance.supplier.completion', $supplierRecord->share_token), [
        'record_title' => 'Supplier Completion Notification Test',
        'record_number' => 'SUP-51001',
        'record_date' => now()->toDateString(),
        'data' => [
            'entity_type' => 'Corporation',
            'representative_full_name' => 'Notification Supplier Rep',
            'email_address' => 'supplier.rep@example.com',
            'phone_number' => '09171234567',
            'legal_acknowledgment' => true,
            'electronic_signature_consent' => true,
            'data_privacy_consent' => true,
            'confidentiality_undertaking' => true,
            'company_policy_compliance' => true,
            'false_information_penalty' => true,
        ],
    ]);

    $response->assertRedirect();

    Notification::assertSentTo($fixtures['treasurer'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($supplierRecord, $fixtures): bool {
        return $notification->action === 'supplier_submitted'
            && $notification->recordId === $supplierRecord->id
            && in_array('database', $notification->via($fixtures['treasurer']), true)
            && in_array('broadcast', $notification->via($fixtures['treasurer']), true)
            && in_array('mail', $notification->via($fixtures['treasurer']), true);
    });

    Notification::assertSentTo($fixtures['approver'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($supplierRecord, $fixtures): bool {
        return $notification->action === 'supplier_submitted'
            && $notification->recordId === $supplierRecord->id
            && in_array('database', $notification->via($fixtures['approver']), true)
            && in_array('broadcast', $notification->via($fixtures['approver']), true)
            && in_array('mail', $notification->via($fixtures['approver']), true);
    });

    Notification::assertNotSentTo($fixtures['owner'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($supplierRecord): bool {
        return $notification->action === 'supplier_submitted' && $notification->recordId === $supplierRecord->id;
    });

    Notification::assertSentTo($fixtures['president'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($supplierRecord, $fixtures): bool {
        return $notification->action === 'supplier_submitted'
            && $notification->recordId === $supplierRecord->id
            && in_array('database', $notification->via($fixtures['president']), true)
            && in_array('broadcast', $notification->via($fixtures['president']), true)
            && in_array('mail', $notification->via($fixtures['president']), true);
    });

    Notification::assertNotSentTo($fixtures['owner'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($supplierRecord): bool {
        return $notification->action === 'supplier_submitted' && $notification->recordId === $supplierRecord->id;
    });
});

test('liquidation due asset assignment and inventory alerts are notified automatically', function () {
    Notification::fake();
    $fixtures = financeNotificationLogicFixtures();

    $ca = financeNotificationCreateAndApproveRecord($this, [
        'module_key' => 'ca',
        'record_number' => 'CA-51001',
        'record_title' => 'Notification Cash Advance',
        'record_date' => now()->toDateString(),
        'amount' => 5000,
        'status' => 'Active',
        'data' => [
            'requestor' => $fixtures['owner']->name,
            'purpose' => 'Notify liquidation due.',
            'amount_requested' => 5000,
            'mode_of_release' => 'Bank Transfer',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ], $fixtures['owner'], $fixtures['president'], $fixtures['treasurer']);

    $dvResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-51001',
        'record_title' => 'DV for Notification CA',
        'record_date' => now()->toDateString(),
        'amount' => 5000,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'ca',
            'source_document_id' => $ca->id,
            'amount' => 5000,
            'payment_type' => 'Bank Transfer',
            'disbursement_type' => 'Bank Transfer',
            'bank_account_id' => $fixtures['bankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
            'line_items' => [
                [
                    'description' => 'Cash advance release',
                    'account_code' => '1000',
                    'debit' => 5000,
                    'credit' => 0,
                ],
            ],
        ],
    ]);
    $dvResponse->assertCreated();

    $dv = FinanceRecord::query()->findOrFail($dvResponse->json('data.id'));
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
                    'description' => 'Cash advance release',
                    'account_code' => '1000',
                    'debit' => 5000,
                    'credit' => 0,
                ],
            ],
        ]),
    ])->assertOk();

    Notification::assertSentTo($fixtures['owner'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($ca): bool {
        return $notification->action === 'liquidation_due' && $notification->recordId === $ca->id;
    });

    Notification::assertSentTo($fixtures['treasurer'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($ca): bool {
        return $notification->action === 'liquidation_due' && $notification->recordId === $ca->id;
    });

    Notification::assertSentTo($fixtures['approver'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($ca): bool {
        return $notification->action === 'liquidation_due' && $notification->recordId === $ca->id;
    });

    $lrResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'lr',
        'record_number' => 'LR-51001',
        'record_title' => 'Notification Liquidation Report',
        'record_date' => now()->toDateString(),
        'amount' => 5000,
        'status' => 'Active',
        'data' => [
            'requester_mode' => 'own_request',
            'requestor' => $fixtures['owner']->name,
            'linked_ca_id' => $ca->id,
            'total_cash_advance' => 5000,
            'purpose' => 'Liquidation notification test.',
            'actual_expenses' => 5000,
            'variance' => 0,
            'variance_indicator' => 'Balanced',
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);
    $lrResponse->assertCreated();

    $lr = FinanceRecord::query()->findOrFail($lrResponse->json('data.id'));
    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $lr))->assertOk();
    $this->actingAs($fixtures['president'])->postJson(route('finance.approve', $lr))->assertOk();
    $this->actingAs($fixtures['treasurer'])->postJson(route('finance.approve', $lr))->assertOk();

    Notification::assertSentTo($fixtures['owner'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($lr): bool {
        return $notification->action === 'approved' && $notification->recordId === $lr->id;
    });

    $arfResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'arf',
        'record_number' => 'ARF-51001',
        'record_title' => 'Notification Consumables',
        'record_date' => now()->toDateString(),
        'amount' => 120,
        'status' => 'Active',
            'data' => [
                'item_classification' => 'Consumable Inventory',
                'asset_code' => 'INV-NOTIFY-001',
                'asset_description' => 'Low stock consumables notification test',
                'linked_po_id' => $fixtures['po']->id,
            'linked_dv_id' => null,
            'supplier_id' => $fixtures['supplier']->id,
            'asset_coa_id' => $fixtures['chartAccount']->id,
            'custodian' => $fixtures['custodian']->id,
            'location' => 'Storage Room',
            'current_quantity' => 4,
            'reserved_quantity' => 1,
                'reorder_level' => 5,
                'minimum_stock_level' => 3,
                'maximum_stock_level' => 20,
                'safety_stock_level' => 2,
                'unit_cost' => 30,
            'acquisition_cost' => 120,
            'acquisition_date' => now()->toDateString(),
            'remarks' => 'Should trigger inventory alert.',
        ],
    ]);
    $arfResponse->assertCreated();

    $arf = FinanceRecord::query()->findOrFail($arfResponse->json('data.id'));

    Notification::assertSentTo($fixtures['custodianUser'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($arf): bool {
        return $notification->action === 'assigned' && $notification->recordId === $arf->id;
    });

    Notification::assertSentTo($fixtures['custodianUser'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($arf): bool {
        return $notification->action === 'inventory_alert' && $notification->recordId === $arf->id;
    });

    Notification::assertSentTo($fixtures['president'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($arf): bool {
        return $notification->action === 'inventory_alert' && $notification->recordId === $arf->id;
    });

    Notification::assertSentTo($fixtures['treasurer'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($arf): bool {
        return $notification->action === 'inventory_alert' && $notification->recordId === $arf->id;
    });

    Notification::assertSentTo($fixtures['approver'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($arf): bool {
        return $notification->action === 'inventory_alert' && $notification->recordId === $arf->id;
    });
});

test('workflow emails include pdf copy record metadata and relationship status', function () {
    $fixtures = financeNotificationLogicFixtures();

    $record = FinanceRecord::query()->create([
        'module_key' => 'po',
        'record_number' => 'PO-51099',
        'record_title' => 'Email Logic Purchase Order',
        'record_date' => now()->toDateString(),
        'status' => 'Active',
        'workflow_status' => 'Approved',
        'approval_status' => 'Approved',
        'submitted_by' => $fixtures['owner']->id,
        'submitted_at' => now(),
        'approved_by' => $fixtures['owner']->id,
        'approved_at' => now(),
        'data' => [
            'relationship_status' => 'Awaiting Disbursement',
        ],
        'attachments' => [
            [
                'name' => 'supporting-document.pdf',
                'path' => 'supporting-document.pdf',
            ],
        ],
        'user' => $fixtures['owner']->name,
    ]);

    $notification = new FinanceRecordWorkflowNotification(
        recordId: $record->id,
        action: 'approved',
        title: 'Finance Record Approved: ' . $record->record_number,
        body: 'A finance record has been approved.',
        buttonLabel: 'View Record',
        url: 'https://example.com/finance/records/' . $record->id,
        pdfData: '%PDF-1.4 test content',
        pdfFilename: 'finance-record.pdf',
    );

    $mail = $notification->toMail($fixtures['owner']);
    $mailReflection = new ReflectionClass($mail);

    $viewProperty = $mailReflection->getProperty('view');
    $viewProperty->setAccessible(true);
    $view = $viewProperty->getValue($mail);

    $viewDataProperty = $mailReflection->getProperty('viewData');
    $viewDataProperty->setAccessible(true);
    $viewData = $viewDataProperty->getValue($mail);

    $rawAttachmentsProperty = $mailReflection->getProperty('rawAttachments');
    $rawAttachmentsProperty->setAccessible(true);
    $rawAttachments = $rawAttachmentsProperty->getValue($mail);

    expect($view)->toBe('emails.finance-workflow-notification');
    expect($viewData['recordNumber'])->toBe('PO-51099');
    expect($viewData['workflowStatus'])->toBe('Approved');
    expect($viewData['approvalStatus'])->toBe('Approved');
    expect($viewData['relationshipStatus'])->toBe('Awaiting Disbursement');
    expect($viewData['buttonLabel'])->toBe('View Record');
    expect($viewData['url'])->toBe('https://example.com/finance/records/' . $record->id);
    expect($rawAttachments)->toHaveCount(1);
    expect($rawAttachments[0]['name'])->toBe('finance-record.pdf');
    expect($rawAttachments[0]['options']['mime'])->toBe('application/pdf');

    $rendered = strip_tags(view($view, $viewData)->render());

    expect($rendered)->toContain('Record Summary');
    expect($rendered)->toContain('Record Number');
    expect($rendered)->toContain('PO-51099');
    expect($rendered)->toContain('Status');
    expect($rendered)->toContain('Approved / Approved');
    expect($rendered)->toContain('Relationship Status');
    expect($rendered)->toContain('Awaiting Disbursement');
    expect($rendered)->toContain('View Record');
    expect($rendered)->toContain('A PDF copy of the current record is attached for reference.');
});
