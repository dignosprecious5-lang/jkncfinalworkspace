<?php

use App\Models\DirectorOfficer;
use App\Models\Employee;
use App\Models\EmployeePayrollProfile;
use App\Models\FinanceRecord;
use App\Models\GisRecord;
use App\Models\PayrollPeriod;
use App\Models\PayrollLevel;
use App\Models\SalaryGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function financeLifecyclePolicyFixtures(): array
{
    $owner = User::factory()->create([
        'name' => 'Lifecycle Requester',
        'email' => 'lifecycle.requester@example.com',
        'role' => 'employee',
    ]);

    $president = User::factory()->create([
        'name' => 'Lifecycle President',
        'email' => 'lifecycle.president@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $president->id,
        'first_name' => 'Lifecycle',
        'last_name' => 'President',
        'email' => 'lifecycle.president@example.com',
        'position' => 'President',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $treasurer = User::factory()->create([
        'name' => 'Lifecycle Treasurer',
        'email' => 'lifecycle.treasurer@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $treasurer->id,
        'first_name' => 'Lifecycle',
        'last_name' => 'Treasurer',
        'email' => 'lifecycle.treasurer@example.com',
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

    $payrollPeriod = PayrollPeriod::query()->create([
        'name' => 'Lifecycle Payroll Period',
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'payroll_start' => now()->startOfMonth()->toDateString(),
        'payroll_end' => now()->endOfMonth()->toDateString(),
        'payroll_date' => now()->toDateString(),
        'pay_date' => now()->toDateString(),
        'dispute_start' => now()->toDateString(),
        'dispute_end' => now()->toDateString(),
        'date_created' => now()->toDateString(),
        'status' => 'open',
    ]);

    $salaryGrade = SalaryGrade::query()->create([
        'code' => 'SG-LIFE',
        'name' => 'Lifecycle Salary Grade',
        'payment_type' => 'monthly',
        'monthly_basic_pay' => 25000,
        'applicable_daily_rate' => 833.33,
        'hourly_rate' => 104.17,
        'minute_rate' => 1.7362,
        'yearly_rate' => 300000,
        'date_created' => now()->toDateString(),
    ]);

    $payrollLevel = PayrollLevel::query()->create([
        'salary_grade_id' => $salaryGrade->id,
        'level_name' => 'Lifecycle Level',
        'computation_type' => 'monthly',
        'work_schedule' => 'every_day',
        'work_schedule_label' => 'Monday to Sunday - 8:00 AM to 5:00 PM',
        'hours_per_day' => 8,
        'date_created' => now()->toDateString(),
    ]);

    $ownerEmployee = Employee::query()->create([
        'user_id' => $owner->id,
        'first_name' => 'Lifecycle',
        'last_name' => 'Requester',
        'email' => 'lifecycle.requester@example.com',
        'position' => 'Employee',
        'payroll_type' => 'monthly',
        'basic_salary' => 25000,
        'hourly_rate' => 104.17,
    ]);

    EmployeePayrollProfile::query()->create([
        'employee_id' => $ownerEmployee->id,
        'payroll_level_id' => $payrollLevel->id,
        'basic_salary_override' => 25000,
        'night_differential_enabled' => false,
    ]);

    $fundingBankAccount = FinanceRecord::query()->create([
        'module_key' => 'bank_account',
        'record_number' => 'BA-10001',
        'record_title' => 'Lifecycle Funding Bank',
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

    $transferSourceBankAccount = FinanceRecord::query()->create([
        'module_key' => 'bank_account',
        'record_number' => 'BA-10002',
        'record_title' => 'Lifecycle Transfer Source',
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

    $transferDestinationBankAccount = FinanceRecord::query()->create([
        'module_key' => 'bank_account',
        'record_number' => 'BA-10003',
        'record_title' => 'Lifecycle Transfer Destination',
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
        'record_number' => 'COA-10001',
        'record_title' => 'Lifecycle Payroll Expense',
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

    $shortageLiquidation = FinanceRecord::query()->create([
        'module_key' => 'lr',
        'record_number' => 'LR-10002',
        'record_title' => 'Liquidation - Shortage',
        'record_date' => now()->toDateString(),
        'amount' => 1800,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $owner->id,
        'approved_at' => now(),
        'data' => [
            'variance_indicator' => 'Shortage',
            'variance' => -1800,
            'total_cash_advance' => 1800,
            'actual_expenses' => 3600,
            'purpose' => 'Shortage from employee-paid company expense.',
            'requestor' => $owner->name,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    return compact(
        'owner',
        'president',
        'treasurer',
        'payrollPeriod',
        'fundingBankAccount',
        'transferSourceBankAccount',
        'transferDestinationBankAccount',
        'chartAccount',
        'shortageLiquidation'
    );
}

function financeCreateAndApproveRecord($testCase, array $payload, User $owner, User $president, User $treasurer): FinanceRecord
{
    if (! array_key_exists('attachments', $payload)) {
        $payload['attachments'] = [
            UploadedFile::fake()->create('supporting-document.pdf', 10, 'application/pdf'),
        ];
    }

    $storeResponse = $testCase->actingAs($owner)->post(route('finance.store'), $payload);
    $storeResponse->assertCreated();

    $record = FinanceRecord::query()->findOrFail($storeResponse->json('data.id'));

    $testCase->actingAs($owner)->postJson(route('finance.submit', $record))->assertOk();
    $testCase->actingAs($president)->postJson(route('finance.approve', $record))->assertOk();
    $testCase->actingAs($treasurer)->postJson(route('finance.approve', $record))->assertOk();

    return $record->fresh();
}

test('payroll authorization completes after dv release and locks the record', function () {
    $fixtures = financeLifecyclePolicyFixtures();

    $pda = financeCreateAndApproveRecord($this, [
        'module_key' => 'pda',
        'record_number' => 'PDA-00001',
        'record_title' => 'Payroll Authorization',
        'record_date' => now()->toDateString(),
        'amount' => 25000,
        'status' => 'Active',
        'data' => [
            'requestor' => $fixtures['owner']->name,
            'payroll_period_id' => $fixtures['payrollPeriod']->id,
            'total_payroll_amount' => 25000,
            'funding_bank_account_id' => $fixtures['fundingBankAccount']->id,
            'payroll_expense_coa_id' => $fixtures['chartAccount']->id,
        ],
    ], $fixtures['owner'], $fixtures['president'], $fixtures['treasurer']);

    expect(data_get($pda->data, 'relationship_status'))->toBe('Awaiting Disbursement Voucher');

    $dvResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-20001',
        'record_title' => 'DV for Payroll',
        'record_date' => now()->toDateString(),
        'amount' => 25000,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'pda',
            'source_document_id' => $pda->id,
            'amount' => 25000,
            'payment_type' => 'Bank Transfer',
            'disbursement_type' => 'Bank Transfer',
            'bank_account_id' => $fixtures['fundingBankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
            'line_items' => [
                [
                    'description' => 'Payroll release',
                    'account_code' => '6000',
                    'debit' => 25000,
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

    $dvUpdate = $this->actingAs($fixtures['president'])->put(route('finance.update', $dv), [
        'module_key' => 'dv',
        'record_number' => $dv->record_number,
        'record_title' => $dv->record_title,
        'record_date' => optional($dv->record_date)->format('Y-m-d'),
        'amount' => $dv->amount,
        'status' => 'Released',
        'data' => array_merge($dv->data ?? [], [
            'line_items' => [
                [
                    'description' => 'Payroll release',
                    'account_code' => '6000',
                    'debit' => 25000,
                    'credit' => 0,
                ],
                [
                    'description' => 'Payroll funding',
                    'account_code' => '1000',
                    'debit' => 0,
                    'credit' => 25000,
                ],
            ],
        ]),
    ]);
    $dvUpdate->assertOk();

    $pda = $pda->fresh();
    $dv = $dv->fresh();

    expect(data_get($dv->data, 'relationship_status'))->toBe('Completed');
    expect(data_get($pda->data, 'relationship_status'))->toBe('Payroll Released');
    expect(data_get($pda->data, 'disbursement_status'))->toBe('Fully Disbursed');
    expect(data_get($pda->data, 'total_disbursed_amount'))->toBe('25000.00');
    expect(data_get($pda->data, 'remaining_balance'))->toBe('0.00');
    expect(data_get($pda->data, 'percentage_paid'))->toBe('100.00');

    $updateResponse = $this->actingAs($fixtures['owner'])->put(route('finance.update', $pda), [
        'module_key' => 'pda',
        'record_number' => $pda->record_number,
        'record_title' => $pda->record_title,
        'record_date' => optional($pda->record_date)->format('Y-m-d'),
        'amount' => $pda->amount,
        'status' => $pda->status,
        'data' => $pda->data ?? [],
    ]);
    $updateResponse->assertForbidden();

    $deleteResponse = $this->actingAs($fixtures['owner'])->post(route('finance.delete.request', $pda));
    $deleteResponse->assertForbidden();
});

test('completed payroll records accept correction requests without overwriting the original values', function () {
    $fixtures = financeLifecyclePolicyFixtures();

    $pda = financeCreateAndApproveRecord($this, [
        'module_key' => 'pda',
        'record_number' => 'PDA-00002',
        'record_title' => 'Payroll Authorization For Correction',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'data' => [
            'requestor' => $fixtures['owner']->name,
            'payroll_period_id' => $fixtures['payrollPeriod']->id,
            'funding_bank_account_id' => $fixtures['fundingBankAccount']->id,
            'payroll_expense_coa_id' => $fixtures['chartAccount']->id,
        ],
    ], $fixtures['owner'], $fixtures['president'], $fixtures['treasurer']);

    $dvResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-20003',
        'record_title' => 'DV for Payroll Correction',
        'record_date' => now()->toDateString(),
        'amount' => 25000,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'pda',
            'source_document_id' => $pda->id,
            'amount' => 25000,
            'payment_type' => 'Bank Transfer',
            'disbursement_type' => 'Bank Transfer',
            'bank_account_id' => $fixtures['fundingBankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
            'line_items' => [
                [
                    'description' => 'Payroll release',
                    'account_code' => '6000',
                    'debit' => 25000,
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
                    'description' => 'Payroll release',
                    'account_code' => '6000',
                    'debit' => 25000,
                    'credit' => 0,
                ],
                [
                    'description' => 'Payroll funding',
                    'account_code' => '1000',
                    'debit' => 0,
                    'credit' => 25000,
                ],
            ],
        ]),
    ])->assertOk();

    $pda = $pda->fresh();
    expect($pda->workflow_status)->toBe('Accepted');
    expect(data_get($pda->data, 'relationship_status'))->toBe('Payroll Released');

    $correctionResponse = $this->actingAs($fixtures['owner'])->postJson(route('finance.correction.request', $pda), [
        'request_type' => 'Correction',
        'reason' => 'The payroll expense amount needs to be corrected for a late adjustment.',
        'proposed_changes' => [
            'amount' => 26000,
            'data' => [
                'correction_note' => 'Late payroll adjustment included.',
            ],
        ],
    ]);
    $correctionResponse->assertOk();

    $pda = $pda->fresh();
    expect($pda->workflow_status)->toBe('Correction Requested');
    expect($pda->amount)->toBe('25000.00');
    expect(data_get($pda->data, 'correction_request_status'))->toBe('Pending');
    expect(collect((array) data_get($pda->data, 'history'))->firstWhere('action', 'Correction Requested'))->not->toBeNull();

    $this->actingAs($fixtures['president'])->postJson(route('finance.correction.approve', $pda), [
        'review_note' => 'Approved as an audit-only correction record.',
    ])->assertOk();

    $pda = $pda->fresh();
    expect($pda->workflow_status)->toBe('Accepted');
    expect($pda->approval_status)->toBe('Approved');
    expect(data_get($pda->data, 'correction_request_status'))->toBe('Approved');
    expect($pda->amount)->toBe('26000.00');
    expect(data_get($pda->data, 'correction_requests.0.original.amount'))->toBe('25000.00');
    expect(data_get($pda->data, 'correction_requests.0.corrected.amount'))->toBe(26000);
    expect(data_get($pda->data, 'correction_requests.0.approved_by_name'))->toBe($fixtures['president']->name);
    expect(data_get($pda->data, 'correction_requests.0.status'))->toBe('Approved');
});

test('finance audit trail preserves significant actions and lifecycle cascades', function () {
    $fixtures = financeLifecyclePolicyFixtures();

    $auditRecordResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'chart_account',
        'record_number' => 'COA-90001',
        'record_title' => 'Audit Trail Account',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'data' => [
            'account_type' => 'Expense',
            'normal_balance' => 'Debit',
            'remarks' => 'Initial audit trail record.',
            'first_approver_user_id' => $fixtures['president']->id,
            'second_approver_user_id' => $fixtures['treasurer']->id,
        ],
    ]);
    $auditRecordResponse->assertCreated();

    $auditRecord = FinanceRecord::query()->findOrFail($auditRecordResponse->json('data.id'));

    $this->actingAs($fixtures['owner'])->put(route('finance.update', $auditRecord), [
        'module_key' => 'chart_account',
        'record_number' => 'COA-90001',
        'record_title' => 'Audit Trail Account Updated',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'data' => [
            'account_type' => 'Expense',
            'normal_balance' => 'Debit',
            'remarks' => 'Updated audit trail record.',
            'first_approver_user_id' => $fixtures['president']->id,
            'second_approver_user_id' => $fixtures['treasurer']->id,
        ],
    ])->assertOk();

    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $auditRecord))->assertOk();
    $this->actingAs($fixtures['president'])->postJson(route('finance.approve', $auditRecord))->assertOk();
    $this->actingAs($fixtures['treasurer'])->postJson(route('finance.approve', $auditRecord))->assertOk();

    $auditRecord = $auditRecord->fresh();
    $auditHistoryActions = collect((array) data_get($auditRecord->data ?? [], 'history', []))
        ->pluck('action')
        ->all();

    expect($auditHistoryActions)->toContain('Created');
    expect($auditHistoryActions)->toContain('Updated');
    expect($auditHistoryActions)->toContain('Submitted');
    expect($auditHistoryActions)->toContain('Approved');

    $holdRecordResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'chart_account',
        'record_number' => 'COA-90002',
        'record_title' => 'Hold and Revert Audit Account',
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
    $this->actingAs($fixtures['president'])->postJson(route('finance.hold', $holdRecord), [
        'review_note' => 'Hold for supporting document review.',
    ])->assertOk();
    $this->actingAs($fixtures['president'])->postJson(route('finance.revert', $holdRecord), [
        'reason' => 'Return for correction before approval.',
    ])->assertOk();

    $holdRecord = $holdRecord->fresh();
    $holdHistoryActions = collect((array) data_get($holdRecord->data ?? [], 'history', []))
        ->pluck('action')
        ->all();

    expect($holdHistoryActions)->toContain('Placed On Hold');
    expect($holdHistoryActions)->toContain('Reverted');

    $ca = financeCreateAndApproveRecord($this, [
        'module_key' => 'ca',
        'record_number' => 'CA-90001',
        'record_title' => 'Audit Trail Cash Advance',
        'record_date' => now()->toDateString(),
        'amount' => 5000,
        'status' => 'Active',
        'data' => [
            'requestor' => $fixtures['owner']->name,
            'purpose' => 'Audit trail fund release.',
            'amount_requested' => 5000,
            'mode_of_release' => 'Bank Transfer',
            'bank_account_id' => $fixtures['fundingBankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ], $fixtures['owner'], $fixtures['president'], $fixtures['treasurer']);

    $dvResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-90001',
        'record_title' => 'Audit Trail DV',
        'record_date' => now()->toDateString(),
        'amount' => 5000,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'ca',
            'source_document_id' => $ca->id,
            'amount' => 5000,
            'payment_type' => 'Bank Transfer',
            'disbursement_type' => 'Bank Transfer',
            'bank_account_id' => $fixtures['fundingBankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
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
                    'description' => 'Audit trail fund release.',
                    'account_code' => '6000',
                    'debit' => 5000,
                    'credit' => 0,
                ],
                [
                    'description' => 'Audit trail funding.',
                    'account_code' => '1000',
                    'debit' => 0,
                    'credit' => 5000,
                ],
            ],
        ]),
    ])->assertOk();

    $ca = $ca->fresh();
    $caHistoryActions = collect((array) data_get($ca->data ?? [], 'history', []))
        ->pluck('action')
        ->all();

    expect($caHistoryActions)->toContain('Relationship Status Updated');
    expect(data_get($ca->data, 'relationship_status'))->toBe('Awaiting Liquidation');
});

test('interbank transfer completes after dv release', function () {
    $fixtures = financeLifecyclePolicyFixtures();

    $ibtf = financeCreateAndApproveRecord($this, [
        'module_key' => 'ibtf',
        'record_number' => 'IBTF-00001',
        'record_title' => 'Interbank Transfer Request',
        'record_date' => now()->toDateString(),
        'amount' => 15000,
        'status' => 'Active',
        'data' => [
            'requestor' => $fixtures['owner']->name,
            'source_bank_account_id' => $fixtures['transferSourceBankAccount']->id,
            'destination_bank_account_id' => $fixtures['transferDestinationBankAccount']->id,
            'amount' => 15000,
            'reason' => 'Move operating funds to destination account.',
        ],
    ], $fixtures['owner'], $fixtures['president'], $fixtures['treasurer']);

    expect(data_get($ibtf->data, 'relationship_status'))->toBe('Awaiting Disbursement Voucher');

    $dvResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-20002',
        'record_title' => 'DV for Transfer',
        'record_date' => now()->toDateString(),
        'amount' => 15000,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'ibtf',
            'source_document_id' => $ibtf->id,
            'amount' => 15000,
            'payment_type' => 'Bank Transfer',
            'disbursement_type' => 'Bank Transfer',
            'bank_account_id' => $fixtures['transferSourceBankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);
    $dvResponse->assertCreated();

    $dv = FinanceRecord::query()->findOrFail($dvResponse->json('data.id'));
    $this->actingAs($fixtures['owner'])->postJson(route('finance.submit', $dv))->assertOk();
    $this->actingAs($fixtures['president'])->postJson(route('finance.approve', $dv))->assertOk();
    $this->actingAs($fixtures['treasurer'])->postJson(route('finance.approve', $dv))->assertOk();

    $dvUpdate = $this->actingAs($fixtures['president'])->put(route('finance.update', $dv), [
        'module_key' => 'dv',
        'record_number' => $dv->record_number,
        'record_title' => $dv->record_title,
        'record_date' => optional($dv->record_date)->format('Y-m-d'),
        'amount' => $dv->amount,
        'status' => 'Released',
        'data' => $dv->data ?? [],
    ]);
    $dvUpdate->assertOk();

    $ibtf = $ibtf->fresh();
    $dv = $dv->fresh();

    expect(data_get($dv->data, 'relationship_status'))->toBe('Completed');
    expect(data_get($ibtf->data, 'relationship_status'))->toBe('Transfer Completed');
    expect(data_get($ibtf->data, 'disbursement_status'))->toBe('Fully Disbursed');
    expect(data_get($ibtf->data, 'total_disbursed_amount'))->toBe('15000.00');
    expect(data_get($ibtf->data, 'remaining_balance'))->toBe('0.00');
    expect(data_get($ibtf->data, 'percentage_paid'))->toBe('100.00');
});

test('cash advances remain awaiting liquidation until a liquidation report is approved', function () {
    $fixtures = financeLifecyclePolicyFixtures();

    $ca = financeCreateAndApproveRecord($this, [
        'module_key' => 'ca',
        'record_number' => 'CA-00001',
        'record_title' => 'Cash Advance - Operating Funds',
        'record_date' => now()->toDateString(),
        'amount' => 5000,
        'status' => 'Active',
        'data' => [
            'requestor' => $fixtures['owner']->name,
            'purpose' => 'Petty operating expenses before reimbursement.',
            'amount_requested' => 5000,
            'mode_of_release' => 'Bank Transfer',
            'bank_account_id' => $fixtures['fundingBankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ], $fixtures['owner'], $fixtures['president'], $fixtures['treasurer']);

    expect(data_get($ca->data, 'relationship_status'))->toBe('Awaiting Disbursement');

    $dvResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-20004',
        'record_title' => 'DV for Cash Advance',
        'record_date' => now()->toDateString(),
        'amount' => 5000,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'ca',
            'source_document_id' => $ca->id,
            'amount' => 5000,
            'payment_type' => 'Bank Transfer',
            'disbursement_type' => 'Bank Transfer',
            'bank_account_id' => $fixtures['fundingBankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
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
                [
                    'description' => 'Cash advance funding',
                    'account_code' => '2000',
                    'debit' => 0,
                    'credit' => 5000,
                ],
            ],
        ]),
    ])->assertOk();

    $ca = $ca->fresh();
    expect(data_get($ca->data, 'relationship_status'))->toBe('Awaiting Liquidation');
    expect(data_get($ca->data, 'disbursement_status'))->toBe('Fully Disbursed');
    expect(data_get($ca->data, 'total_disbursed_amount'))->toBe('5000.00');
    expect(data_get($ca->data, 'remaining_balance'))->toBe('0.00');
    expect(data_get($ca->data, 'percentage_paid'))->toBe('100.00');

    $lrResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'lr',
        'record_number' => 'LR-20001',
        'record_title' => 'Liquidation Report for Cash Advance',
        'record_date' => now()->toDateString(),
        'amount' => 5000,
        'status' => 'Active',
        'data' => [
            'requester_mode' => 'own_request',
            'requestor' => $fixtures['owner']->name,
            'linked_ca_id' => $ca->id,
            'total_cash_advance' => 5000,
            'purpose' => 'Liquidation of cash advance after the expenses occurred.',
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

    $ca = $ca->fresh();
    $lr = $lr->fresh();

    expect(data_get($lr->data, 'relationship_status'))->toBe('Completed');
    expect(data_get($ca->data, 'relationship_status'))->toBe('Completed');
    expect(data_get($ca->data, 'disbursement_status'))->toBe('Fully Disbursed');
    expect(data_get($ca->data, 'total_disbursed_amount'))->toBe('5000.00');
    expect(data_get($ca->data, 'remaining_balance'))->toBe('0.00');
    expect(data_get($ca->data, 'percentage_paid'))->toBe('100.00');
    expect(data_get($ca->data, 'next_action'))->toBe('No further action');
});

test('liquidation reports must reference the original cash advance', function () {
    $fixtures = financeLifecyclePolicyFixtures();

    $ca = financeCreateAndApproveRecord($this, [
        'module_key' => 'ca',
        'record_number' => 'CA-00002',
        'record_title' => 'Cash Advance - Travel Funds',
        'record_date' => now()->toDateString(),
        'amount' => 3200,
        'status' => 'Active',
        'data' => [
            'requestor' => $fixtures['owner']->name,
            'purpose' => 'Travel funds for approved company activity.',
            'amount_requested' => 3200,
            'mode_of_release' => 'Bank Transfer',
            'bank_account_id' => $fixtures['fundingBankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ], $fixtures['owner'], $fixtures['president'], $fixtures['treasurer']);

    $invalidResponse = $this->actingAs($fixtures['owner'])->postJson(route('finance.store'), [
        'module_key' => 'lr',
        'record_number' => 'LR-30001',
        'record_title' => 'Liquidation Report Without CA',
        'record_date' => now()->toDateString(),
        'amount' => 3200,
        'status' => 'Active',
        'data' => [
            'requester_mode' => 'own_request',
            'requestor' => $fixtures['owner']->name,
            'total_cash_advance' => 3200,
            'purpose' => 'Should fail because the CA reference is missing.',
            'actual_expenses' => 3200,
            'variance' => 0,
            'variance_indicator' => 'Balanced',
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);
    $invalidResponse->assertStatus(422);
    $invalidResponse->assertJsonValidationErrors(['data.linked_ca_id']);

    $validResponse = $this->actingAs($fixtures['owner'])->postJson(route('finance.store'), [
        'module_key' => 'lr',
        'record_number' => 'LR-30002',
        'record_title' => 'Liquidation Report for CA-00002',
        'record_date' => now()->toDateString(),
        'amount' => 3200,
        'status' => 'Active',
        'data' => [
            'requester_mode' => 'own_request',
            'requestor' => $fixtures['owner']->name,
            'linked_ca_id' => $ca->id,
            'total_cash_advance' => 3200,
            'purpose' => 'Liquidation of the original cash advance.',
            'actual_expenses' => 3200,
            'variance' => 0,
            'variance_indicator' => 'Balanced',
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ]);
    $validResponse->assertCreated();

    $lr = FinanceRecord::query()->findOrFail($validResponse->json('data.id'));
    expect((string) data_get($lr->data, 'linked_ca_id'))->toBe((string) $ca->id);
});

test('expense reimbursements complete after dv release and remain linked to the shortage liquidation report', function () {
    $fixtures = financeLifecyclePolicyFixtures();

    $err = financeCreateAndApproveRecord($this, [
        'module_key' => 'err',
        'record_number' => 'ERR-00001',
        'record_title' => 'Expense Reimbursement - Employee Purchase',
        'record_date' => now()->toDateString(),
        'amount' => 1800,
        'status' => 'Active',
        'data' => [
            'requester_mode' => 'own_request',
            'requestor' => $fixtures['owner']->name,
            'linked_lr_id' => $fixtures['shortageLiquidation']->id,
            'amount' => 1800,
            'fund_source' => 'Expense Budget',
            'reimbursement_mode' => 'Bank Transfer',
            'recipient_bank_account' => 'Employee settlement account',
            'recipient_bank_number' => '000-000-000',
            'reimbursement_payment_details' => 'Receipt-backed reimbursement for a personal purchase made for the company.',
            'coa_id' => $fixtures['chartAccount']->id,
        ],
    ], $fixtures['owner'], $fixtures['president'], $fixtures['treasurer']);

    expect(data_get($err->data, 'relationship_status'))->toBe('Awaiting Disbursement Voucher');

    $dvResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'dv',
        'record_number' => 'DV-20005',
        'record_title' => 'DV for Expense Reimbursement',
        'record_date' => now()->toDateString(),
        'amount' => 1800,
        'status' => 'Active',
        'data' => [
            'source_document_type' => 'err',
            'source_document_id' => $err->id,
            'amount' => 1800,
            'payment_type' => 'Bank Transfer',
            'disbursement_type' => 'Bank Transfer',
            'bank_account_id' => $fixtures['fundingBankAccount']->id,
            'coa_id' => $fixtures['chartAccount']->id,
            'line_items' => [
                [
                    'description' => 'Expense reimbursement settlement',
                    'account_code' => '2000',
                    'debit' => 1800,
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
                    'description' => 'Expense reimbursement settlement',
                    'account_code' => '2000',
                    'debit' => 1800,
                    'credit' => 0,
                ],
                [
                    'description' => 'Expense reimbursement funding',
                    'account_code' => '1000',
                    'debit' => 0,
                    'credit' => 1800,
                ],
            ],
        ]),
    ])->assertOk();

    $err = $err->fresh();
    $dv = $dv->fresh();

    expect(data_get($err->data, 'relationship_status'))->toBe('Completed');
    expect(data_get($dv->data, 'relationship_status'))->toBe('Completed');
    expect(data_get($err->data, 'disbursement_status'))->toBe('Fully Disbursed');
    expect(data_get($err->data, 'total_disbursed_amount'))->toBe('1800.00');
    expect(data_get($err->data, 'remaining_balance'))->toBe('0.00');
    expect(data_get($err->data, 'percentage_paid'))->toBe('100.00');
    expect((string) data_get($err->data, 'linked_lr_id'))->toBe((string) $fixtures['shortageLiquidation']->id);
});
