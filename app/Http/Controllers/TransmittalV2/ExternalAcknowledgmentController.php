<?php

namespace App\Http\Controllers\TransmittalV2;

use App\Http\Controllers\Controller;
use App\Http\Middleware\TransmittalV2\EnsureTransmittalV2Enabled;
use App\Models\ExternalAcknowledgment;
use App\Models\ProofOfTransmission;
use App\Models\Transmittal;
use App\Services\TransmittalWorkflowBoundary;
use App\Models\TransmittalHistory;
use App\Models\TransmittalReceipt;
use App\Models\TransmittalRecipient;
use App\Services\TransmittalV2\TransmittalAcknowledgmentToken;
use App\Services\TransmittalV2\TransmittalReceiptNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * V2 public acknowledgment endpoint protected by the default-disabled feature gate.
 */
final class ExternalAcknowledgmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [EnsureTransmittalV2Enabled::class];
    }

    public function show(string $token): View
    {
        $this->assertEnabled();
        $recipient = $this->recipientFor($token);

        return view('transmittal-v2.acknowledgments.show', [
            'recipient' => $recipient,
            'transmittal' => $recipient->transmittal,
        ]);
    }

    public function store(
        Request $request,
        string $token,
        TransmittalReceiptNumber $receiptNumbers
    ): RedirectResponse {
        $this->assertEnabled();
        $recipient = $this->recipientFor($token);

        if ($recipient->receipt) {
            return redirect()->route('transmittal-v2.acknowledgments.show', ['token' => $token])
                ->with('status', 'This acknowledgment has already been recorded.');
        }

        $validated = $request->validate([
            'signed_name' => ['required', 'string', 'max:160'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'confirmation_check' => ['required', 'accepted'],
        ]);

        DB::transaction(function () use ($recipient, $token, $validated, $request, $receiptNumbers): void {
            // Serialize acknowledgments for this transmittal, then revalidate the token
            // and recipient under row locks before any receipt or audit writes.
            $transmittal = TransmittalWorkflowBoundary::v2Query()
                ->whereKey($recipient->transmittal_id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)
                ->where('transmittal_id', $transmittal->id)
                ->whereKey($recipient->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCurrentToken($locked, $token);
            abort_unless($locked->sent_at !== null, 404);

            if ($locked->receipt()->exists()) {
                abort(409, 'This acknowledgment has already been recorded.');
            }

            if (ExternalAcknowledgment::query()
                ->where('transmittal_id', $transmittal->id)
                ->where('recipient_id', $locked->id)
                ->where('event', 'acknowledged')
                ->exists()) {
                abort(409, 'A prior acknowledgment event exists without its receipt; reconciliation is required.');
            }

            $receivedAt = now();
            $receiptCode = 'ORDO-TRR-'.strtoupper(Str::random(24));
            $receipt = $transmittal->receipts()->create([
                'transmittal_id' => $transmittal->id,
                'recipient_id' => $locked->id,
                'receipt_no' => $receiptNumbers->generate(),
                'receipt_date' => $receivedAt->toDateString(),
                'signed_name' => $validated['signed_name'],
                'received_by' => $validated['signed_name'],
                'comments' => $validated['comments'] ?? null,
                'receipt_code' => $receiptCode,
                'snapshot' => [
                    'tracking_number' => $transmittal->tracking_number,
                    'subject' => $transmittal->subject,
                    'recipient_email' => $locked->email,
                    'signed_name' => $validated['signed_name'],
                    'comments' => $validated['comments'] ?? null,
                ],
                'received_at' => $receivedAt,
                'ip_address' => (string) $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ]);

            ExternalAcknowledgment::query()->create([
                'transmittal_id' => $transmittal->id,
                'recipient_id' => $locked->id,
                'receipt_id' => $receipt->id,
                'event' => 'acknowledged',
                'details' => [
                    'receipt_code' => $receipt->receipt_code,
                    'signed_name' => $validated['signed_name'],
                    'ip_address' => (string) $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                ],
                'occurred_at' => $receivedAt,
            ]);

            $sentProof = $locked->proofs()
                ->where('status', 'sent')
                ->latest('transmitted_at')
                ->first();

            if ($sentProof) {
                ProofOfTransmission::query()->create([
                    'transmittal_id' => $transmittal->id,
                    'recipient_id' => $locked->id,
                    'channel' => $sentProof->channel,
                    'status' => 'acknowledged',
                    'transaction_reference' => $sentProof->transaction_reference,
                    'acknowledged_at' => $receivedAt,
                    'transmitted_at' => $sentProof->transmitted_at,
                    'provider_metadata' => [
                        'receipt_code' => $receipt->receipt_code,
                        'acknowledged_by' => $validated['signed_name'],
                    ],
                ]);
            }

            TransmittalHistory::query()->create([
                'transmittal_id' => $transmittal->id,
                'actor_id' => null,
                'action' => 'acknowledged',
                'description' => 'Acknowledgment received from the addressed recipient.',
                'metadata' => ['receipt_code' => $receipt->receipt_code],
                'created_at' => $receivedAt,
            ]);

            if (! $transmittal->recipients()->whereDoesntHave('receipt')->exists()) {
                $transmittal->forceFill(['status' => 'acknowledged'])->save();
            }
        });

        return redirect()->route('transmittal-v2.acknowledgments.show', ['token' => $token])
            ->with('status', 'Your acknowledgment has been recorded.');
    }

    private function recipientFor(string $token): TransmittalRecipient
    {
        abort_unless(TransmittalAcknowledgmentToken::isValid($token), 404);

        $recipient = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)
            ->with([
                'transmittal:id,workflow_version,tracking_number,subject,status',
                'receipt',
            ])
            ->where('acknowledgment_token_hash', TransmittalAcknowledgmentToken::hash($token))
            ->where('acknowledgment_expires_at', '>', now())
            ->whereNull('acknowledgment_revoked_at')
            ->first();

        abort_unless($recipient && $recipient->sent_at !== null, 404);
        $parent = TransmittalWorkflowBoundary::requireV2($recipient->transmittal);
        $recipient->setRelation('transmittal', $parent);
        if ($recipient->receipt !== null) {
            TransmittalWorkflowBoundary::assertChild($parent, $recipient->receipt);
            abort_unless((string) $recipient->receipt->recipient_id === (string) $recipient->id, 404);
        }

        return $recipient;
    }

    private function assertCurrentToken(TransmittalRecipient $recipient, string $token): void
    {
        abort_unless(
            TransmittalAcknowledgmentToken::isValid($token)
                && is_string($recipient->acknowledgment_token_hash)
                && hash_equals(
                    $recipient->acknowledgment_token_hash,
                    TransmittalAcknowledgmentToken::hash($token)
                )
                && $recipient->acknowledgment_expires_at !== null
                && $recipient->acknowledgment_expires_at->isFuture()
                && $recipient->acknowledgment_revoked_at === null,
            404
        );
    }

    private function assertEnabled(): void
    {
        abort_unless((bool) config('transmittal_v2.enabled', false), 404);
    }
}
