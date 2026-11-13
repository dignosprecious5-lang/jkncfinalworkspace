<?php

namespace App\Services\TransmittalV2;

use App\Models\ApprovalAuthorityCode;
use App\Models\Transmittal;
use App\Services\TransmittalWorkflowBoundary;
use App\Models\TransmittalHistory;
use App\Models\User;
use App\Services\TransmittalV2Authorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TransmittalApprovalService
{
    public function approve(Transmittal $transmittal, User $approver): ApprovalAuthorityCode
    {
        $this->assertEnabled();
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        if (! TransmittalV2Authorization::canApprove($approver)
            || TransmittalV2Authorization::ownsTransmittal($approver, $transmittal->prepared_by)) {
            throw new AccessDeniedHttpException('A separate authorized approver must approve this transmittal.');
        }

        return DB::transaction(function () use ($transmittal, $approver): ApprovalAuthorityCode {
            $locked = TransmittalWorkflowBoundary::v2Query()
                ->with(['preparer', 'recipients', 'documents', 'workspaceItems.document'])
                ->lockForUpdate()
                ->findOrFail($transmittal->id);

            if (! TransmittalV2Authorization::canApprove($approver)
                || TransmittalV2Authorization::ownsTransmittal($approver, $locked->prepared_by)) {
                throw new AccessDeniedHttpException('A separate authorized approver must approve this transmittal.');
            }

            if ($locked->status !== 'pending_approval') {
                throw ValidationException::withMessages([
                    'status' => 'Only a transmittal submitted for approval can be approved.',
                ]);
            }

            if ($locked->recipients->isEmpty()) {
                throw ValidationException::withMessages([
                    'recipients' => 'At least one external recipient is required before approval.',
                ]);
            }

            abort_if($this->hasFrozenApprovalEvidence($locked), 409, 'Frozen approval evidence cannot be replaced.');
            foreach ($locked->workspaceItems as $item) {
                TransmittalWorkflowBoundary::assertChild($locked, $item);
                if ($item->document_id !== null) {
                    $document = $locked->documents()->whereKey($item->document_id)->lockForUpdate()->firstOrFail();
                    $item->setRelation('document', $document);
                }
            }

            $approvedAt = now();
            $snapshot = [
                'tracking_number' => $locked->tracking_number,
                'external_reference' => $locked->external_reference,
                'subject' => $locked->subject,
                'purpose' => $locked->purpose,
                'direction' => $locked->direction,
                'transmittal_date' => $locked->transmittal_date?->toDateString(),
                'origin_office' => $locked->origin_office,
                'recipient_address' => $locked->recipient_address,
                'related_company' => $locked->related_company,
                'delivery_method' => $locked->delivery_method,
                'delivery_type' => $locked->delivery_type,
                'delivery_actions' => $locked->delivery_actions ?? [],
                'prepared_by_name' => $locked->prepared_by_name,
                'prepared_at' => $locked->prepared_at?->toIso8601String(),
                'approved_by_name' => $approver->name,
                'operations_manager' => $locked->operations_manager,
                'delivered_by' => $locked->delivered_by,
                'received_by' => $locked->received_by,
                'processing_company' => $locked->processing_company,
                'confidentiality' => $locked->confidentiality,
                'prepared_by' => [
                    'name' => $locked->preparer?->name ?? 'Public submitter',
                    'email' => $locked->preparer?->email,
                ],
                'recipients' => $locked->recipients->map(fn ($recipient) => [
                    'name' => $recipient->name,
                    'email' => $recipient->email,
                    'organization' => $recipient->organization,
                ])->all(),
                'documents' => $locked->documents->map(fn ($document) => [
                    'name' => $document->original_name,
                    'category' => $document->category,
                    'mime_type' => $document->mime_type,
                    'size_bytes' => $document->size_bytes,
                    'sha256' => $document->sha256,
                ])->all(),
                'items' => $locked->workspaceItems->map(fn ($item) => [
                    'id' => $item->id,
                    'document_id' => $item->document_id,
                    'line_number' => $item->line_number,
                    'position' => $item->position,
                    'particular' => $item->particular,
                    'unique_id' => $item->unique_id,
                    'quantity' => $item->quantity,
                    'description' => $item->description,
                    'remarks' => $item->remarks,
                    'attachment' => $item->document ? [
                        'name' => $item->document->original_name,
                        'sha256' => $item->document->sha256,
                    ] : null,
                ])->all(),
                'approved_by' => [
                    'name' => $approver->name,
                    'email' => $approver->email,
                ],
                'approved_at' => $approvedAt->toIso8601String(),
            ];
            $serialized = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $code = 'ORDO-AUTH-'.strtoupper(Str::random(20));

            $authority = ApprovalAuthorityCode::query()->create([
                'transmittal_id' => $locked->id,
                'approved_by' => $approver->id,
                'code' => $code,
                'frozen_snapshot' => $snapshot,
                'snapshot_hash' => hash('sha256', $serialized),
                'approved_at' => $approvedAt,
            ]);

            $locked->forceFill([
                'status' => 'approved',
                'workflow_status' => 'Accepted',
                'approval_status' => 'Approved',
                'approved_by' => $approver->id,
                'approved_at' => $approvedAt,
                'approved_by_name' => $approver->name,
                'approved_snapshot' => $snapshot,
                'approval_authority_code' => $code,
            ])->save();

            TransmittalHistory::query()->create([
                'transmittal_id' => $locked->id,
                'actor_id' => $approver->id,
                'action' => 'approved',
                'description' => "Approved with authority code {$code}.",
                'metadata' => ['snapshot_hash' => hash('sha256', $serialized)],
                'created_at' => $approvedAt,
            ]);

            return $authority;
        });
    }

    public function reject(Transmittal $transmittal, User $approver, string $reason): Transmittal
    {
        $this->assertEnabled();
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        if (! TransmittalV2Authorization::canApprove($approver)) {
            throw new AccessDeniedHttpException('Only an administrator can reject a transmittal.');
        }

        return DB::transaction(function () use ($transmittal, $approver, $reason): Transmittal {
            $locked = TransmittalWorkflowBoundary::v2Query()
                ->lockForUpdate()
                ->findOrFail($transmittal->id);

            if (! TransmittalV2Authorization::canApprove($approver)) {
                throw new AccessDeniedHttpException('Only an administrator can reject a transmittal.');
            }

            if ($locked->status !== 'pending_approval') {
                throw ValidationException::withMessages([
                    'status' => 'Only a transmittal awaiting review can be rejected.',
                ]);
            }

            abort_if($this->hasFrozenApprovalEvidence($locked), 409, 'Conflicting approval evidence prevents rejection.');

            $rejectedAt = now();
            $locked->forceFill([
                'status' => 'rejected',
                'workflow_status' => 'Reverted',
                'approval_status' => 'Rejected',
                'review_note' => $reason,
                'rejected_by' => $approver->id,
                'rejected_at' => $rejectedAt,
                'rejection_reason' => $reason,
            ])->save();

            TransmittalHistory::query()->create([
                'transmittal_id' => $locked->id,
                'actor_id' => $approver->id,
                'action' => 'rejected',
                'description' => 'Rejected by an administrator.',
                'metadata' => ['reason' => $reason],
                'created_at' => $rejectedAt,
            ]);

            return $locked;
        });
    }

    private function assertEnabled(): void
    {
        abort_unless((bool) config('transmittal_v2.enabled', false), 404);
    }

    private function hasFrozenApprovalEvidence(Transmittal $transmittal): bool
    {
        return $transmittal->approved_snapshot !== null
            || $transmittal->approved_at !== null
            || $transmittal->approved_by !== null
            || $transmittal->approval_authority_code !== null
            || strtolower((string) $transmittal->approval_status) === 'approved'
            || $transmittal->authorityCode()->exists();
    }
}
