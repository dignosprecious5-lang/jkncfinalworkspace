<?php

use App\Models\Employee;
use App\Models\FinanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function financeAssetPhotoDependencies(User $owner): array
{
    seedFinanceOfficialApprovers($owner);

    $supplier = FinanceRecord::query()->create([
        'module_key' => 'supplier',
        'record_number' => 'SUP-00001',
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
            'trade_name' => 'Supplier Trading',
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $chartAccount = FinanceRecord::query()->create([
        'module_key' => 'chart_account',
        'record_number' => 'CA-00001',
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
        'record_number' => 'PO-00001',
        'record_title' => 'Approved Purchase Order',
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
            'total_amount' => 15000,
        ],
        'attachments' => [],
        'user' => $owner->name,
    ]);

    $custodian = Employee::query()->create([
        'first_name' => 'Asset',
        'last_name' => 'Custodian',
        'email' => 'custodian@example.com',
        'work_email' => 'custodian@example.com',
        'payroll_type' => 'Monthly Paid',
    ]);

    return compact('supplier', 'po', 'chartAccount', 'custodian');
}

it('stores asset photos as attachments and captures them in history', function () {
    Storage::fake('public');

    $owner = User::factory()->create([
        'name' => 'Asset Owner',
        'email' => 'owner@example.com',
        'role' => 'employee',
    ]);

    $deps = financeAssetPhotoDependencies($owner);

    $response = $this->actingAs($owner)->post(route('finance.store'), [
        'module_key' => 'arf',
        'record_number' => 'ARF-00001',
        'record_title' => 'Office Printer',
        'record_date' => now()->toDateString(),
        'amount' => 15000,
        'status' => 'Active',
        'data' => [
            'item_classification' => 'Fixed Asset',
            'asset_code' => 'FA-PRINTER-001',
            'asset_description' => 'Office printer for records room',
            'linked_po_id' => $deps['po']->id,
            'custodian' => $deps['custodian']->id,
            'useful_life' => 5,
            'remarks' => 'Photo upload regression test.',
        ],
        'attachments' => [
            UploadedFile::fake()->create('purchase-invoice.pdf', 80, 'application/pdf'),
        ],
        'arf_photos' => [
            UploadedFile::fake()->create('printer-front.jpg', 80, 'image/jpeg'),
        ],
    ]);

    $response->assertCreated();

    $recordId = $response->json('data.id');
    $record = FinanceRecord::query()->findOrFail($recordId);

    expect((array) $record->attachments)->toHaveCount(2);
    expect(collect($record->attachments)->contains(fn ($attachment) => data_get($attachment, 'category') === 'Asset Photo'))->toBeTrue();
    expect(collect($record->attachments)->contains(fn ($attachment) => data_get($attachment, 'mime') && str_starts_with((string) data_get($attachment, 'mime'), 'image/')))->toBeTrue();
    expect((array) data_get($record->data, 'history'))->toHaveCount(2);

    $attachmentChange = collect(data_get($record->data, 'history.0.changes', []))->firstWhere('field', 'attachments');
    expect((string) data_get($attachmentChange, 'new_value'))->toContain('Asset Photo');
    expect((string) data_get($attachmentChange, 'new_value'))->toContain('printer-front.jpg');
    expect((string) data_get($attachmentChange, 'new_value'))->toContain('purchase-invoice.pdf');

    $jsonResponse = $this->actingAs($owner)->getJson(route('finance.show', $record));

    $jsonResponse->assertOk();
    $jsonResponse->assertJsonPath('attachments.0.is_image', false);
    $jsonResponse->assertJsonPath('attachments.1.is_image', true);
});
