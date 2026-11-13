<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\TransmittalV2\TransmittalAcknowledgmentToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\Support\TransmittalV2Isolation;
use Tests\TestCase;

if (! defined('TRANSMITTAL_V2_BOOTSTRAPPED') || TRANSMITTAL_V2_BOOTSTRAPPED !== true) {
    throw new \RuntimeException('V2 HTTP acceptance requires the dedicated isolated launcher.');
}

/** Application-registered gated routes and hand-built SQLite :memory: schema; no migrations. */
final class TransmittalV2HttpAcceptanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TransmittalV2Isolation::assertBootedGuards($this->app);
        DB::purge(TransmittalV2Isolation::CONNECTION);
        $connection = DB::connection(TransmittalV2Isolation::CONNECTION);
        self::assertSame(':memory:', $connection->getDatabaseName());
        self::assertSame('sqlite', $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        self::assertSame([], $connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"));
        $this->createSyntheticSchema();

        config(['transmittal_v2.enabled' => false]);
        $router = $this->app->make('router');
        self::assertNotNull($router->getRoutes()->getByName('transmittals.index'), 'The source-registered V2 routes were not loaded.');
        self::assertNotNull($router->getRoutes()->getByName('transmittal-v2.acknowledgments.show'), 'The source-registered acknowledgment route was not loaded.');
        self::assertNotNull($router->getRoutes()->getByName('transmittal.index'), 'The V1 route was lost during V2 registration.');
        $manifest = json_decode(file_get_contents(base_path('patches/transmittal-v2/transmittal-v2-route-manifest.json')), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(32, $manifest['routes']);
        foreach ($manifest['routes'] as $entry) {
            $registered = $router->getRoutes()->getByName($entry['name']);
            self::assertNotNull($registered, $entry['name'].' is missing from the application route table.');
            self::assertSame(ltrim($entry['uri'], '/'), $registered->uri(), $entry['name'].' URI differs from the manifest.');
            self::assertContains($entry['method'], $registered->methods(), $entry['name'].' method differs from the manifest.');
            self::assertContains(\App\Http\Middleware\TransmittalV2\EnsureTransmittalV2Enabled::class, $registered->gatherMiddleware(), $entry['name'].' is missing its V2 feature gate.');
        }
        $this->get('/transmittals')->assertNotFound();

        config(['transmittal_v2.enabled' => true]);
        config(['transmittal_v2.optional_features.smart_upload' => false, 'transmittal_v2.optional_features.ocr' => false]);
        $this->get('/transmittals/smart-upload')->assertNotFound();
        $this->postJson('/transmittals/analyze', [])->assertNotFound();
        self::assertNotNull($router->getRoutes()->getByName('transmittals.approve'), 'Application approval route was not registered.');
        self::assertNotNull($router->getRoutes()->getByName('transmittal-v2.acknowledgments.store'), 'Test-only acknowledgment route was not registered.');
        self::assertNotNull($router->getRoutes()->getByName('transmittal-v2.acknowledgments.documents.download'), 'Application document route was not registered.');
        TransmittalV2Isolation::assertBootedGuards($this->app);
    }

    protected function tearDown(): void
    {
        try {
            TransmittalV2Isolation::assertBootedGuards($this->app);
            DB::purge(TransmittalV2Isolation::CONNECTION);
        } finally {
            parent::tearDown();
        }
    }

    public function test_http_transmit_rejects_v2_transmittal_without_recipients(): void
    {
        $owner = $this->user('employee');
        $transmittal = $this->transmittal('v2', $owner->id, 'approved');
        config(['transmittal_v2.enabled' => true, 'transmittal_v2.dispatch.enabled' => true,
            'transmittal_v2.dispatch.provider' => 'mock']);

        $this->actingAs($owner)->postJson(route('transmittals.transmit', $transmittal))
            ->assertUnprocessable()->assertJsonValidationErrors('recipients');
        self::assertSame(0, DB::table('transmittal_recipients')->count());
    }

    public function test_http_approval_enforces_feature_gate_role_separation_and_v2_scope(): void
    {
        $owner = $this->user('admin');
        $otherAdmin = $this->user('admin');
        $employee = $this->user('employee');
        $v2 = $this->transmittal('v2', $owner->id, 'pending_approval');
        $v1 = $this->transmittal('v1', $owner->id, 'pending_approval');
        $this->recipient($v2, 'recipient@example.invalid', str_repeat('A', 64));
        DB::table('transmittal_workspace_items')->insert([
            'transmittal_id' => $v2, 'position' => 1, 'line_number' => 1,
            'particular' => 'Synthetic document', 'quantity' => '0', 'created_at' => now(), 'updated_at' => now(),
        ]);

        config(['transmittal_v2.enabled' => false]);
        $this->actingAs($otherAdmin)->post(route('transmittals.approve', $v2))->assertNotFound();
        self::assertSame('pending_approval', DB::table('transmittals')->where('id', $v2)->value('status'));

        config(['transmittal_v2.enabled' => true]);
        $this->actingAs($owner)->post(route('transmittals.approve', $v2))->assertForbidden();
        $this->actingAs($employee)->post(route('transmittals.approve', $v2))->assertForbidden();
        $this->actingAs($otherAdmin)->post(route('transmittals.approve', $v1))->assertNotFound();
        self::assertSame(0, DB::table('approval_authority_codes')->count());

        $this->actingAs($otherAdmin)->post(route('transmittals.approve', $v2))->assertRedirect(route('transmittals.show', $v2));
        $parent = DB::table('transmittals')->where('id', $v2)->first();
        self::assertSame('approved', $parent->status);
        self::assertSame('v2', $parent->workflow_version);
        self::assertSame(1, DB::table('approval_authority_codes')->where('transmittal_id', $v2)->count());
        self::assertSame(1, DB::table('transmittal_histories')->where('transmittal_id', $v2)->where('action', 'approved')->count());
        $snapshot = json_decode($parent->approved_snapshot, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('0', $snapshot['items'][0]['quantity']);

        $this->actingAs($otherAdmin)->postJson(route('transmittals.approve', $v2))->assertForbidden();
        self::assertSame(1, DB::table('approval_authority_codes')->where('transmittal_id', $v2)->count());
    }

    public function test_http_acknowledgment_is_token_scoped_idempotent_and_renders_pdf(): void
    {
        $parent = $this->transmittal('v2', null, 'sent');
        $token = str_repeat('AckToken_7', 6);
        $recipient = $this->recipient($parent, 'recipient@example.invalid', $token);
        DB::table('proof_of_transmissions')->insert([
            'transmittal_id' => $parent, 'recipient_id' => $recipient, 'channel' => 'email',
            'status' => 'sent', 'transaction_reference' => 'synthetic-provider-ref',
            'transmitted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post(route('transmittal-v2.acknowledgments.store', $token), [
            'signed_name' => 'Synthetic Recipient', 'comments' => 'Received', 'confirmation_check' => '1',
        ])->assertRedirect(route('transmittal-v2.acknowledgments.show', $token));

        self::assertSame(1, DB::table('transmittal_receipts')->where('transmittal_id', $parent)->where('recipient_id', $recipient)->count());
        self::assertSame(1, DB::table('external_acknowledgments')->where('recipient_id', $recipient)->where('event', 'acknowledged')->count());
        self::assertSame(1, DB::table('proof_of_transmissions')->where('recipient_id', $recipient)->where('status', 'acknowledged')->count());
        self::assertSame('acknowledged', DB::table('transmittals')->where('id', $parent)->value('status'));
        self::assertSame(TransmittalAcknowledgmentToken::hash($token), DB::table('transmittal_recipients')->where('id', $recipient)->value('acknowledgment_token_hash'));

        $receipt = DB::table('transmittal_receipts')->where('recipient_id', $recipient)->first();
        $this->post(route('transmittal-v2.acknowledgments.store', $token), [
            'signed_name' => 'Synthetic Recipient', 'confirmation_check' => '1',
        ])->assertRedirect(route('transmittal-v2.acknowledgments.show', $token));
        self::assertSame(1, DB::table('transmittal_receipts')->where('recipient_id', $recipient)->count());

        $pdfResponse = $this->get(route('transmittal-v2.acknowledgments.receipts.pdf', ['token' => $token, 'receipt' => $receipt->id]));
        $pdfResponse->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-', (string) $pdfResponse->getContent());
    }

    public function test_actual_transmit_route_fails_closed_without_sending_or_mutating_v2_state(): void
    {
        $actor = $this->user('admin');
        $parent = $this->transmittal('v2', $actor->id, 'approved');
        $recipient = $this->recipient($parent, 'blocked@example.invalid', str_repeat('Blocked_1', 7));
        DB::table('transmittal_recipients')->where('id', $recipient)->update([
            'sent_at' => null,
            'acknowledgment_token_hash' => null,
            'acknowledgment_expires_at' => null,
        ]);

        config(['transmittal_v2.enabled' => true]);
        Http::preventStrayRequests();
        $this->actingAs($actor)->post(route('transmittals.transmit', $parent))->assertStatus(503);

        self::assertNull(DB::table('transmittal_recipients')->where('id', $recipient)->value('sent_at'));
        self::assertSame(0, DB::table('proof_of_transmissions')->count());
        self::assertFalse(Schema::hasTable('transmittal_email_dispatches'));
        Http::assertNothingSent();
    }

    public function test_manual_draft_edit_uploads_and_downloads_a_private_document_without_ocr(): void
    {
        config(['transmittal_v2.enabled' => true]);
        $form = [
            'intent' => 'draft',
            'subject' => 'Manual attachment acceptance',
            'purpose' => 'Verify manual document attachment without OCR.',
            'direction' => 'send',
            'transmittal_date' => now()->toDateString(),
            'origin_office' => 'Synthetic Office',
            'confidentiality' => 'standard',
        ];

        $created = $this->postJson(route('transmittals.store'), $form)->assertOk();
        $transmittalId = (int) $created->json('id');
        self::assertGreaterThan(0, $transmittalId);
        self::assertSame('v2', DB::table('transmittals')->where('id', $transmittalId)->value('workflow_version'));
        self::assertSame('draft', DB::table('transmittals')->where('id', $transmittalId)->value('status'));

        $updated = $this->put(route('transmittals.update', $transmittalId), $form + [
            'documents' => [UploadedFile::fake()->createWithContent('manual.pdf', "%PDF-1.4\nsynthetic private file\n")],
        ]);
        $updated->assertRedirect()->assertSessionHasNoErrors();

        $document = DB::table('transmittal_documents')->where('transmittal_id', $transmittalId)->first();
        self::assertNotNull($document);
        self::assertSame('v2', DB::table('transmittals')->where('id', $transmittalId)->value('workflow_version'));
        self::assertTrue(Storage::disk('local')->exists($document->storage_path));
        $response = $this->get(route('transmittals.documents.download', [$transmittalId, $document->id]));
        $response->assertOk()->assertHeader('x-content-type-options', 'nosniff');
        self::assertNotSame('', $response->streamedContent());
        self::assertSame(0, DB::table('transmittal_histories')->where('transmittal_id', $transmittalId)->where('action', 'ocr_analyzed')->count());
    }

    public function test_private_document_http_download_checks_token_parent_and_content_hash(): void
    {
        $parent = $this->transmittal('v2', null, 'sent');
        $token = str_repeat('Download_9', 6);
        $this->recipient($parent, 'download@example.invalid', $token);
        $bytes = "%PDF-1.4\nSynthetic private document\n";
        $path = 'synthetic/'.$parent.'/document.pdf';
        Storage::disk('local')->put($path, $bytes);
        $document = DB::table('transmittal_documents')->insertGetId([
            'transmittal_id' => $parent, 'category' => 'document', 'original_name' => 'document.pdf',
            'storage_path' => $path, 'mime_type' => 'application/pdf', 'size_bytes' => strlen($bytes),
            'sha256' => hash('sha256', $bytes), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->get(route('transmittal-v2.acknowledgments.documents.download', ['token' => $token, 'document' => $document]));
        $response->assertOk()->assertHeader('x-content-type-options', 'nosniff');
        self::assertSame($bytes, $response->streamedContent());

        $badToken = str_repeat('Invalid_4', 6);
        $this->get(route('transmittal-v2.acknowledgments.documents.download', ['token' => $badToken, 'document' => $document]))->assertNotFound();
        DB::table('transmittal_documents')->where('id', $document)->update(['sha256' => str_repeat('0', 64)]);
        $this->get(route('transmittal-v2.acknowledgments.documents.download', ['token' => $token, 'document' => $document]))->assertStatus(409);
    }

    private function createSyntheticSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name')->nullable(); $table->string('email')->nullable();
            $table->string('role')->nullable(); $table->string('password')->nullable();
            $table->rememberToken(); $table->timestamps();
        });
        Schema::create('transmittals', function (Blueprint $table): void {
            $table->id(); $table->string('workflow_version', 2)->nullable(); $table->unsignedBigInteger('prepared_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable(); $table->unsignedBigInteger('rejected_by')->nullable();
            $table->string('mode')->nullable(); $table->string('status')->nullable(); $table->string('workflow_status')->nullable(); $table->string('approval_status')->nullable();
            $table->string('tracking_number')->nullable(); $table->string('transmittal_no')->nullable(); $table->string('external_reference')->nullable();
            $table->string('subject')->nullable(); $table->text('purpose')->nullable(); $table->string('direction')->nullable();
            $table->date('transmittal_date')->nullable(); $table->string('origin_office')->nullable(); $table->string('recipient_address')->nullable();
            $table->string('related_company')->nullable(); $table->string('delivery_method')->nullable(); $table->string('delivery_type')->nullable();
            $table->string('by_person_who')->nullable(); $table->string('registered_mail_provider')->nullable();
            $table->json('delivery_actions')->nullable(); $table->boolean('action_delivery')->default(false); $table->boolean('action_pick_up')->default(false);
            $table->boolean('action_drop_off')->default(false); $table->boolean('action_email')->default(false);
            $table->string('prepared_by_name')->nullable(); $table->timestamp('prepared_at')->nullable();
            $table->string('operations_manager')->nullable(); $table->string('delivered_by')->nullable(); $table->string('received_by')->nullable();
            $table->string('processing_company')->nullable(); $table->string('document_custodian')->nullable(); $table->string('confidentiality')->nullable();
            $table->timestamp('approved_at')->nullable(); $table->string('approved_by_name')->nullable(); $table->json('approved_snapshot')->nullable();
            $table->string('approval_authority_code')->nullable(); $table->timestamp('rejected_at')->nullable();
            $table->string('edit_token_hash', 64)->nullable();
            $table->string('rejection_reason')->nullable(); $table->text('review_note')->nullable();
            $table->timestamp('received_at')->nullable(); $table->string('recipient_email')->nullable(); $table->string('office_name')->nullable();
            $table->string('party_name')->nullable(); $table->string('address')->nullable(); $table->string('receiver_affiliation')->nullable();
            $table->string('electronic_method')->nullable(); $table->string('approved_position')->nullable();
            $table->timestamps();
        });
        Schema::create('transmittal_recipients', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->string('name')->nullable();
            $table->string('email')->nullable(); $table->string('organization')->nullable(); $table->char('acknowledgment_token_hash', 64)->nullable();
            $table->timestamp('acknowledgment_expires_at')->nullable(); $table->timestamp('acknowledgment_revoked_at')->nullable();
            $table->timestamp('sent_at')->nullable(); $table->timestamps();
        });
        Schema::create('transmittal_documents', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('category')->nullable(); $table->string('original_name'); $table->string('storage_path');
            $table->string('mime_type')->nullable(); $table->unsignedBigInteger('size_bytes')->nullable(); $table->char('sha256', 64);
            $table->text('ocr_text')->nullable(); $table->json('extracted_data')->nullable(); $table->timestamps();
        });
        Schema::create('transmittal_workspace_items', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedInteger('position')->default(0); $table->unsignedInteger('line_number')->nullable();
            $table->string('particular')->nullable(); $table->string('unique_id')->nullable(); $table->string('quantity')->nullable();
            $table->text('description')->nullable(); $table->text('remarks')->nullable(); $table->timestamps();
        });
        Schema::create('transmittal_attachments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->string('file_path')->nullable();
            $table->string('storage_path')->nullable(); $table->string('original_name')->nullable(); $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable(); $table->timestamps();
        });
        Schema::create('transmittal_receipts', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->unsignedBigInteger('recipient_id')->nullable()->unique();
            $table->string('receipt_no')->unique(); $table->date('receipt_date')->nullable(); $table->string('mode')->nullable();
            $table->string('from_name')->nullable(); $table->string('to_name')->nullable(); $table->string('office_name')->nullable();
            $table->string('delivery_type')->nullable(); $table->string('delivery_detail')->nullable(); $table->string('recipient_email')->nullable();
            $table->string('actions_summary')->nullable(); $table->string('prepared_by_name')->nullable(); $table->string('approved_by_name')->nullable();
            $table->string('approved_position')->nullable(); $table->string('document_custodian')->nullable(); $table->string('delivered_by')->nullable();
            $table->string('received_by')->nullable(); $table->timestamp('received_at')->nullable(); $table->unsignedBigInteger('generated_by')->nullable();
            $table->string('signed_name')->nullable(); $table->text('comments')->nullable(); $table->string('receipt_code')->nullable();
            $table->json('snapshot')->nullable(); $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable(); $table->timestamps();
        });
        Schema::create('proof_of_transmissions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->unsignedBigInteger('recipient_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable(); $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('channel')->nullable(); $table->string('status')->nullable(); $table->string('transaction_reference')->nullable();
            $table->timestamp('acknowledged_at')->nullable(); $table->timestamp('transmitted_at')->nullable();
            $table->json('provider_metadata')->nullable(); $table->timestamps();
        });
        Schema::create('external_acknowledgments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->unsignedBigInteger('recipient_id');
            $table->unsignedBigInteger('receipt_id')->nullable(); $table->string('event'); $table->json('details')->nullable();
            $table->timestamp('occurred_at');
        });
        Schema::create('transmittal_histories', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action'); $table->text('description')->nullable(); $table->json('metadata')->nullable(); $table->timestamp('created_at');
        });
        Schema::create('approval_authority_codes', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id')->unique(); $table->unsignedBigInteger('approved_by');
            $table->string('code')->unique(); $table->json('frozen_snapshot'); $table->char('snapshot_hash', 64); $table->timestamp('approved_at');
        });
    }

    private function user(string $role): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role).' Synthetic User', 'email' => $role.'-'.bin2hex(random_bytes(4)).'@example.invalid',
            'role' => $role, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }

    private function transmittal(string $version, ?int $owner, string $status): int
    {
        return DB::table('transmittals')->insertGetId([
            'workflow_version' => $version === 'v1' ? 'v1' : 'v2', 'prepared_by' => $owner, 'status' => $status,
            'workflow_status' => $status === 'pending_approval' ? 'Submitted' : 'Accepted',
            'approval_status' => $status === 'pending_approval' ? 'Pending' : 'Approved',
            'tracking_number' => 'SYN-'.strtoupper(bin2hex(random_bytes(4))), 'subject' => 'Synthetic transmittal',
            'direction' => 'send', 'transmittal_date' => now()->toDateString(), 'prepared_by_name' => 'Synthetic Preparer',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function recipient(int $transmittal, string $email, string $token): int
    {
        return DB::table('transmittal_recipients')->insertGetId([
            'transmittal_id' => $transmittal, 'name' => 'Synthetic Recipient', 'email' => $email,
            'organization' => 'Synthetic Organization', 'acknowledgment_token_hash' => TransmittalAcknowledgmentToken::hash($token),
            'acknowledgment_expires_at' => now()->addHour(), 'sent_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
