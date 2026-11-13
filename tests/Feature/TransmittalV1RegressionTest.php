<?php

use App\Models\Transmittal;
use App\Models\TransmittalReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TransmittalV1Fixture;

uses(TransmittalV1Fixture::class);

beforeEach(function (): void {
    $this->setUpTransmittalV1Fixture();
});

afterEach(function (): void {
    $this->travelBack();
    DB::purge('transmittal_v1_regression');
});

test('V1 routes retain authentication and reject anonymous requests without changing data', function (): void {
    $owner = $this->v1User();
    $record = $this->v1Record($owner);
    $receipt = $this->v1Receipt($record);
    $before = $this->v1BusinessSnapshot($record);
    foreach (['transmittal.index', 'transmittal.store', 'transmittal.submit', 'transmittal.preview',
        'transmittal.preview.pdf', 'transmittal.receipt.pdf', 'transmittal.receipts.show',
        'admin.transmittal.dashboard', 'corporate.approvals.approve', 'corporate.approvals.reject', 'corporate.approvals.revise'] as $name) {
        $route = Route::getRoutes()->getByName($name);
        expect($route)->not->toBeNull();
        expect($route->gatherMiddleware())->toContain('auth');
    }
    foreach (['/transmittal', '/transmittal/'.$record->id.'/preview', '/transmittal/'.$record->id.'/preview-pdf',
        '/transmittal/'.$record->id.'/receipt-pdf', '/transmittal-receipts/'.$receipt->id, '/admin/transmittal-dashboard'] as $url) {
        $this->getJson($url)->assertUnauthorized();
    }
    foreach (['/transmittal', '/transmittal/'.$record->id.'/submit',
        '/admin/corporate-approvals/transmittal/'.$record->id.'/approve'] as $url) {
        $this->postJson($url)->assertUnauthorized();
    }
    expect($this->v1BusinessSnapshot($record))->toBe($before);
    $this->assertDatabaseHas('transmittals', ['id' => $record->id, 'workflow_status' => 'Uploaded']);
    $this->assertDatabaseCount('transmittals', 1);
    $this->assertDatabaseCount('transmittal_receipts', 1);
    Mail::assertNothingSent();
    Notification::assertNothingSent();
});

test('V1 accepts the legacy form and preserves item and attachment bytes without V2 fields', function (): void {
    $user = $this->v1User();
    $itemFile = UploadedFile::fake()->createWithContent('legacy-item.pdf', "%PDF-1.4\n% Synthetic V1 attachment\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] /Contents 4 0 R >>\nendobj\n4 0 obj\n<< /Length 0 >>\nstream\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000035 00000 n \n0000000084 00000 n \n0000000141 00000 n \n0000000228 00000 n \ntrailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n276\n%%EOF\n");
    $attachment = UploadedFile::fake()->createWithContent('support.txt', 'isolated supporting document bytes');
    $itemBytes = file_get_contents($itemFile->getRealPath());
    $attachmentBytes = file_get_contents($attachment->getRealPath());
    $response = $this->actingAs($user)->withHeader('Accept', 'application/json')->call('POST', '/transmittal', [
        'transmittal_date' => '2026-10-21', 'mode' => 'SEND', 'party_name' => 'Fixture Counterparty',
        'office_name' => 'Fixture Office', 'address' => 'Original delivery address', 'delivery_type' => 'By Person',
        'by_person_who' => 'Fixture Courier', 'approved_by_name' => 'Written Operations Manager',
        'approved_position' => 'Operations Manager', 'document_custodian' => 'Fixture Custodian',
        'receiver_affiliation' => 'Original receiving office', 'action_delivery' => '1', 'action_email' => '0',
        'items' => json_encode([['no' => 7, 'particular' => 'Legacy document', 'unique_id' => 'LEGACY-007',
            'qty' => 3, 'description' => 'Original description', 'remarks' => 'Original remarks'], []], JSON_THROW_ON_ERROR),
        'upload_manifest' => json_encode([
            'version' => 1,
            'item_files' => [['index' => 0, 'size' => $itemFile->getSize()]],
            'attachments' => [['index' => 0, 'size' => $attachment->getSize()]],
        ], JSON_THROW_ON_ERROR),
    ], [], ['item_files' => [$itemFile], 'attachments' => [$attachment]]);
    $response->assertOk()->assertJsonPath('message', 'Transmittal saved successfully.');
    $id = $response->json('id');
    $this->assertDatabaseHas('transmittals', [
        'id' => $id, 'transmittal_no' => 'TRN-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT),
        'mode' => 'SEND', 'party_name' => 'Fixture Counterparty', 'office_name' => 'Fixture Office',
        'approved_by_name' => 'Written Operations Manager', 'receiver_affiliation' => 'Original receiving office',
        'workflow_status' => 'Uploaded', 'approval_status' => 'Pending', 'submitted_by' => $user->id,
        'action_delivery' => true, 'action_email' => false,
    ]);
    $this->assertDatabaseCount('transmittals', 1);
    $this->assertDatabaseCount('transmittal_items', 1);
    $this->assertDatabaseCount('transmittal_attachments', 1);
    $this->assertDatabaseHas('transmittal_items', ['transmittal_id' => $id, 'item_no' => 7,
        'unique_id' => 'LEGACY-007', 'qty' => 3, 'description' => 'Original description', 'remarks' => 'Original remarks']);
    $record = Transmittal::query()->findOrFail($id);
    expect(Storage::disk('public')->get($record->items->first()->attachment_path))->toBe($itemBytes);
    expect(Storage::disk('public')->get($record->attachments->first()->file_path))->toBe($attachmentBytes);
    $this->assertDatabaseCount('transmittal_receipts', 0);
    Mail::assertNothingSent();
});

test('V1 rejects an invalid legacy mode without creating records', function (): void {
    $this->actingAs($this->v1User())->postJson('/transmittal', [
        'mode' => 'INVALID', 'party_name' => 'Fixture Party', 'office_name' => 'Fixture Office',
        'upload_manifest' => json_encode(['version' => 1, 'item_files' => [], 'attachments' => []], JSON_THROW_ON_ERROR),
    ])->assertUnprocessable()->assertJsonValidationErrors('mode');
    $this->assertDatabaseCount('transmittals', 0);
    $this->assertDatabaseCount('transmittal_items', 0);
});

test('V1 requires party and office names', function (): void {
    $this->actingAs($this->v1User())->postJson('/transmittal', [
        'mode' => 'SEND',
        'upload_manifest' => json_encode(['version' => 1, 'item_files' => [], 'attachments' => []], JSON_THROW_ON_ERROR),
    ])
        ->assertUnprocessable()->assertJsonPath('message', 'Party name and office name are required.');
    $this->assertDatabaseCount('transmittals', 0);
});

test('V1 submits uploaded and reverted records while preserving legacy fields and files', function (string $state): void {
    $owner = $this->v1User();
    $record = $this->v1Record($owner, ['workflow_status' => $state, 'review_note' => 'Existing review note']);
    $before = $this->v1BusinessSnapshot($record);
    $this->actingAs($owner)->postJson('/transmittal/'.$record->id.'/submit')->assertOk();
    $this->assertDatabaseHas('transmittals', ['id' => $record->id, 'workflow_status' => 'Submitted',
        'approval_status' => 'Pending', 'submitted_by' => $owner->id, 'review_note' => 'Existing review note']);
    expect($this->v1BusinessSnapshot($record))->toBe($before);
    $this->assertDatabaseCount('transmittal_receipts', 0);
})->with(['Uploaded', 'Reverted']);

test('V1 refuses submission from other states without changing existing data', function (string $state): void {
    $owner = $this->v1User();
    $record = $this->v1Record($owner, ['workflow_status' => $state]);
    $before = (array) DB::table('transmittals')->find($record->id);
    $this->actingAs($owner)->postJson('/transmittal/'.$record->id.'/submit')->assertUnprocessable();
    expect((array) DB::table('transmittals')->find($record->id))->toBe($before);
})->with(['Submitted', 'Accepted', 'Archived']);

test('V1 corporate decisions require permission and preserve denied requests unchanged', function (): void {
    $owner = $this->v1User();
    $record = $this->v1Record($owner, ['workflow_status' => 'Submitted']);
    $before = (array) DB::table('transmittals')->find($record->id);
    $this->actingAs($owner)->get('/admin/transmittal-dashboard')->assertForbidden();
    foreach (['approve', 'reject', 'revise'] as $decision) {
        $this->postJson('/admin/corporate-approvals/transmittal/'.$record->id.'/'.$decision,
            ['review_note' => 'Must not be applied'])->assertForbidden();
    }
    expect((array) DB::table('transmittals')->find($record->id))->toBe($before);
    $this->assertDatabaseCount('transmittal_receipts', 0);
    Mail::assertNothingSent();
    Notification::assertNothingSent();
});

test('V1 approval creates a receipt and keeps the written manager separate from the system approver', function (string $mode): void {
    $owner = $this->v1User();
    $approver = $this->v1User(true);
    $record = $this->v1Record($owner, ['mode' => $mode, 'workflow_status' => 'Submitted', 'review_note' => 'Old note']);
    $before = $this->v1BusinessSnapshot($record);
    $usersBefore = DB::table('users')->orderBy('id')->get()->toJson();
    $this->actingAs($approver)->from('/admin/corporate-dashboard')
        ->post('/admin/corporate-approvals/transmittal/'.$record->id.'/approve')
        ->assertRedirect('/admin/corporate-dashboard')->assertSessionHas('success');
    $this->assertDatabaseHas('transmittals', ['id' => $record->id, 'workflow_status' => 'Accepted',
        'approval_status' => 'Approved', 'approved_by' => $approver->id,
        'approved_by_name' => 'Fixture Operations Manager', 'review_note' => null]);
    expect($record->fresh()->approved_at)->not->toBeNull();
    $receipt = TransmittalReceipt::query()->where('transmittal_id', $record->id)->sole();
    expect($receipt->receipt_no)->toMatch('/^TRR-\d{5}$/');
    $this->assertDatabaseHas('transmittal_receipts', [
        'id' => $receipt->id, 'mode' => $mode, 'generated_by' => $approver->id,
        'from_name' => $mode === 'SEND' ? 'Fixture Origin Office' : 'Fixture Counterparty',
        'to_name' => $mode === 'SEND' ? 'Fixture Counterparty' : 'Fixture Origin Office',
        'approved_by_name' => 'Fixture Operations Manager', 'approved_position' => 'Operations Manager',
        'delivery_detail' => 'By Person - Fixture Courier', 'actions_summary' => 'Delivery, Drop Off',
    ]);
    expect($this->v1BusinessSnapshot($record))->toBe($before);
    expect(DB::table('users')->orderBy('id')->get()->toJson())->toBe($usersBefore);
})->with(['SEND', 'RECEIVE']);

test('V1 preserves an existing receipt instead of replacing it during approval', function (): void {
    $owner = $this->v1User();
    $approver = $this->v1User(true);
    $record = $this->v1Record($owner, ['workflow_status' => 'Submitted']);
    $receipt = $this->v1Receipt($record, ['receipt_no' => 'RETAIN-ORIGINAL-RECEIPT']);
    $before = (array) DB::table('transmittal_receipts')->find($receipt->id);
    $this->actingAs($approver)->post('/admin/corporate-approvals/transmittal/'.$record->id.'/approve')
        ->assertRedirect()->assertSessionHas('success');
    $this->assertDatabaseCount('transmittal_receipts', 1);
    expect((array) DB::table('transmittal_receipts')->find($receipt->id))->toBe($before);
});

test('V1 refuses decisions on records that are not submitted', function (string $state): void {
    $owner = $this->v1User();
    $approver = $this->v1User(true);
    $record = $this->v1Record($owner, ['workflow_status' => $state]);
    $before = (array) DB::table('transmittals')->find($record->id);
    foreach (['approve', 'reject', 'revise'] as $decision) {
        $this->actingAs($approver)->from('/admin/corporate-dashboard')
            ->post('/admin/corporate-approvals/transmittal/'.$record->id.'/'.$decision, ['review_note' => 'Not applied'])
            ->assertRedirect('/admin/corporate-dashboard')->assertSessionHas('error');
    }
    expect((array) DB::table('transmittals')->find($record->id))->toBe($before);
    $this->assertDatabaseCount('transmittal_receipts', 0);
    Mail::assertNothingSent();
    Notification::assertNothingSent();
})->with(['Uploaded', 'Accepted', 'Reverted', 'Archived']);

test('V1 revision and rejection preserve legacy fields and record the review decision', function (string $decision, string $approvalStatus): void {
    $owner = $this->v1User();
    $approver = $this->v1User(true);
    $record = $this->v1Record($owner, ['workflow_status' => 'Submitted']);
    $before = $this->v1BusinessSnapshot($record);
    $this->actingAs($approver)->post('/admin/corporate-approvals/transmittal/'.$record->id.'/'.$decision,
        ['review_note' => 'Fixture review reason'])->assertRedirect()->assertSessionHas('success');
    $this->assertDatabaseHas('transmittals', ['id' => $record->id, 'workflow_status' => 'Reverted',
        'approval_status' => $approvalStatus, 'review_note' => 'Fixture review reason', 'approved_by' => $approver->id]);
    expect($this->v1BusinessSnapshot($record))->toBe($before);
    $this->assertDatabaseCount('transmittal_receipts', 0);
})->with([['revise', 'Needs Revision'], ['reject', 'Rejected']]);

test('V1 preview retains its view and PDF links without modifying legacy records', function (): void {
    $owner = $this->v1User();
    $record = $this->v1Record($owner, ['workflow_status' => 'Accepted', 'approval_status' => 'Approved']);
    $receipt = $this->v1Receipt($record);
    $before = $this->v1BusinessSnapshot($record);
    $receiptBefore = (array) DB::table('transmittal_receipts')->find($receipt->id);
    $this->actingAs($owner)->get('/transmittal/'.$record->id.'/preview')
        ->assertOk()->assertViewIs('transmittal.preview')
        ->assertViewHas('transmittal', fn ($value) => $value->id === $record->id)
        ->assertViewHas('transmittalPdfUrl', route('transmittal.preview.pdf', $record->id))
        ->assertViewHas('receiptPdfUrl', route('transmittal.receipt.pdf', $record->id));
    expect($this->v1BusinessSnapshot($record))->toBe($before);
    expect((array) DB::table('transmittal_receipts')->find($receipt->id))->toBe($receiptBefore);
});

test('V1 preview PDF preserves its data, filename and renderer response', function (): void {
    $owner = $this->v1User();
    $approver = $this->v1User(true);
    $record = $this->v1Record($owner, ['approved_by' => $approver->id]);
    $before = $this->v1BusinessSnapshot($record);
    $this->expectV1Pdf('transmittal.preview-pdf', function (array $data) use ($record, $approver): bool {
        expect($data['transmittal']->id)->toBe($record->id);
        expect($data['transmittal']->approved_by_name)->toBe('Fixture Operations Manager');
        expect($data['approvedByDisplay'])->toBe($approver->name);
        expect($data)->toHaveKey('corporateContext');
        return true;
    }, 'a4', 'portrait', ['isPhpEnabled' => true, 'isRemoteEnabled' => true], 'transmittal-'.$record->transmittal_no.'.pdf');
    $this->actingAs($owner)->get('/transmittal/'.$record->id.'/preview-pdf')->assertOk()
        ->assertHeader('content-type', 'application/pdf')->assertContent('isolated PDF renderer response');
    expect($this->v1BusinessSnapshot($record))->toBe($before);
});

test('V1 receipt PDF preserves the receipt and SEND or RECEIVE party directions', function (string $mode): void {
    $owner = $this->v1User();
    $record = $this->v1Record($owner, ['mode' => $mode]);
    $receipt = $this->v1Receipt($record);
    $before = (array) DB::table('transmittal_receipts')->find($receipt->id);
    $this->expectV1Pdf('transmittal.receipt-pdf', function (array $data) use ($record, $receipt, $mode): bool {
        expect($data['transmittal']->id)->toBe($record->id);
        expect($data['receipt']->id)->toBe($receipt->id);
        expect($data['fromValue'])->toBe($mode === 'SEND' ? 'Fixture Origin Office' : 'Fixture Counterparty');
        expect($data['toValue'])->toBe($mode === 'SEND' ? 'Fixture Counterparty' : 'Fixture Origin Office');
        expect($data['approvedByText'])->toBe('Fixture Operations Manager (Operations Manager)');
        return true;
    }, [0, 0, 612, 255], null, null, 'receipt-'.$receipt->receipt_no.'.pdf');
    $this->actingAs($owner)->get('/transmittal/'.$record->id.'/receipt-pdf')->assertOk()
        ->assertHeader('content-type', 'application/pdf')->assertContent('isolated PDF renderer response');
    expect((array) DB::table('transmittal_receipts')->find($receipt->id))->toBe($before);
    $this->assertDatabaseCount('transmittal_receipts', 1);
})->with(['SEND', 'RECEIVE']);

test('V1 returns not found when a receipt PDF has no receipt', function (): void {
    $owner = $this->v1User();
    $record = $this->v1Record($owner);
    Pdf::shouldReceive('loadView')->never();
    $this->actingAs($owner)->get('/transmittal/'.$record->id.'/receipt-pdf')->assertNotFound();
    $this->assertDatabaseCount('transmittal_receipts', 0);
});

test('V1 receipt page resolves its receipt identifier without rewriting the snapshot', function (): void {
    $owner = $this->v1User();
    $record = $this->v1Record($owner);
    $receipt = $this->v1Receipt($record);
    $before = (array) DB::table('transmittal_receipts')->find($receipt->id);
    $this->actingAs($owner)->get('/transmittal-receipts/'.$receipt->id)->assertOk()
        ->assertViewIs('transmittal.receipt')
        ->assertViewHas('receipt', fn ($value) => $value->id === $receipt->id && $value->transmittal_id === $record->id);
    expect((array) DB::table('transmittal_receipts')->find($receipt->id))->toBe($before);
});

test('V1 ordinary saves cannot replace the written Operations Manager', function (): void {
    $owner = $this->v1User();
    $record = $this->v1Record($owner);
    $before = $this->v1BusinessSnapshot($record);
    $record->update(['approved_by_name' => 'Different System User', 'review_note' => 'Unrelated note']);
    expect($record->fresh()->approved_by_name)->toBe('Fixture Operations Manager');
    expect($this->v1BusinessSnapshot($record))->toBe($before);
});
