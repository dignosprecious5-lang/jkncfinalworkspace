<?php

use App\Models\FinanceRecord;
use App\Models\DirectorOfficer;
use App\Models\Employee;
use App\Models\GisRecord;
use App\Models\User;
use App\Notifications\FinanceRecordWorkflowNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function financeCashReturnDependencies(User $owner): array
{
    $president = User::factory()->create([
        'name' => 'Cash Return President',
        'email' => 'president@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $president->id,
        'first_name' => 'Cash Return',
        'last_name' => 'President',
        'email' => 'president@example.com',
        'position' => 'President',
        'payroll_type' => 'Monthly Paid',
        'basic_salary' => 0,
        'hourly_rate' => 0,
    ]);

    $treasurer = User::factory()->create([
        'name' => 'Cash Return Treasurer',
        'email' => 'treasurer@example.com',
        'role' => 'admin',
    ]);
    Employee::query()->create([
        'user_id' => $treasurer->id,
        'first_name' => 'Cash Return',
        'last_name' => 'Treasurer',
        'email' => 'treasurer@example.com',
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

    $linkedLiquidation = FinanceRecord::query()->create([
        'module_key' => 'lr',
        'record_number' => 'LR-00001',
        'record_title' => 'Liquidation - Overage',
        'record_date' => now()->toDateString(),
        'amount' => 250,
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $owner->id,
        'approved_at' => now(),
        'data' => [
            'variance_indicator' => 'Overage',
            'variance' => 250,
            'requestor' => 'Cash Return Requestor',
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $bankAccount = FinanceRecord::query()->create([
        'module_key' => 'bank_account',
        'record_number' => 'BA-00001',
        'record_title' => 'Receiving Bank',
        'record_date' => now()->toDateString(),
        'status' => 'Active',
        'workflow_status' => 'Accepted',
        'approval_status' => 'Approved',
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
        'approved_by' => $owner->id,
        'approved_at' => now(),
        'data' => [
            'linked_coa_id' => 1,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $chartAccount = FinanceRecord::query()->create([
        'module_key' => 'chart_account',
        'record_number' => 'CA-00001',
        'record_title' => 'Cash Return Account',
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

    return compact('linkedLiquidation', 'bankAccount', 'chartAccount', 'president', 'treasurer', 'gisRecord');
}

test('cash return form supports attachments history and admin notifications on update and submit', function () {
    Storage::fake('public');
    Notification::fake();

    $owner = User::factory()->create([
        'name' => 'Cash Return Owner',
        'email' => 'owner@example.com',
        'role' => 'employee',
    ]);
    $admin = User::factory()->create([
        'name' => 'Finance Admin',
        'email' => 'admin@example.com',
        'role' => 'admin',
    ]);

    $deps = financeCashReturnDependencies($owner);

    $storeResponse = $this->actingAs($owner)->post(route('finance.store'), [
        'module_key' => 'crf',
        'record_number' => 'CRF-00001',
        'record_title' => 'Cash Return - Overage',
        'record_date' => now()->toDateString(),
        'amount' => 250,
        'status' => 'Active',
        'data' => [
            'requester_mode' => 'own_request',
            'requestor' => 'Cash Return Owner',
            'linked_lr_id' => $deps['linkedLiquidation']->id,
            'amount_returned' => 250,
            'mode_of_return' => 'Cash',
            'receiving_bank_account_id' => $deps['bankAccount']->id,
            'coa_id' => $deps['chartAccount']->id,
            'remarks' => 'Initial cash return submission.',
        ],
        'attachments' => [
            UploadedFile::fake()->create('cash-return-initial.pdf', 50, 'application/pdf'),
        ],
    ]);

    $storeResponse->assertCreated();

    $recordId = $storeResponse->json('data.id');
    $record = FinanceRecord::query()->findOrFail($recordId);

    expect($record->module_key)->toBe('crf');
    expect($record->workflow_status)->toBe('Uploaded');
    expect((array) $record->attachments)->toHaveCount(1);
    expect((array) data_get($record->data, 'history'))->toHaveCount(2);

    $updateResponse = $this->actingAs($owner)->post(route('finance.update', $record), [
        'module_key' => 'crf',
        '_method' => 'PUT',
        'record_number' => 'CRF-00001',
        'record_title' => 'Cash Return - Overage',
        'record_date' => now()->toDateString(),
        'amount' => 250,
        'status' => 'Active',
        'existing_attachments_json' => json_encode($record->attachments ?? [], JSON_THROW_ON_ERROR),
        'data' => [
            'requester_mode' => 'own_request',
            'requestor' => 'Cash Return Owner',
            'linked_lr_id' => $deps['linkedLiquidation']->id,
            'amount_returned' => 250,
            'mode_of_return' => 'Bank Transfer',
            'receiving_bank_account_id' => $deps['bankAccount']->id,
            'coa_id' => $deps['chartAccount']->id,
            'remarks' => 'Updated cash return details with backup document.',
        ],
        'attachments' => [
            UploadedFile::fake()->create('cash-return-update.pdf', 55, 'application/pdf'),
        ],
    ]);

    $updateResponse->assertOk();

    $record->refresh();
    expect((array) $record->attachments)->toHaveCount(2);
    expect((array) data_get($record->data, 'history'))->toHaveCount(2);

    Notification::assertSentTo($admin, FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($record): bool {
        return $notification->action === 'updated'
            && $notification->recordId === $record->id;
    });

    $submitResponse = $this->actingAs($owner)->postJson(route('finance.submit', $record));

    $submitResponse->assertOk();

    $record->refresh();
    expect($record->workflow_status)->toBe('Submitted');
    expect((array) data_get($record->data, 'history'))->toHaveCount(4);

    Notification::assertSentTo($admin, FinanceRecordWorkflowNotification::class, function (FinanceRecordWorkflowNotification $notification) use ($record): bool {
        return $notification->action === 'submitted'
            && $notification->recordId === $record->id;
    });
});
