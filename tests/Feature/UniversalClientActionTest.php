<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Deal;
use App\Models\ClientActionRequest;
use App\Models\User;
use App\Services\ClientActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UniversalClientActionTest extends TestCase
{
    use RefreshDatabase;

    protected ClientActionService $service;
    protected Deal $testDeal;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ClientActionService::class);
        Storage::fake('public');

        $this->testUser = User::create([
            'name' => 'Staff Officer',
            'email' => 'staff@ordo.test',
            'password' => bcrypt('password123'),
        ]);

        $this->testDeal = Deal::create([
            'deal_code' => 'DEAL-2026-TEST',
            'company' => 'Acme Corporation',
            'client_name' => 'Acme Corp Contact',
            'email' => 'client@acme.com',
            'pipeline_stage' => 'Proposal',
            'deal_stage' => 'Proposal',
            'total_price' => 50000,
        ]);
    }

    /**
     * TEST 1 — Portal
     * Create Client Action: Approve Proposal V3
     * Confirm it appears in the authorized client's portal.
     * Complete the action.
     * Confirm the SAME Client Action becomes completed.
     * Confirm evidence and channel are recorded as Portal.
     */
    public function test_test1_portal_client_action_lifecycle()
    {
        $deal = $this->testDeal;

        // 1. Create Client Action Request
        $action = $this->service->createActionRequest([
            'deal_id' => $deal->id,
            'client_name' => 'Acme Corp Contact',
            'client_email' => 'client@acme.com',
            'module_source' => 'Proposal',
            'action_type' => 'Approve',
            'document_title' => 'Commercial Proposal',
            'document_version' => 'V3',
            'title' => 'Approve Proposal V3',
            'instructions' => 'Please review and approve Proposal V3.',
            'primary_channel' => 'Client Portal',
            'created_by_user_id' => $this->testUser->id,
        ]);

        $this->assertInstanceOf(ClientActionRequest::class, $action);
        $this->assertEquals('Approve', $action->action_type);
        $this->assertEquals('V3', $action->document_version);
        $this->assertNotEmpty($action->action_code);

        // 2. Confirm it appears in portal listing for client
        $portalResponse = $this->getJson(route('client-actions.portal', ['email' => 'client@acme.com']));
        $portalResponse->assertStatus(200);
        $portalActions = $portalResponse->json('actions');
        $this->assertNotEmpty($portalActions);
        $matching = collect($portalActions)->firstWhere('id', $action->id);
        $this->assertNotNull($matching, 'Action must appear in client portal');

        // 3. Complete the action via Portal
        $updatedAction = $this->service->recordPortalResponse(
            $action,
            'Approved',
            'Approved via Acme Client Portal',
            ['ip' => '127.0.0.1', 'client_email' => 'client@acme.com']
        );

        // 4. Confirm the SAME Client Action is updated and completed
        $this->assertEquals($action->id, $updatedAction->id, 'Must update the exact SAME Client Action Request');
        $this->assertEquals('Approved', $updatedAction->status);
        $this->assertEquals('Client Portal', $updatedAction->response_channel);
        $this->assertEquals('Approved', $updatedAction->response_decision);
        $this->assertNotNull($updatedAction->responded_at);
        $this->assertNotNull($updatedAction->completed_at);
        $this->assertEquals('Client Portal Response Log', $updatedAction->evidence_type);

        // Confirm audit trail
        $auditTrail = $updatedAction->audit_trail;
        $this->assertIsArray($auditTrail);
        $this->assertTrue(collect($auditTrail)->contains(function ($entry) {
            return ($entry['action'] ?? '') === 'Portal Response Submitted';
        }));
    }

    /**
     * TEST 2 — Secure Link
     * Create Client Action: Approve Proposal V3
     * Generate secure link.
     * Open without portal login.
     * Confirm only the authorized action is accessible.
     * Complete the action.
     * Confirm the SAME Client Action is updated.
     * Confirm channel = Secure Link.
     */
    public function test_test2_secure_no_login_link_lifecycle()
    {
        $deal = $this->testDeal;

        // 1. Create Client Action
        $action = $this->service->createActionRequest([
            'deal_id' => $deal->id,
            'client_name' => 'Jane Smith',
            'client_email' => 'jane.smith@client.com',
            'module_source' => 'Proposal',
            'action_type' => 'Approve',
            'document_title' => 'Master Proposal',
            'document_version' => 'V3',
            'title' => 'Approve Master Proposal V3',
            'primary_channel' => 'Secure Link',
            'requires_otp' => false,
            'created_by_user_id' => $this->testUser->id,
        ]);

        $this->assertNotNull($action->secure_token);

        // 2. Open without portal login via public secure URL
        $publicUrl = route('client-action.public.show', ['token' => $action->secure_token]);
        $response = $this->get($publicUrl);
        $response->assertStatus(200);
        $response->assertSee('Master Proposal');
        $response->assertSee('V3');

        // Security check: Confirm internal deal notes are not leaked
        $response->assertDontSee('internal_admin_secret_notes');

        // 3. Complete action via Secure Link public submission
        $submitResponse = $this->post(route('client-action.public.respond', ['token' => $action->secure_token]), [
            'decision' => 'Approved',
            'notes' => 'Approved from secure email link without login',
            'signatory_name' => 'Jane Smith',
        ]);

        $submitResponse->assertStatus(200);
        $submitResponse->assertJson(['success' => true]);

        // 4. Confirm the SAME Client Action is updated
        $freshAction = $action->fresh();
        $this->assertEquals($action->id, $freshAction->id);
        $this->assertEquals('Approved', $freshAction->status);
        $this->assertEquals('Secure Link', $freshAction->response_channel);
        $this->assertEquals('Approved', $freshAction->response_decision);
        $this->assertNotNull($freshAction->responded_at);
        $this->assertEquals('Jane Smith', $freshAction->response_data['signatory_name'] ?? null);
    }

    /**
     * TEST 3 — Email
     * Create Client Action.
     * Receive/capture client email response.
     * Link the email evidence.
     * Record the decision.
     * Confirm the audit trail says the decision was recorded from email evidence.
     */
    public function test_test3_email_evidence_capture()
    {
        $deal = $this->testDeal;

        // 1. Create Client Action
        $action = $this->service->createActionRequest([
            'deal_id' => $deal->id,
            'client_name' => 'John Doe',
            'client_email' => 'john.doe@enterprise.com',
            'module_source' => 'Proposal',
            'action_type' => 'Approve',
            'document_title' => 'Enterprise Proposal',
            'document_version' => 'V3',
            'title' => 'Approve Proposal V3',
            'created_by_user_id' => $this->testUser->id,
        ]);

        // 2. Staff captures email evidence and records decision
        $updatedAction = $this->service->recordEmailResponse(
            $action,
            'Approved',
            'Client email from john.doe@enterprise.com: "Hi Team, we reviewed Proposal V3 and hereby formally approve the terms and pricing. Thanks, John Doe."',
            null,
            'email_confirmation_msg.eml',
            $this->testUser->id,
            $this->testUser->name
        );

        // 3. Confirm SAME record updated
        $this->assertEquals($action->id, $updatedAction->id);
        $this->assertEquals('Approved', $updatedAction->status);
        $this->assertEquals('Email', $updatedAction->response_channel);
        $this->assertEquals('Client Email Evidence', $updatedAction->evidence_type);

        // 4. Confirm audit trail explicitly records that decision was recorded from email evidence
        $auditTrail = $updatedAction->audit_trail;
        $emailAudit = collect($auditTrail)->firstWhere('action', 'Recorded from Email Evidence');
        $this->assertNotNull($emailAudit, 'Audit trail must contain Recorded from Email Evidence entry');
        $this->assertStringContainsString('RECORDED FROM EMAIL EVIDENCE', $emailAudit['description'] ?? '');
        $this->assertEquals('Email', $emailAudit['metadata']['channel'] ?? '');
    }

    /**
     * TEST 4 — Manual Signature
     * Generate controlled document.
     * Upload signed physical copy.
     * Record signatory, document date, uploader, recorded-at timestamp, and verification state.
     * Confirm channel = Manual / Wet Signature.
     * Confirm it is explicitly NOT labeled as an ORDO-native digital signature.
     */
    public function test_test4_manual_wet_signature_evidence()
    {
        $deal = $this->testDeal;

        // 1. Create Client Action
        $action = $this->service->createActionRequest([
            'deal_id' => $deal->id,
            'client_name' => 'Robert Johnson',
            'client_email' => 'robert@corp.com',
            'module_source' => 'CASA',
            'action_type' => 'Sign',
            'document_title' => 'Client Account Services Agreement (CASA)',
            'document_version' => 'V1',
            'title' => 'Sign CASA Agreement',
            'created_by_user_id' => $this->testUser->id,
        ]);

        // Verify controlled document route exists and can be rendered
        $docResponse = $this->get(route('client-actions.controlled-document', ['id' => $action->id]));
        $docResponse->assertStatus(200);
        $docResponse->assertSee('CONTROLLED');

        // 2. Upload signed physical wet copy
        $updatedAction = $this->service->recordManualSignature(
            $action,
            'Robert Johnson',
            '2026-09-24',
            '/storage/client-actions/evidence/signed_casa_wet.pdf',
            'signed_casa_wet.pdf',
            'Physical wet ink signature verified on printed controlled copy.',
            $this->testUser->id,
            $this->testUser->name,
            'Verified (Physical Copy)'
        );

        // 3. Confirm SAME record updated
        $this->assertEquals($action->id, $updatedAction->id);
        $this->assertEquals('Signed', $updatedAction->status);
        $this->assertEquals('Manual / Wet Signature', $updatedAction->response_channel);
        $this->assertEquals('Physical / Wet Signed Document', $updatedAction->evidence_type);
        $this->assertEquals('Robert Johnson', $updatedAction->signatory_name);
        $this->assertEquals('2026-09-24', $updatedAction->document_signed_date?->format('Y-m-d'));

        // 4. Confirm explicitly NOT labeled as digital signature
        $this->assertFalse($updatedAction->evidence_metadata['is_native_digital_signature'] ?? true, 'Manual / Wet Signature must NOT be labeled as an ORDO digital signature');
        $this->assertEquals('Physical / Wet Signature (Manual Evidence)', $updatedAction->evidence_metadata['method'] ?? null);

        // Confirm audit trail
        $auditTrail = $updatedAction->audit_trail;
        $audit = collect($auditTrail)->firstWhere('action', 'Physical / Wet Signature Recorded');
        $this->assertNotNull($audit);
        $this->assertStringContainsString('NOT an ORDO native digital signature', $audit['description'] ?? '');
    }

    /**
     * TEST 5 — External Signing
     * Upload returned signed evidence from outside ORDO.
     * Confirm status = Completed / Externally Completed.
     * Confirm ORDO does NOT claim that the document was digitally signed inside ORDO.
     */
    public function test_test5_external_signing_not_claimed_as_ordo_signature()
    {
        $deal = $this->testDeal;

        // 1. Create Client Action
        $action = $this->service->createActionRequest([
            'deal_id' => $deal->id,
            'client_name' => 'Alice Wong',
            'client_email' => 'alice@partner.com',
            'module_source' => 'START',
            'action_type' => 'Acknowledge',
            'document_title' => 'Service Memo START Activation',
            'document_version' => 'V1',
            'title' => 'Acknowledge Service Memo',
            'created_by_user_id' => $this->testUser->id,
        ]);

        // 2. Upload external signing evidence (e.g. DocuSign/AdobeSign/External notary)
        $updatedAction = $this->service->recordExternalSigning(
            $action,
            'External DocuSign Envelope #98124',
            'Alice Wong',
            '2026-09-24',
            '/storage/client-actions/evidence/docusign_certificate.pdf',
            'docusign_certificate.pdf',
            'Executed via client enterprise DocuSign account.',
            $this->testUser->id,
            $this->testUser->name
        );

        // 3. Confirm SAME record updated
        $this->assertEquals($action->id, $updatedAction->id);
        $this->assertEquals('Completed', $updatedAction->status);
        $this->assertEquals('External Signing', $updatedAction->response_channel);
        $this->assertEquals('External Signing Evidence', $updatedAction->evidence_type);

        // 4. Confirm ORDO does NOT claim digital signature inside ORDO
        $this->assertFalse($updatedAction->evidence_metadata['is_native_digital_signature'] ?? true, 'External signing must NOT be claimed as ORDO-native digital signature');
        $this->assertStringContainsString('Executed outside ORDO', $updatedAction->evidence_notes);

        // Confirm audit trail explicitly records external execution
        $auditTrail = $updatedAction->audit_trail;
        $audit = collect($auditTrail)->firstWhere('action', 'Externally Completed / Manual Evidence');
        $this->assertNotNull($audit);
        $this->assertStringContainsString('outside ORDO', $audit['description'] ?? '');
    }

    /**
     * TEST 6 — Version Control
     * Create Proposal V3 Client Action.
     * Create Proposal V4.
     * Confirm the V3 Client Action remains linked to V3.
     * Do not silently change its document/version reference.
     */
    public function test_test6_document_version_integrity()
    {
        $deal = $this->testDeal;

        // 1. Create Proposal V3 Action Request
        $actionV3 = $this->service->createActionRequest([
            'deal_id' => $deal->id,
            'client_name' => 'David Miller',
            'client_email' => 'david@client.com',
            'module_source' => 'Proposal',
            'action_type' => 'Approve',
            'document_title' => 'Project Scope Proposal',
            'document_version' => 'V3',
            'title' => 'Approve Project Scope Proposal V3',
            'created_by_user_id' => $this->testUser->id,
        ]);

        $this->assertEquals('V3', $actionV3->document_version);
        $this->assertEquals('Approve Project Scope Proposal V3', $actionV3->title);

        // 2. Simulate creating a new Proposal V4 in the system
        $actionV4 = $this->service->createActionRequest([
            'deal_id' => $deal->id,
            'client_name' => 'David Miller',
            'client_email' => 'david@client.com',
            'module_source' => 'Proposal',
            'action_type' => 'Approve',
            'document_title' => 'Project Scope Proposal',
            'document_version' => 'V4',
            'title' => 'Approve Project Scope Proposal V4',
            'created_by_user_id' => $this->testUser->id,
        ]);

        // 3. Confirm the V3 Client Action REMAINS explicitly linked to V3
        $freshV3 = $actionV3->fresh();
        $this->assertEquals('V3', $freshV3->document_version, 'Action V3 must remain linked to V3');
        $this->assertEquals('Approve Project Scope Proposal V3', $freshV3->title);
        $this->assertNotEquals($actionV3->id, $actionV4->id, 'Separate version request has distinct ID');
        $this->assertEquals('V4', $actionV4->document_version);

        // Previous action history remains intact
        $dealActions = ClientActionRequest::where('deal_id', $deal->id)->get();
        $this->assertCount(2, $dealActions->whereIn('document_version', ['V3', 'V4']));
    }
}
