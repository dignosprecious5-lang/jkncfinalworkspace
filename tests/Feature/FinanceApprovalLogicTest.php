<?php

use App\Models\DirectorOfficer;
use App\Models\Employee;
use App\Models\FinanceRecord;
use App\Models\GisRecord;
use App\Models\User;
use App\Notifications\FinanceRecordWorkflowNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function financeApprovalDirectoryFixtures(): array
{
    $owner = User::factory()->create([
        'name' => 'Finance Requester',
        'email' => 'requester@example.com',
        'role' => 'employee',
    ]);

    $president = User::factory()->create([
        'name' => 'Corporate President',
        'email' => 'president@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $president->id,
        'first_name' => 'Corporate',
        'last_name' => 'President',
        'email' => 'president@example.com',
        'position' => 'President',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $treasurer = User::factory()->create([
        'name' => 'Corporate Treasurer',
        'email' => 'treasurer@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $treasurer->id,
        'first_name' => 'Corporate',
        'last_name' => 'Treasurer',
        'email' => 'treasurer@example.com',
        'position' => 'Treasurer',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $employeeApproverOne = User::factory()->create([
        'name' => 'Employee Approver One',
        'email' => 'employee.approver.one@example.com',
        'role' => 'employee',
    ]);
    Employee::query()->create([
        'user_id' => $employeeApproverOne->id,
        'first_name' => 'Employee',
        'last_name' => 'Approver One',
        'email' => 'employee.approver.one@example.com',
        'position' => 'Finance Supervisor',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $employeeApproverTwo = User::factory()->create([
        'name' => 'Employee Approver Two',
        'email' => 'employee.approver.two@example.com',
        'role' => 'employee',
    ]);
    Employee::query()->create([
        'user_id' => $employeeApproverTwo->id,
        'first_name' => 'Employee',
        'last_name' => 'Approver Two',
        'email' => 'employee.approver.two@example.com',
        'position' => 'Accounting Manager',
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

    return compact(
        'owner',
        'president',
        'treasurer',
        'employeeApproverOne',
        'employeeApproverTwo',
        'gisRecord'
    );
}

test('finance approval routing defaults to officers and allows employee approvers on request modules', function () {
    $fixtures = financeApprovalDirectoryFixtures();

    $defaultResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'chart_account',
        'record_number' => 'CA-90001',
        'record_title' => 'Operating Expense Account',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'data' => [
            'account_type' => 'Expense',
            'normal_balance' => 'Debit',
        ],
    ]);

    $defaultResponse->assertCreated();

    $defaultRecord = FinanceRecord::query()->findOrFail($defaultResponse->json('data.id'));
    expect(data_get($defaultRecord->data, 'first_approver_user_id'))->toBe($fixtures['president']->id);
    expect(data_get($defaultRecord->data, 'second_approver_user_id'))->toBe($fixtures['treasurer']->id);
    expect(data_get($defaultRecord->data, 'approval_steps.0.user_id'))->toBe($fixtures['president']->id);
    expect(data_get($defaultRecord->data, 'approval_steps.1.user_id'))->toBe($fixtures['treasurer']->id);

    $employeeResponse = $this->actingAs($fixtures['owner'])->post(route('finance.store'), [
        'module_key' => 'chart_account',
        'record_number' => 'CA-90002',
        'record_title' => 'Employee-Routed Account',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'data' => [
            'account_type' => 'Expense',
            'normal_balance' => 'Debit',
            'first_approver_user_id' => $fixtures['employeeApproverOne']->id,
            'second_approver_user_id' => $fixtures['employeeApproverTwo']->id,
        ],
    ]);

    $employeeResponse->assertCreated();

    $employeeRecord = FinanceRecord::query()->findOrFail($employeeResponse->json('data.id'));
    expect(data_get($employeeRecord->data, 'approval_steps.0.user_id'))->toBe($fixtures['employeeApproverOne']->id);
    expect(data_get($employeeRecord->data, 'approval_steps.1.user_id'))->toBe($fixtures['employeeApproverTwo']->id);
    expect(data_get($employeeRecord->data, 'approval_steps.0.source'))->toBe('Employee');
    expect(data_get($employeeRecord->data, 'approval_steps.1.source'))->toBe('Employee');
});

test('finance approval actions stay manual when requester is also an approver and hold or revert reasons are audited', function () {
    Notification::fake();

    $fixtures = financeApprovalDirectoryFixtures();
    $admin = User::factory()->create([
        'name' => 'Finance Admin',
        'email' => 'finance.admin@example.com',
        'role' => 'admin',
    ]);

    $storeResponse = $this->actingAs($fixtures['president'])->post(route('finance.store'), [
        'module_key' => 'chart_account',
        'record_number' => 'CA-90003',
        'record_title' => 'Requester Is Also Approver',
        'record_date' => now()->toDateString(),
        'amount' => 0,
        'status' => 'Active',
        'data' => [
            'account_type' => 'Expense',
            'normal_balance' => 'Debit',
        ],
    ]);

    $storeResponse->assertCreated();

    $record = FinanceRecord::query()->findOrFail($storeResponse->json('data.id'));
    expect(data_get($record->data, 'first_approver_user_id'))->toBe($fixtures['president']->id);
    expect(data_get($record->data, 'approval_actions'))->toBeArray()->toHaveCount(0);

    $submitResponse = $this->actingAs($fixtures['president'])->postJson(route('finance.submit', $record));

    $submitResponse->assertOk();

    $record->refresh();
    expect($record->workflow_status)->toBe('Submitted');
    expect($record->approval_status)->toBe('Pending');
    expect((array) data_get($record->data, 'approval_actions'))->toHaveCount(0);

    $holdResponse = $this->actingAs($admin)->postJson(route('finance.hold', $record), [
        'review_note' => 'Missing supporting schedule.',
    ]);

    $holdResponse->assertOk();

    $record->refresh();
    expect($record->workflow_status)->toBe('On Hold');
    expect($record->approval_status)->toBe('On Hold');
    expect(data_get($record->data, 'held_by_name'))->toBe($admin->name);
    expect(data_get($record->data, 'history'))->toBeArray();
    $holdHistory = collect(data_get($record->data, 'history'))->firstWhere('action', 'Placed On Hold');
    expect(data_get($holdHistory, 'reason'))->toBe('Missing supporting schedule.');

    Notification::assertSentTo($fixtures['president'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($record): bool {
        return $notification->action === 'held'
            && $notification->recordId === $record->id
            && $notification->reviewNote === 'Missing supporting schedule.';
    });

    $revertResponse = $this->actingAs($admin)->postJson(route('finance.revert', $record), [
        'reason' => 'Please attach the supporting schedule before resubmission.',
    ]);

    $revertResponse->assertOk();

    $record->refresh();
    expect($record->workflow_status)->toBe('Reverted');
    expect($record->approval_status)->toBe('Needs Revision');
    expect(data_get($record->data, 'reverted_by_name'))->toBe($admin->name);
    expect(data_get($record->data, 'approved_by_name'))->toBeNull();
    expect((array) data_get($record->data, 'approval_actions'))->toHaveCount(0);
    expect(data_get($record->data, 'history'))->toBeArray();
    $revertHistory = collect(data_get($record->data, 'history'))->firstWhere('action', 'Reverted');
    expect(data_get($revertHistory, 'reason'))->toBe('Please attach the supporting schedule before resubmission.');

    Notification::assertSentTo($fixtures['president'], FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($record): bool {
        return $notification->action === 'reverted'
            && $notification->recordId === $record->id
            && $notification->reviewNote === 'Please attach the supporting schedule before resubmission.';
    });
});
