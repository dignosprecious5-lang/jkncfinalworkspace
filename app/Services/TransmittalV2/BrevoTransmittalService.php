<?php

namespace App\Services\TransmittalV2;

use App\Models\Transmittal;
use App\Models\TransmittalDocument;
use App\Models\TransmittalRecipient;
use App\Services\TransmittalWorkflowBoundary;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BrevoTransmittalService
{
    /**
     * Submit an already validated, immutable outbox payload through the same
     * Brevo client/configuration used by existing V2 callers. The reference
     * and tag are correlation aids only; no provider idempotency is asserted.
     */
    public function submitOutboxPayload(string $dispatchUuid, array $payload): TransmittalDispatchResult
    {
        $this->assertEnabled();
        if (! \Illuminate\Support\Str::isUuid($dispatchUuid)) {
            return TransmittalDispatchResult::rejected('invalid_dispatch_reference', 422);
        }

        $apiKey = config('transmittal_v2.brevo.api_key');
        $senderEmail = config('transmittal_v2.brevo.sender_email');
        if (! is_string($apiKey) || $apiKey === '' || ! is_string($senderEmail) || $senderEmail === '') {
            return TransmittalDispatchResult::rejected('provider_configuration_missing', 503);
        }
        $recipientEmail = data_get($payload, 'to.email');
        $subject = $payload['subject'] ?? null;
        $html = $payload['html'] ?? null;
        $attachmentPayload = $payload['attachments'] ?? [];
        if (! is_string($recipientEmail) || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)
            || ! is_string($subject) || ! is_string($html) || ! is_array($attachmentPayload)) {
            return TransmittalDispatchResult::rejected('invalid_send_payload', 422);
        }

        $attachments = [];
        foreach ($attachmentPayload as $attachment) {
            if (! is_array($attachment) || ! is_string($attachment['name'] ?? null)
                || ! is_string($attachment['content'] ?? null)
                || base64_decode($attachment['content'], true) === false) {
                return TransmittalDispatchResult::rejected('invalid_attachment_payload', 422);
            }
            $attachments[] = ['name' => basename($attachment['name']), 'content' => $attachment['content']];
        }

        $referenceTag = 'ordo-v2-'.$dispatchUuid;
        $brevoPayload = [
            'sender' => [
                'name' => config('transmittal_v2.brevo.sender_name') ?: 'JK&C Transmittal Notice',
                'email' => $senderEmail,
            ],
            'to' => [[
                'email' => $recipientEmail,
                'name' => is_string(data_get($payload, 'to.name')) ? data_get($payload, 'to.name') : $recipientEmail,
            ]],
            'replyTo' => [
                'name' => config('transmittal_v2.brevo.sender_name') ?: 'JK&C Support',
                'email' => $senderEmail,
            ],
            'subject' => $subject,
            'htmlContent' => $html,
            'tags' => [$referenceTag],
            'headers' => ['X-Entity-Ref-ID' => $dispatchUuid],
        ];
        if ($attachments !== []) {
            $brevoPayload['attachment'] = $attachments;
        }

        try {
            $response = $this->client($apiKey)->post('https://api.brevo.com/v3/smtp/email', $brevoPayload);
        } catch (\Illuminate\Http\Client\ConnectionException) {
            return TransmittalDispatchResult::unknown('provider_connection_outcome_unknown');
        } catch (\Throwable) {
            return TransmittalDispatchResult::unknown('provider_transport_outcome_unknown');
        }

        $status = $response->status();
        if ($response->successful()) {
            $messageId = $response->json('messageId');
            if (! is_string($messageId) || $messageId === '' || strlen($messageId) > 255 || preg_match('/[\r\n]/', $messageId)) {
                $messageId = null;
            }
            return TransmittalDispatchResult::accepted($messageId, $status);
        }
        if ($status === 408 || $status === 429 || $status >= 500) {
            return TransmittalDispatchResult::unknown('provider_response_outcome_unknown', $status);
        }
        if ($status >= 400 && $status < 500) {
            return TransmittalDispatchResult::rejected('provider_rejected', $status);
        }

        return TransmittalDispatchResult::unknown('provider_response_outcome_unknown', $status);
    }

    public function sendAcknowledgmentRequest(TransmittalRecipient $recipient, string $token): string
    {
        $this->assertEnabled();
        $apiKey = config('transmittal_v2.brevo.api_key');
        $senderEmail = config('transmittal_v2.brevo.sender_email');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($senderEmail) || $senderEmail === '') {
            throw new RuntimeException('V2 email delivery is not configured. Set ORDO_TRANSMITTAL_BREVO_API_KEY and ORDO_TRANSMITTAL_BREVO_SENDER_EMAIL through the approved configuration process.');
        }

        if (! view()->exists('emails.transmittal-v2.acknowledgment-request')) {
            throw new RuntimeException('The V2 acknowledgment email template is not integrated.');
        }

        $recipient = TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)->findOrFail($recipient->id);
        $transmittal = TransmittalWorkflowBoundary::requireV2($recipient->transmittal);
        $url = route('transmittal-v2.acknowledgments.show', ['token' => $token]);
        $attachments = $transmittal->documents()
            ->where('category', 'attachment')
            ->get(['original_name', 'storage_path'])
            ->map(function (TransmittalDocument $document) use ($transmittal): array {
                TransmittalWorkflowBoundary::assertChild($transmittal, $document);
                $disk = Storage::disk('local');
                $path = $disk->path($document->storage_path);
                if (! is_file($path) || ! is_readable($path)) {
                    throw new RuntimeException('An associated transmittal attachment is missing or unreadable; delivery was stopped.');
                }

                $digest = hash_file('sha256', $path);
                if (! is_string($digest) || ! hash_equals((string) $document->sha256, $digest)) {
                    throw new RuntimeException('An associated transmittal attachment failed integrity verification; delivery was stopped.');
                }

                $content = file_get_contents($path);
                if (! is_string($content)) {
                    throw new RuntimeException('An associated transmittal attachment could not be read; delivery was stopped.');
                }

                return [
                    'name' => basename($document->original_name),
                    'content' => base64_encode($content),
                ];
            })
            ->values()
            ->all();

        $payload = [
            'sender' => [
                'name' => config('transmittal_v2.brevo.sender_name'),
                'email' => $senderEmail,
            ],
            'to' => [[
                'email' => $recipient->email,
                'name' => $recipient->name ?: $recipient->email,
            ]],
            'subject' => "Transmittal {$transmittal->tracking_number}: {$transmittal->subject}",
            'htmlContent' => view('emails.transmittal-v2.acknowledgment-request', [
                'recipient' => $recipient,
                'transmittal' => $transmittal,
                'acknowledgmentUrl' => $url,
            ])->render(),
            'headers' => [
                'X-Entity-Ref-ID' => (string) Str::uuid(),
            ],
        ];
        if ($attachments !== []) {
            $payload['attachment'] = $attachments;
        }

        $response = $this->client($apiKey)->post('https://api.brevo.com/v3/smtp/email', $payload)->throw();

        return (string) ($response->json('messageId') ?? '');
    }

    /**
     * Send direct transmittal document email with PDF and attachments via Brevo.
     */
    public function sendTransmittalDocument(
        string $recipientEmail,
        string $subject,
        string $htmlContent,
        string $pdfOutput,
        string $pdfFileName,
        array $fileAttachments = [],
        ?Transmittal $transmittal = null
    ): string {
        $this->assertEnabled();
        abort_unless($transmittal !== null, 404);
        $transmittal = TransmittalWorkflowBoundary::requireV2($transmittal);
        $apiKey = config('transmittal_v2.brevo.api_key');
        $senderEmail = config('transmittal_v2.brevo.sender_email');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($senderEmail) || $senderEmail === '') {
            throw new RuntimeException('V2 email delivery is not configured. Set ORDO_TRANSMITTAL_BREVO_API_KEY and ORDO_TRANSMITTAL_BREVO_SENDER_EMAIL through the approved configuration process.');
        }

        $attachments = [
            [
                'name' => $pdfFileName,
                'content' => base64_encode($pdfOutput),
            ],
        ];

        foreach ($fileAttachments as $file) {
            if (! is_array($file) || ! isset($file['path']) || ! is_string($file['path'])
                || ! is_file($file['path']) || ! is_readable($file['path'])) {
                throw new RuntimeException('An associated transmittal attachment is missing or unreadable; delivery was stopped.');
            }

            $content = file_get_contents($file['path']);
            if (! is_string($content)) {
                throw new RuntimeException('An associated transmittal attachment could not be read; delivery was stopped.');
            }

            $attachments[] = [
                'name' => $file['name'] ?? basename($file['path']),
                'content' => base64_encode($content),
            ];
        }

        $payload = [
            'sender' => [
                'name' => config('transmittal_v2.brevo.sender_name') ?: 'JK&C Transmittal Notice',
                'email' => $senderEmail,
            ],
            'to' => [[
                'email' => $recipientEmail,
            ]],
            'replyTo' => [
                'name' => config('transmittal_v2.brevo.sender_name') ?: 'JK&C Support',
                'email' => $senderEmail,
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'headers' => [
                'X-Entity-Ref-ID' => (string) Str::uuid(),
                'Precedence' => 'bulk',
                'Auto-Submitted' => 'auto-generated',
            ],
            'attachment' => $attachments,
        ];

        $response = $this->client($apiKey)->post('https://api.brevo.com/v3/smtp/email', $payload)->throw();

        return (string) ($response->json('messageId') ?? '');
    }

    private function client(string $apiKey): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders(['api-key' => $apiKey])
            ->connectTimeout(5)
            ->timeout(max(1, (int) config('transmittal_v2.brevo.timeout_seconds', 20)));
    }

    private function assertEnabled(): void
    {
        abort_unless((bool) config('transmittal_v2.enabled', false), 404);
    }
}
