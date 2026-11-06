<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\DealProposal;
use App\Models\DealProposalApprovalEvent;
use App\Models\DealProposalDelivery;
use App\Models\DealProposalVersion;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\DealProposalPdfArtifactService;
use App\Services\DealProposalIssuanceService;
use App\Services\DealProposalPdfRenderer;
use App\Services\DealProposalSnapshotService;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\QueryException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;
use Tests\DealsMysqlTestCase;

class DealProposalVersioningTest extends DealsMysqlTestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertDealsMysqlIsolationIdentity();
    }

    private function assertCacheControlDirectives(\Illuminate\Testing\TestResponse $response, array $expected): void
    {
        $actual = array_map('trim', explode(',', (string) $response->headers->get('Cache-Control')));
        sort($actual);
        sort($expected);
        $this->assertSame($expected, $actual);
    }

    private function canonicalizeJsonObjectKeys(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalizeJsonObjectKeys($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    private function createProposal(): DealProposal
    {
        $contact = Contact::query()->create([
            'first_name' => 'Snapshot',
            'last_name' => 'Client',
            'email' => 'snapshot@example.invalid',
        ]);
        $deal = Deal::query()->create([
            'contact_id' => $contact->id,
            'deal_name' => 'Snapshot test deal',
            'stage' => 'Proposal',
        ]);

        return $deal->proposal()->create(['status' => 'saved']);
    }

    private function createFinanceActor(array $attributes = [], array $permissions = ['finance_approver' => true]): User
    {
        $user = User::factory()->create(array_merge(['role' => 'Employee'], $attributes));
        UserPermission::query()->create(array_merge(['user_id' => $user->id], $permissions));

        return $user;
    }

    public function test_assigned_eligible_finance_user_can_approve_active_deal_quotation(): void
    {
        $proposal = $this->createProposal();
        $finance = $this->createFinanceActor();
        $proposal->deal()->update(['assigned_finance_user_id' => $finance->id]);
        $this->actingAs($finance);

        $this->post(route('deals.quotation.approve', $proposal->deal_id))->assertRedirect();

        $this->assertSame('approved', $proposal->fresh()->quotation_status);
        $this->assertNotNull($proposal->fresh()->quotation_approved_at);
    }

    public function test_wrong_null_and_cross_deal_finance_assignments_are_denied_without_writes(): void
    {
        $proposal = $this->createProposal();
        $assigned = $this->createFinanceActor();
        $wrong = $this->createFinanceActor();
        $proposal->deal()->update(['assigned_finance_user_id' => $assigned->id]);

        $this->actingAs($wrong)
            ->post(route('deals.quotation.approve', $proposal->deal_id))
            ->assertForbidden();
        $this->assertNull($proposal->fresh()->quotation_approved_at);

        $proposal->deal()->update(['assigned_finance_user_id' => null]);
        $this->actingAs($assigned)
            ->post(route('deals.quotation.approve', $proposal->deal_id))
            ->assertForbidden();
        $this->assertNull($proposal->fresh()->quotation_approved_at);

        $proposal->deal()->update(['assigned_finance_user_id' => $assigned->id]);
        $otherProposal = $this->createProposal();
        $this->actingAs($assigned)
            ->post(route('deals.quotation.approve', $otherProposal->deal_id))
            ->assertForbidden();
        $this->assertNull($otherProposal->fresh()->quotation_approved_at);
    }

    public function test_inactive_disabled_archived_and_client_accounts_cannot_act_as_assigned_finance(): void
    {
        Mail::fake();
        Storage::fake('public');
        $proposal = $this->createProposal();
        foreach ([
            ['is_active' => false],
            ['disabled_at' => now()],
            ['archived_at' => now()],
            ['role' => 'Client'],
        ] as $attributes) {
            $finance = $this->createFinanceActor($attributes);
            $proposal->deal()->update(['assigned_finance_user_id' => $finance->id]);
            $this->actingAs($finance)
                ->post(route('deals.quotation.approve', $proposal->deal_id))
                ->assertForbidden();
            $this->assertNull($proposal->fresh()->quotation_approved_at);
            $this->assertNull($proposal->fresh()->payment_confirmed_at);
        }
        Mail::assertNothingOutgoing();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_generic_finance_permissions_do_not_authorize_deal_finance_mutations(): void
    {
        $proposal = $this->createProposal();
        $finance = $this->createFinanceActor([], ['approve_finance' => true, 'access_finance' => true]);
        $proposal->deal()->update(['assigned_finance_user_id' => $finance->id]);
        $this->actingAs($finance)
            ->post(route('deals.quotation.approve', $proposal->deal_id))
            ->assertForbidden();
        $this->assertNull($proposal->fresh()->quotation_approved_at);
    }

    public function test_finance_assignment_does_not_authorize_ordinary_proposal_editing(): void
    {
        $proposal = $this->createProposal();
        $finance = $this->createFinanceActor();
        $proposal->deal()->update(['assigned_finance_user_id' => $finance->id]);
        $this->actingAs($finance)
            ->post(route('deals.proposal.preview', $proposal->deal_id))
            ->assertForbidden();
        $this->assertSame('saved', $proposal->fresh()->status);
    }

    public function test_unauthorized_staff_proposal_get_does_not_create_a_proposal(): void
    {
        $contact = Contact::query()->create(['first_name' => 'No', 'last_name' => 'Proposal', 'email' => 'no-proposal@example.invalid']);
        $deal = Deal::query()->create(['contact_id' => $contact->id, 'deal_name' => 'No proposal', 'stage' => 'Proposal']);
        $staff = User::factory()->create(['role' => 'Employee']);
        $this->actingAs($staff)->get(route('deals.proposal.show', $deal))->assertForbidden();
        $this->actingAs($staff)->put(route('deals.proposal.update', $deal), [])->assertForbidden();
        $this->assertSame(0, DealProposal::query()->where('deal_id', $deal->id)->count());
    }

    public function test_client_token_cannot_authorize_staff_only_proposal_routes(): void
    {
        $proposal = $this->createProposal();
        $proposal->update(['client_access_token' => 'client-route-token', 'client_access_expires_at' => now()->addDay()]);
        $client = User::factory()->create(['role' => 'Client']);
        $this->actingAs($client)
            ->get(route('deals.proposal.show', $proposal->deal_id))
            ->assertForbidden();
        $this->assertSame('saved', $proposal->fresh()->status);
    }

    public function test_internal_proposal_review_mutations_are_admin_only(): void
    {
        $proposal = $this->createProposal();
        $staff = User::factory()->create(['role' => 'Employee']);
        $this->actingAs($staff)->post(route('deals.proposal.admin-approve', $proposal->deal_id))->assertForbidden();
        $this->actingAs($staff)->post(route('deals.proposal.admin-reject', $proposal->deal_id), ['reason' => 'Not approved'])->assertForbidden();
        $this->assertSame('saved', $proposal->fresh()->status);
    }

    public function test_admin_and_superadmin_can_complete_internal_proposal_reviews(): void
    {
        $approvedProposal = $this->createProposal();
        $rejectedProposal = $this->createProposal();

        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)
            ->post(route('deals.proposal.admin-approve', $approvedProposal->deal_id))
            ->assertRedirect();
        $this->assertSame('approved', $approvedProposal->fresh()->status);

        $superAdmin = User::factory()->create(['role' => 'SuperAdmin']);
        $this->actingAs($superAdmin)
            ->post(route('deals.proposal.admin-reject', $rejectedProposal->deal_id))
            ->assertRedirect();
        $this->assertSame('rejected', $rejectedProposal->fresh()->status);
    }

    private function versionPayload(DealProposal $proposal, int $versionNumber = 1): array
    {
        return [
            'deal_proposal_id' => $proposal->id,
            'version_number' => $versionNumber,
            'snapshot_schema_version' => DealProposalSnapshotService::SNAPSHOT_SCHEMA_VERSION,
            'resolved_proposal_data' => ['client_name' => 'Snapshot Client'],
            'commercial_values' => ['total' => '120.00'],
            'source_metadata' => ['captured_from' => 'resolved_issue_payload'],
            'document_identifiers' => ['reference_id' => 'SYNTHETIC-1'],
            'rendered_html' => '<html><body>Synthetic issued proposal</body></html>',
            'artifact_private_path' => 'deal-proposal-versions/'.$proposal->deal_id.'/synthetic-v1.pdf',
            'artifact_sha256' => str_repeat('a', 64),
            'issued_at' => now(),
            'issuance_request_key' => 'request-key-'.$versionNumber,
            'issuance_request_fingerprint' => DealProposalVersion::fingerprintIssuanceRequest(
                (int) $proposal->id,
                'client@example.invalid',
                ['reference_id' => 'SYNTHETIC-1', 'version' => $versionNumber],
            ),
        ];
    }

    private function validPdfBytes(): string
    {
        $bytes = "%PDF-1.4\n";
        $catalogOffset = strlen($bytes);
        $bytes .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pagesOffset = strlen($bytes);
        $bytes .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";
        $xrefOffset = strlen($bytes);
        $bytes .= "xref\n0 3\n0000000000 65535 f \n";
        $bytes .= sprintf("%010d 00000 n \n", $catalogOffset);
        $bytes .= sprintf("%010d 00000 n \n", $pagesOffset);
        $bytes .= "trailer\n<< /Size 3 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $bytes;
    }

    private function validXrefStreamPdfBytes(): string
    {
        $bytes = "%PDF-1.5\n";
        $xrefOffset = strlen($bytes);
        $bytes .= "1 0 obj\n<< /Size 2 /Root 1 0 R /W [1 4 2] /Index [0 2] /Length 14 /Padding <";
        $bytes .= str_repeat('A', 600);
        $bytes .= "> /Type /XRef >>\nstream\n".str_repeat("\0", 14)."\nendstream\nendobj\n";
        $bytes .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $bytes;
    }

    private function issuanceInputs(DealProposal $proposal, string $recipient = 'CLIENT@example.invalid'): array
    {
        $issueDate = new DateTimeImmutable('2026-10-29 09:00:00', new DateTimeZone('Asia/Manila'));
        return [
            'actor' => User::factory()->create(['role' => 'Admin']),
            'deal_id' => (int) $proposal->deal_id,
            'proposal_id' => (int) $proposal->id,
            'key' => '11111111-1111-4111-8111-111111111111',
            'recipient' => $recipient,
            'document' => [
                'date' => $issueDate->format('F d, Y'),
                'year' => $issueDate->format('Y'),
                'client_name' => 'Resolved Synthetic Client',
                'reference_id' => 'SNAPSHOT-REFERENCE-1',
            ],
            'html' => '<main>Resolved synthetic proposal content.</main>',
            'commercial' => ['subtotal' => '120.00', 'total' => '120.00'],
            'source' => ['captured_from' => 'authorized_resolved_payload', 'revision' => 'synthetic-1'],
            'identifiers' => ['reference_id' => 'SNAPSHOT-REFERENCE-1'],
            'issue_date' => $issueDate,
        ];
    }

    private function preparePdfRenderer(string $pdfBytes): void
    {
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
        $pdf->shouldReceive('output')->once()->andReturn($pdfBytes);
        Pdf::shouldReceive('loadView')->once()->andReturn($pdf);
    }

    private function issue(DealProposal $proposal, array $inputs): array
    {
        return app(DealProposalIssuanceService::class)->issue(
            $inputs['actor'], $inputs['deal_id'], $inputs['proposal_id'], $inputs['key'],
            $inputs['recipient'], $inputs['document'], $inputs['html'], $inputs['commercial'],
            $inputs['source'], $inputs['identifiers'], $inputs['issue_date'],
        );
    }

    private function createCurrentIssuedVersion(
        DealProposal $proposal,
        string $token,
        string $pdfBytes,
        ?\DateTimeInterface $expiresAt = null,
    ): DealProposalVersion
    {
        $expiry = $expiresAt ?? now()->addDay();
        $proposal->forceFill([
            'client_access_token' => $token,
            'client_access_expires_at' => $expiry,
        ])->save();

        $payload = $this->versionPayload($proposal);
        $payload['artifact_private_path'] = 'deal-proposal-versions/'.$proposal->id.'/issued-fixture.pdf';
        $payload['artifact_sha256'] = hash('sha256', $pdfBytes);
        $payload['rendered_html'] = '<article>Issued snapshot marker: immutable content.</article>';
        $payload['issued_token_sha256'] = hash('sha256', $token);
        $payload['issued_to_email'] = 'client@example.invalid';
        $payload['client_access_expires_at'] = $expiry;

        Storage::disk('local')->put($payload['artifact_private_path'], $pdfBytes);

        return DealProposalVersion::query()->create($payload);
    }

    private function signInAsProposalSender(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'Admin',
            'must_change_password' => false,
        ]));
    }

    private function postProposalSend(DealProposal $proposal, string $key, string $recipient = 'client@example.invalid')
    {
        return $this->post(route('deals.proposal.send', ['deal' => $proposal->deal_id]), [
            'recipient_email' => $recipient,
            'issuance_request_key' => $key,
        ]);
    }

    private function prepareControllerIssuanceRenderer(string $pdfBytes, ?array &$captured = null): void
    {
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
        $pdf->shouldReceive('output')->once()->andReturn($pdfBytes);
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('deals.proposal.pdf', \Mockery::on(function (array $viewData) use (&$captured): bool {
                $captured = $viewData;
                return true;
            }))
            ->andReturn($pdf);
    }

    public function test_snapshot_builder_keeps_resolved_content_and_decimal_strings(): void
    {
        $payload = app(DealProposalSnapshotService::class)->build(
            ['client_name' => 'Snapshot Client', 'service_rows' => [['name' => 'Fixed service']]],
            ['service_total' => '0012.5', 'tax' => '0', 'total' => '12.50'],
            ['captured_from' => 'already_resolved_payload', 'catalog_revision' => 'synthetic-revision'],
            ['reference_id' => 'SYNTHETIC-1'],
            '<html><body>Issued HTML</body></html>',
            'deal-proposal-versions/1/11111111-1111-4111-8111-111111111111.pdf',
            str_repeat('B', 64),
        );

        $this->assertSame(1, $payload['snapshot_schema_version']);
        $this->assertSame('12.50', $payload['commercial_values']['service_total']);
        $this->assertSame('0.00', $payload['commercial_values']['tax']);
        $this->assertSame('deal-proposal-versions/1/11111111-1111-4111-8111-111111111111.pdf', $payload['artifact_private_path']);
        $this->assertSame(str_repeat('b', 64), $payload['artifact_sha256']);
        $this->assertSame('<html><body>Issued HTML</body></html>', $payload['rendered_html']);
        $this->assertSame('already_resolved_payload', $payload['source_metadata']['captured_from']);
    }

    public function test_snapshot_builder_rejects_float_money_and_non_private_artifact_paths(): void
    {
        $service = app(DealProposalSnapshotService::class);

        try {
            $service->build([], ['total' => 12.5], [], [], '<html>issued</html>', 'deal-proposal-versions/1/v1.pdf', str_repeat('a', 64));
            $this->fail('Float commercial amounts must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('decimal strings', $exception->getMessage());
        }

        try {
            $service->build(['client_access_token' => 'synthetic-secret'], [], [], [], '<html>issued</html>', 'deal-proposal-versions/1/v1.pdf', str_repeat('a', 64));
            $this->fail('Raw access tokens must not be copied into snapshots.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Credential fields', $exception->getMessage());
        }

        foreach ([
            'deal-proposal-versions/1/../v1.pdf',
            '/deal-proposal-versions/1/v1.pdf',
            'C:/deal-proposal-versions/1/v1.pdf',
            'public/proposals/v1.pdf',
            'deal-proposal-versions/1/v1.txt',
        ] as $path) {
            try {
                $service->build([], ['total' => '12.50'], [], [], '<html>issued</html>', $path, str_repeat('a', 64));
                $this->fail("Invalid artifact path was accepted: {$path}");
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('relative deal-proposal-versions', $exception->getMessage());
            }
        }
    }

    public function test_private_pdf_artifacts_are_unique_and_verified_after_write(): void
    {
        Storage::fake('local');
        $service = app(DealProposalPdfArtifactService::class);
        $pdfBytes = $this->validPdfBytes();

        $first = $service->store(17, $pdfBytes);
        $second = $service->store(17, $pdfBytes);

        $this->assertNotSame($first['path'], $second['path']);
        $this->assertStringStartsWith('deal-proposal-versions/17/', $first['path']);
        $this->assertSame(strlen($pdfBytes), $first['byte_size']);
        $this->assertSame(hash('sha256', $pdfBytes), $first['sha256']);
        Storage::disk('local')->assertExists($first['path']);
        Storage::disk('local')->assertExists($second['path']);
        $this->assertSame(strlen($pdfBytes), Storage::disk('local')->size($first['path']));
        $this->assertSame($first['sha256'], hash('sha256', Storage::disk('local')->get($first['path'])));
    }

    public function test_private_pdf_artifact_storage_fails_closed_and_cleans_partial_files(): void
    {
        Storage::fake('local');
        $service = app(DealProposalPdfArtifactService::class);
        $pdfBytes = $this->validPdfBytes();

        try {
            $service->store(17, 'truncated pdf');
            $this->fail('Invalid or incomplete PDF bytes must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('invalid or incomplete', $exception->getMessage());
        }

        try {
            $service->store(17, "%PDF-1.4\nstartxref\n99999\n%%EOF\n");
            $this->fail('PDF data with an invalid cross-reference offset must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('cross-reference offset', $exception->getMessage());
        }

        Storage::disk('local')->put('deal-proposal-versions/18', 'blocks-directory');
        $this->expectException(RuntimeException::class);
        $service->store(18, $pdfBytes);
    }

    public function test_pdf_renderer_returns_bytes_from_resolved_content_without_public_disk_writes(): void
    {
        Storage::fake('public');
        $issueDate = new DateTimeImmutable('2026-10-29 09:00:00', new DateTimeZone('Asia/Manila'));
        $resolvedData = [
            'date' => $issueDate->format('F d, Y'),
            'year' => $issueDate->format('Y'),
            'client_name' => 'Resolved Synthetic Client',
            'reference_id' => 'SNAPSHOT-REFERENCE-1',
        ];
        $resolvedHtml = '<main>Resolved synthetic proposal content.</main>';
        $expectedBytes = $this->validPdfBytes();

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
        $pdf->shouldReceive('output')->once()->andReturn($expectedBytes);
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('deals.proposal.pdf', \Mockery::on(function (array $viewData) use ($resolvedData, $resolvedHtml): bool {
                $this->assertSame($resolvedData, $viewData['documentData']);
                $this->assertSame($resolvedHtml, $viewData['proposalHtml']);

                return true;
            }))
            ->andReturn($pdf);

        $bytes = app(DealProposalPdfRenderer::class)->render($resolvedData, $resolvedHtml, $issueDate);

        $this->assertSame($expectedBytes, $bytes);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_pdf_renderer_requires_resolved_date_to_match_explicit_issue_date(): void
    {
        $this->expectException(RuntimeException::class);
        app(DealProposalPdfRenderer::class)->render(
            ['date' => 'October 28, 2026', 'year' => '2026'],
            '<main>Resolved proposal content.</main>',
            new DateTimeImmutable('2026-10-29 09:00:00', new DateTimeZone('Asia/Manila')),
        );
    }

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function test_pdf_validator_accepts_xref_tables_and_long_xref_stream_dictionaries(): void
    {
        DealProposalPdfArtifactService::assertValidPdfBytes($this->validPdfBytes());
        DealProposalPdfArtifactService::assertValidPdfBytes($this->validXrefStreamPdfBytes());
    }

    public function test_pdf_validator_rejects_truncated_and_malformed_cross_reference_data(): void
    {
        foreach ([
            "%PDF-1.4\ntruncated without eof",
            "%PDF-1.4\nstartxref\n8\n%%EOF\n",
            "%PDF-1.5\n1 0 obj\n<< /Type /Catalog >>\nendobj\nstartxref\n9\n%%EOF\n",
        ] as $bytes) {
            try {
                DealProposalPdfArtifactService::assertValidPdfBytes($bytes);
                $this->fail('Malformed or truncated PDF bytes were accepted.');
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_cleanup_only_removes_this_attempts_unreferenced_artifact(): void
    {
        Storage::fake('local');
        $service = app(DealProposalPdfArtifactService::class);
        $pdfBytes = $this->validPdfBytes();
        $issued = $service->store(19, $pdfBytes);
        $otherPath = 'deal-proposal-versions/19/already-issued.pdf';
        Storage::disk('local')->put($otherPath, $pdfBytes);

        $this->assertFalse($service->cleanupUnreferenced($issued, fn (): bool => true));
        Storage::disk('local')->assertExists($issued['path']);
        Storage::disk('local')->assertExists($otherPath);
        $this->assertFalse($service->cleanupUnreferenced($issued, fn () => null));
        Storage::disk('local')->assertExists($issued['path']);
        $this->assertFalse($service->cleanupUnreferenced([
            'path' => $otherPath,
            'sha256' => hash('sha256', $pdfBytes),
        ], fn (): bool => false));
        Storage::disk('local')->assertExists($otherPath);

        $this->assertTrue($service->cleanupUnreferenced($issued, fn (): bool => false));
        Storage::disk('local')->assertMissing($issued['path']);
        Storage::disk('local')->assertExists($otherPath);
    }

    public function test_version_number_is_unique_per_proposal(): void
    {
        $proposal = $this->createProposal();
        DealProposalVersion::query()->create($this->versionPayload($proposal));

        $this->expectException(QueryException::class);
        $duplicateNumber = $this->versionPayload($proposal);
        $duplicateNumber['artifact_private_path'] = 'deal-proposal-versions/'.$proposal->deal_id.'/different-artifact.pdf';
        $duplicateNumber['issuance_request_key'] = 'different-request-key';
        $duplicateNumber['issuance_request_fingerprint'] = str_repeat('c', 64);
        DealProposalVersion::query()->create($duplicateNumber);
    }

    public function test_issuance_request_keys_are_unique_per_proposal(): void
    {
        $proposal = $this->createProposal();
        DealProposalVersion::query()->create($this->versionPayload($proposal, 1));
        $duplicateKey = $this->versionPayload($proposal, 2);
        $duplicateKey['issuance_request_key'] = 'request-key-1';
        $duplicateKey['artifact_private_path'] = 'deal-proposal-versions/'.$proposal->deal_id.'/different-artifact.pdf';

        $this->expectException(QueryException::class);
        DealProposalVersion::query()->create($duplicateKey);
    }

    public function test_issuance_retry_returns_same_version_and_rejects_conflicting_fingerprint(): void
    {
        $proposal = $this->createProposal();
        $payload = $this->versionPayload($proposal);
        $version = DealProposalVersion::query()->create($payload);

        $sameFingerprint = DealProposalVersion::fingerprintIssuanceRequest(
            (int) $proposal->id,
            ' CLIENT@example.invalid ',
            ['version' => 1, 'reference_id' => 'SYNTHETIC-1'],
        );
        $retry = DealProposalVersion::resolveIssuanceRetry(
            (int) $proposal->id,
            (string) $payload['issuance_request_key'],
            $sameFingerprint,
        );
        $this->assertSame($version->id, $retry?->id);

        $differentRecipientFingerprint = DealProposalVersion::fingerprintIssuanceRequest(
            (int) $proposal->id,
            'different@example.invalid',
            ['reference_id' => 'SYNTHETIC-1', 'version' => 1],
        );

        $this->expectException(\DomainException::class);
        DealProposalVersion::resolveIssuanceRetry(
            (int) $proposal->id,
            (string) $payload['issuance_request_key'],
            $differentRecipientFingerprint,
        );
    }

    public function test_issuance_request_key_cannot_be_reused_for_another_proposal(): void
    {
        $proposal = $this->createProposal();
        $payload = $this->versionPayload($proposal);
        DealProposalVersion::query()->create($payload);
        $otherProposal = $this->createProposal();

        $this->expectException(\DomainException::class);
        DealProposalVersion::resolveIssuanceRetry(
            (int) $otherProposal->id,
            (string) $payload['issuance_request_key'],
            (string) $payload['issuance_request_fingerprint'],
        );
    }

    public function test_approval_event_idempotency_key_is_unique_without_legacy_backfill(): void
    {
        $proposal = $this->createProposal();
        $version = DealProposalVersion::query()->create($this->versionPayload($proposal));
        $event = [
            'deal_proposal_version_id' => $version->id,
            'event_type' => 'client_approved',
            'idempotency_key' => 'client-approval-version-'.$version->id,
            'occurred_at' => now(),
            'actor_type' => 'client',
            'actor_name' => 'Snapshot Client',
        ];
        DealProposalApprovalEvent::query()->create($event);

        $this->expectException(QueryException::class);
        DealProposalApprovalEvent::query()->create($event);
    }

    public function test_issued_versions_and_approval_events_reject_updates_and_deletes(): void
    {
        $proposal = $this->createProposal();
        $version = DealProposalVersion::query()->create($this->versionPayload($proposal));
        $event = DealProposalApprovalEvent::query()->create([
            'deal_proposal_version_id' => $version->id,
            'event_type' => 'client_approved',
            'occurred_at' => now(),
            'actor_type' => 'client',
            'actor_name' => 'Snapshot Client',
            'note' => 'Approved as issued.',
        ]);

        try {
            $version->version_number = 2;
            $version->save();
            $this->fail('Issued version updates must be rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        try {
            $version->delete();
            $this->fail('Issued version deletion must be rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('cannot be deleted', $exception->getMessage());
        }

        try {
            $event->note = 'Changed approval';
            $event->save();
            $this->fail('Approval event updates must be rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        $event->delete();
    }

    public function test_legacy_proposal_is_not_given_a_fabricated_version_or_approval_event(): void
    {
        $proposal = $this->createProposal();
        $proposal->forceFill([
            'status' => 'approved',
            'client_approved_at' => now(),
            'client_approved_by_name' => 'Legacy Client',
        ])->save();

        $this->assertSame(0, DealProposalVersion::query()->where('deal_proposal_id', $proposal->id)->count());
        $this->assertSame(0, DealProposalApprovalEvent::query()->count());
        $this->assertSame('Legacy Client', $proposal->fresh()->client_approved_by_name);
    }

    public function test_first_issuance_persists_version_delivery_private_artifact_and_bound_token(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $inputs = $this->issuanceInputs($proposal);
        $this->preparePdfRenderer($this->validPdfBytes());
        $result = $this->issue($proposal, $inputs);

        $this->assertSame(1, $result['version']->version_number);
        $this->assertFalse($result['replayed']);
        $this->assertTrue($result['is_current']);
        $this->assertSame(64, strlen($result['access_token']));
        $this->assertSame(hash('sha256', $result['access_token']), $result['version']->issued_token_sha256);
        $this->assertSame('client@example.invalid', $result['delivery']->recipient_email);
        $this->assertSame(DealProposalDelivery::STATUS_PENDING, $result['delivery']->status);
        $this->assertSame($result['access_token'], $proposal->fresh()->client_access_token);
        $this->assertSame(1, DealProposalVersion::query()->where('deal_proposal_id', $proposal->id)->count());
        $this->assertSame(1, DealProposalDelivery::query()->count());
        Storage::disk('local')->assertExists($result['version']->artifact_private_path);
        $this->assertSame(hash('sha256', $this->validPdfBytes()), $result['version']->artifact_sha256);
    }

    public function test_identical_issuance_retry_returns_original_version_delivery_and_token(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $inputs = $this->issuanceInputs($proposal);
        $this->preparePdfRenderer($this->validPdfBytes());
        $first = $this->issue($proposal, $inputs);
        $retry = $this->issue($proposal, $inputs);

        $this->assertSame($first['version']->id, $retry['version']->id);
        $this->assertSame($first['delivery']->id, $retry['delivery']->id);
        $this->assertSame($first['access_token'], $retry['access_token']);
        $this->assertTrue($retry['replayed']);
        $this->assertSame(1, DealProposalVersion::query()->where('deal_proposal_id', $proposal->id)->count());
        $this->assertSame(1, DealProposalDelivery::query()->count());
    }

    public function test_conflicting_issuance_key_is_rejected_without_rotating_token(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $inputs = $this->issuanceInputs($proposal);
        $this->preparePdfRenderer($this->validPdfBytes());
        $first = $this->issue($proposal, $inputs);
        $conflict = $inputs;
        $conflict['recipient'] = 'other@example.invalid';

        try {
            $this->issue($proposal, $conflict);
            $this->fail('A request key reused for another recipient must be rejected.');
        } catch (\DomainException) {
            $this->assertSame($first['access_token'], $proposal->fresh()->client_access_token);
            $this->assertSame(1, DealProposalVersion::query()->count());
            $this->assertSame(1, DealProposalDelivery::query()->count());
        }
    }

    public function test_pdf_render_failure_creates_no_issuance_records_or_token(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $inputs = $this->issuanceInputs($proposal);
        Pdf::shouldReceive('loadView')->once()->andThrow(new RuntimeException('synthetic render failure'));
        try {
            $this->issue($proposal, $inputs);
            $this->fail('PDF rendering failure must abort issuance.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Unable to render the resolved Deal proposal PDF.', $exception->getMessage());
            $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());
            $this->assertSame('synthetic render failure', $exception->getPrevious()->getMessage());
            $this->assertSame(0, DealProposalVersion::query()->count());
            $this->assertSame(0, DealProposalDelivery::query()->count());
            $this->assertNull($proposal->fresh()->client_access_token);
        }
    }

    public function test_artifact_write_failure_creates_no_issuance_records_or_token(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $inputs = $this->issuanceInputs($proposal);
        $this->preparePdfRenderer($this->validPdfBytes());
        Storage::disk('local')->put('deal-proposal-versions/'.$proposal->id, 'blocks-directory');
        try {
            $this->issue($proposal, $inputs);
            $this->fail('The failed private artifact write must abort issuance.');
        } catch (RuntimeException) {
            $this->assertSame(0, DealProposalVersion::query()->count());
            $this->assertSame(0, DealProposalDelivery::query()->count());
            $this->assertNull($proposal->fresh()->client_access_token);
        }
    }

    public function test_database_failure_rolls_back_records_and_cleans_new_artifact(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $inputs = $this->issuanceInputs($proposal);
        $this->preparePdfRenderer($this->validPdfBytes());
        $injected = false;
        DB::listen(function (QueryExecuted $query) use (&$injected): void {
            if (! $injected && preg_match('/^update\s+[\x60"]?deal_proposals\b/i', ltrim($query->sql)) === 1) {
                $injected = true;
                throw new RuntimeException('synthetic post-update database failure');
            }
        });
        try {
            $this->issue($proposal, $inputs);
            $this->fail('Injected database failure must abort the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('synthetic post-update database failure', $exception->getMessage());
        }

        $this->assertTrue($injected);
        $this->assertSame(0, DealProposalVersion::query()->count());
        $this->assertSame(0, DealProposalDelivery::query()->count());
        $this->assertNull($proposal->fresh()->client_access_token);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_approved_and_deletion_pending_proposals_are_rejected_before_rendering(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $proposal->forceFill(['quotation_approved_at' => now()])->save();
        $inputs = $this->issuanceInputs($proposal);
        try {
            $this->issue($proposal, $inputs);
            $this->fail('Internally approved proposals must not be issued.');
        } catch (\DomainException) {
            $this->assertSame(0, DealProposalVersion::query()->count());
        }

        $proposal->forceFill(['quotation_approved_at' => null])->save();
        $proposal->deal()->update(['delete_request_status' => 'pending']);
        try {
            $this->issue($proposal, $inputs);
            $this->fail('Deals with pending deletion requests must not be issued.');
        } catch (\DomainException) {
            $this->assertSame(0, DealProposalVersion::query()->count());
            $this->assertNull($proposal->fresh()->client_access_token);
        }
    }

    public function test_new_request_creates_next_version_and_superseded_retry_cannot_recover_old_token(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $firstInputs = $this->issuanceInputs($proposal);
        $this->preparePdfRenderer($this->validPdfBytes());
        $first = $this->issue($proposal, $firstInputs);

        $secondInputs = $firstInputs;
        $secondInputs['key'] = '22222222-2222-4222-8222-222222222222';
        $secondInputs['document']['reference_id'] = 'SNAPSHOT-REFERENCE-2';
        $secondInputs['identifiers']['reference_id'] = 'SNAPSHOT-REFERENCE-2';
        $secondInputs['html'] = '<main>Second resolved synthetic proposal.</main>';
        $this->preparePdfRenderer($this->validPdfBytes());
        $second = $this->issue($proposal, $secondInputs);
        $this->assertSame(2, $second['version']->version_number);
        $this->assertNotSame($first['access_token'], $second['access_token']);
        $this->assertSame(hash('sha256', $second['access_token']), $second['version']->issued_token_sha256);
        $this->assertSame($second['access_token'], $proposal->fresh()->client_access_token);

        $firstRetry = $this->issue($proposal, $firstInputs);
        $this->assertSame($first['version']->id, $firstRetry['version']->id);
        $this->assertFalse($firstRetry['is_current']);
        $this->assertNull($firstRetry['access_token']);
        $this->assertSame($second['access_token'], $proposal->fresh()->client_access_token);
    }

    public function test_client_view_serves_exact_issued_html_with_private_no_store_headers_after_live_deal_changes(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $token = str_repeat('a', 64);
        $version = $this->createCurrentIssuedVersion($proposal, $token, $this->validPdfBytes());
        $proposal->deal()->update(['deal_name' => 'Changed live deal name']);

        $response = $this->get(route('deals.proposal.client.show', ['token' => $token]));

        $response->assertOk();
        $response->assertSee('Issued snapshot marker: immutable content.', false);
        $response->assertDontSee('Changed live deal name');
        $this->assertCacheControlDirectives($response, [
            'private', 'no-store', 'no-cache', 'must-revalidate', 'max-age=0',
        ]);
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($version->rendered_html, $version->fresh()->rendered_html);
    }

    public function test_client_pdf_download_returns_exact_verified_private_bytes_and_no_store_headers(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $token = str_repeat('b', 64);
        $pdfBytes = $this->validPdfBytes();
        $version = $this->createCurrentIssuedVersion($proposal, $token, $pdfBytes);

        $response = $this->get(route('deals.proposal.client.download', ['token' => $token]));

        $response->assertOk();
        $response->assertContent($pdfBytes);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="proposal-v1.pdf"');
        $this->assertCacheControlDirectives($response, [
            'private', 'no-store', 'no-cache', 'must-revalidate', 'max-age=0',
        ]);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertDontSee($version->artifact_private_path);
    }

    public function test_client_version_access_rejects_expired_invalid_and_superseded_tokens(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $expiredToken = str_repeat('c', 64);
        $expired = now()->subMinute();
        $this->createCurrentIssuedVersion($proposal, $expiredToken, $this->validPdfBytes(), $expired);
        $proposal->forceFill(['client_access_expires_at' => $expired])->save();

        $this->get(route('deals.proposal.client.show', ['token' => $expiredToken]))->assertForbidden();

        $this->get(route('deals.proposal.client.show', ['token' => str_repeat('x', 64)]))->assertNotFound();

        $currentToken = str_repeat('d', 64);
        $proposal->forceFill([
            'client_access_token' => $currentToken,
            'client_access_expires_at' => now()->addDay(),
        ])->save();
        $this->get(route('deals.proposal.client.download', ['token' => $expiredToken]))->assertNotFound();
        $this->get(route('deals.proposal.client.show', ['token' => $currentToken]))->assertForbidden();
    }

    public function test_client_pdf_download_fails_closed_for_missing_or_corrupted_private_artifact(): void
    {
        Storage::fake('local');
        $proposal = $this->createProposal();
        $token = str_repeat('e', 64);
        $version = $this->createCurrentIssuedVersion($proposal, $token, $this->validPdfBytes());
        Storage::disk('local')->delete($version->artifact_private_path);
        $this->get(route('deals.proposal.client.download', ['token' => $token]))->assertNotFound();

        Storage::disk('local')->put($version->artifact_private_path, $this->validPdfBytes().'corruption');
        $this->get(route('deals.proposal.client.download', ['token' => $token]))->assertNotFound();
    }

    public function test_legacy_client_proposal_keeps_existing_html_rendering_without_fabricated_version(): void
    {
        $proposal = $this->createProposal();
        $token = str_repeat('f', 64);
        $proposal->forceFill([
            'client_access_token' => $token,
            'client_access_expires_at' => now()->addDay(),
            'document_html' => '<article>Legacy proposal document.</article>',
        ])->save();

        $response = $this->get(route('deals.proposal.client.show', ['token' => $token]));

        $response->assertOk();
        $response->assertSee('Legacy proposal document.', false);
        $this->assertSame(0, DealProposalVersion::query()->where('deal_proposal_id', $proposal->id)->count());
    }

    public function test_versioned_send_flag_defaults_disabled_and_preserves_legacy_email_behavior(): void
    {
        $this->assertFalse((bool) config('deals.versioned_proposal_issuance'));
        config(['deals.versioned_proposal_issuance' => false]);
        $proposal = $this->createProposal();
        $this->signInAsProposalSender();
        Mail::shouldReceive('html')->once()->withAnyArgs();

        $response = $this->post(route('deals.proposal.send', ['deal' => $proposal->deal_id]), [
            'recipient_email' => 'legacy@example.invalid',
        ]);

        $response->assertRedirect(route('deals.proposal.show', ['deal' => $proposal->deal_id]));
        $response->assertSessionHas('success', 'Proposal saved link sent to legacy@example.invalid.');
        $this->assertSame('sent', $proposal->fresh()->status);
        $this->assertSame('legacy@example.invalid', $proposal->fresh()->client_form_sent_to_email);
        $this->assertNotEmpty($proposal->fresh()->client_access_token);
        $this->assertSame(0, DealProposalVersion::query()->count());
        $this->assertSame(0, DealProposalDelivery::query()->count());
    }

    public function test_enabled_versioned_send_captures_one_html_snapshot_pdf_and_delivery_without_synchronous_mail(): void
    {
        config(['deals.versioned_proposal_issuance' => true]);
        Storage::fake('local');
        $proposal = $this->createProposal();
        $deal = $proposal->deal;
        $deal->forceFill([
            'company_name' => 'Captured Synthetic Company',
            'services' => 'Synthetic Service',
            'total_service_fee' => '123.45',
        ])->save();
        $proposal->forceFill([
            'reference_id' => 'SYNTHETIC-VERSIONED-ISSUE',
            'proposal_date' => '2026-10-29',
            'document_html' => '<article>Captured immutable proposal HTML.</article>',
            'price_regular' => '123.45',
            'price_subtotal' => '123.45',
            'price_total' => '123.45',
            'price_down' => '61.73',
            'price_balance' => '61.72',
        ])->save();
        $this->signInAsProposalSender();
        Mail::shouldReceive('html')->never();
        $captured = null;
        $pdfBytes = $this->validPdfBytes();
        $this->prepareControllerIssuanceRenderer($pdfBytes, $captured);

        $response = $this->postProposalSend($proposal, '33333333-3333-4333-8333-333333333333');

        $response->assertRedirect(route('deals.proposal.show', ['deal' => $proposal->deal_id]));
        $response->assertSessionHas('success', 'Proposal version issued and queued for email delivery.');
        $response->assertSessionHas('proposal_client_link');
        $version = DealProposalVersion::query()->sole();
        $delivery = DealProposalDelivery::query()->sole();
        $this->assertSame(1, $version->version_number);
        $this->assertSame('33333333-3333-4333-8333-333333333333', $version->issuance_request_key);
        $this->assertSame('Captured Synthetic Company', $version->resolved_proposal_data['business_name']);
        $this->assertSame('123.45', $version->commercial_values['service_total']);
        $this->assertSame('<article>Captured immutable proposal HTML.</article>', $version->rendered_html);
        $this->assertSame($version->rendered_html, $captured['proposalHtml']);
        $serializedDocumentData = json_decode(
            json_encode($captured['documentData'], JSON_THROW_ON_ERROR),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertSame(
            $this->canonicalizeJsonObjectKeys($version->resolved_proposal_data),
            $this->canonicalizeJsonObjectKeys($serializedDocumentData),
        );
        $this->assertSame(hash('sha256', $pdfBytes), $version->artifact_sha256);
        $this->assertSame($version->id, $delivery->deal_proposal_version_id);
        $this->assertSame(DealProposalDelivery::STATUS_PENDING, $delivery->status);
        $this->assertSame(hash('sha256', $proposal->fresh()->client_access_token), $version->issued_token_sha256);
        Storage::disk('local')->assertExists($version->artifact_private_path);
        $this->assertSame($pdfBytes, Storage::disk('local')->get($version->artifact_private_path));
        $this->assertSame(0, DealProposalDelivery::query()->where('status', DealProposalDelivery::STATUS_SENT)->count());
    }

    public function test_same_send_uuid_reuses_frozen_snapshot_and_token_after_live_data_changes(): void
    {
        config(['deals.versioned_proposal_issuance' => true]);
        Storage::fake('local');
        $proposal = $this->createProposal();
        $proposal->forceFill([
            'reference_id' => 'SYNTHETIC-RETRY',
            'proposal_date' => '2026-10-29',
            'document_html' => '<article>Original retry snapshot.</article>',
            'price_regular' => '90.00',
            'price_subtotal' => '90.00',
            'price_total' => '90.00',
        ])->save();
        $this->signInAsProposalSender();
        Mail::shouldReceive('html')->never();
        $this->prepareControllerIssuanceRenderer($this->validPdfBytes());
        $key = '44444444-4444-4444-8444-444444444444';
        $this->postProposalSend($proposal, $key)->assertSessionHas('success');
        $original = DealProposalVersion::query()->sole();
        $originalToken = $proposal->fresh()->client_access_token;
        $proposal->deal()->update([
            'company_name' => 'Changed After Original Issuance',
            'total_service_fee' => '999.99',
        ]);

        $retry = $this->postProposalSend($proposal, $key);

        $retry->assertSessionHas('success', 'The existing proposal version was reused. No new version or email was created.');
        $this->assertSame(1, DealProposalVersion::query()->count());
        $this->assertSame(1, DealProposalDelivery::query()->count());
        $this->assertSame($original->id, DealProposalVersion::query()->sole()->id);
        $this->assertSame($originalToken, $proposal->fresh()->client_access_token);
        $this->assertSame('Original retry snapshot.', strip_tags($original->rendered_html));
        $this->assertSame('90.00', $original->commercial_values['total']);
    }

    public function test_conflicting_send_uuid_is_rejected_without_rotating_token_or_creating_records(): void
    {
        config(['deals.versioned_proposal_issuance' => true]);
        Storage::fake('local');
        $proposal = $this->createProposal();
        $proposal->forceFill([
            'proposal_date' => '2026-10-29',
            'document_html' => '<article>Original conflict snapshot.</article>',
        ])->save();
        $this->signInAsProposalSender();
        Mail::shouldReceive('html')->never();
        $this->prepareControllerIssuanceRenderer($this->validPdfBytes());
        $key = '55555555-5555-4555-8555-555555555555';
        $this->postProposalSend($proposal, $key)->assertSessionHas('success');
        $token = $proposal->fresh()->client_access_token;

        $conflict = $this->postProposalSend($proposal, $key, 'different@example.invalid');

        $conflict->assertSessionHas('error', 'This issuance request key was already used with conflicting proposal or recipient details.');
        $this->assertSame($token, $proposal->fresh()->client_access_token);
        $this->assertSame(1, DealProposalVersion::query()->count());
        $this->assertSame(1, DealProposalDelivery::query()->count());
    }

    public function test_approved_proposal_is_not_issued_when_feature_flag_is_enabled(): void
    {
        config(['deals.versioned_proposal_issuance' => true]);
        $proposal = $this->createProposal();
        $proposal->forceFill([
            'status' => 'approved',
            'client_approved_at' => now(),
        ])->save();
        $this->signInAsProposalSender();
        Mail::shouldReceive('html')->never();
        Pdf::shouldReceive('loadView')->never();

        $this->postProposalSend($proposal, '66666666-6666-4666-8666-666666666666')
            ->assertSessionHas('error', 'Approved proposals are read-only and cannot be edited or resent.');

        $this->assertSame('approved', $proposal->fresh()->status);
        $this->assertNotEmpty($proposal->fresh()->client_approved_at);
        $this->assertNull($proposal->fresh()->client_access_token);
        $this->assertSame(0, DealProposalVersion::query()->count());
    }

    public function test_internally_approved_proposal_is_not_issued_when_feature_flag_is_enabled(): void
    {
        config(['deals.versioned_proposal_issuance' => true]);
        $proposal = $this->createProposal();
        $proposal->forceFill([
            'quotation_status' => 'approved',
            'quotation_approved_at' => now(),
        ])->save();
        $this->signInAsProposalSender();
        Mail::shouldReceive('html')->never();
        Pdf::shouldReceive('loadView')->never();

        $this->postProposalSend($proposal, '99999999-9999-4999-8999-999999999999')
            ->assertSessionHas('error', 'Approved proposals are read-only and cannot be edited or resent.');

        $this->assertNull($proposal->fresh()->client_access_token);
        $this->assertSame(0, DealProposalVersion::query()->count());
        $this->assertSame(0, DealProposalDelivery::query()->count());
    }

    public function test_failed_versioned_pdf_render_does_not_rotate_or_create_a_client_token(): void
    {
        config(['deals.versioned_proposal_issuance' => true]);
        $proposal = $this->createProposal();
        $proposal->forceFill(['proposal_date' => '2026-10-29'])->save();
        $this->signInAsProposalSender();
        Mail::shouldReceive('html')->never();
        Pdf::shouldReceive('loadView')->once()->andThrow(new RuntimeException('synthetic render failure'));

        $response = $this->postProposalSend($proposal, '77777777-7777-4777-8777-777777777777');

        $response->assertSessionHas('error', 'Unable to issue the proposal. No new client link was activated.');
        $this->assertNull($proposal->fresh()->client_access_token);
        $this->assertSame(0, DealProposalVersion::query()->count());
        $this->assertSame(0, DealProposalDelivery::query()->count());
    }

    public function test_failed_private_artifact_write_does_not_rotate_or_create_a_client_token(): void
    {
        config(['deals.versioned_proposal_issuance' => true]);
        Storage::fake('local');
        $proposal = $this->createProposal();
        $proposal->forceFill(['proposal_date' => '2026-10-29'])->save();
        $this->signInAsProposalSender();
        Mail::shouldReceive('html')->never();
        $this->prepareControllerIssuanceRenderer($this->validPdfBytes());
        Storage::disk('local')->put('deal-proposal-versions/'.$proposal->id, 'blocks-directory');

        $response = $this->postProposalSend($proposal, '88888888-8888-4888-8888-888888888888');

        $response->assertSessionHas('error', 'Unable to issue the proposal. No new client link was activated.');
        $this->assertNull($proposal->fresh()->client_access_token);
        $this->assertSame(0, DealProposalVersion::query()->count());
        $this->assertSame(0, DealProposalDelivery::query()->count());
    }

    public function test_enabled_send_requires_a_uuid_preserved_on_validation_redirect(): void
    {
        config(['deals.versioned_proposal_issuance' => true]);
        $proposal = $this->createProposal();
        $this->signInAsProposalSender();

        $response = $this->post(route('deals.proposal.send', ['deal' => $proposal->deal_id]), [
            'recipient_email' => 'client@example.invalid',
            'issuance_request_key' => 'not-a-uuid',
        ]);

        $response->assertSessionHasErrors('issuance_request_key');
        $response->assertSessionHas('_old_input.issuance_request_key', 'not-a-uuid');
        $this->assertNull($proposal->fresh()->client_access_token);
        $this->assertSame(0, DealProposalVersion::query()->count());
    }
}
