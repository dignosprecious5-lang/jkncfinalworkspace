<?php

namespace App\Services\TransmittalV2;

use RuntimeException;

/** Local/test-only provider. It never performs network or mail transport. */
final class MockTransmittalDispatchProvider implements TransmittalDispatchProvider
{
    /** @var list<array{dispatch_uuid:string,payload:array}> */
    private array $submissions = [];

    public function __construct(private readonly ?TransmittalDispatchResult $nextResult = null) {}

    public function submit(string $dispatchUuid, array $payload): TransmittalDispatchResult
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('The mock provider is restricted to local and testing environments.');
        }
        $attachments = array_map(static function (array $attachment): array {
            $content = base64_decode((string) ($attachment['content'] ?? ''), true);
            return [
                'name_fingerprint' => hash('sha256', (string) ($attachment['name'] ?? '')),
                'sha256' => is_string($content) ? hash('sha256', $content) : null,
                'size_bytes' => is_string($content) ? strlen($content) : null,
            ];
        }, $payload['attachments'] ?? []);
        $this->submissions[] = [
            'dispatch_uuid' => $dispatchUuid,
            'recipient_fingerprint' => hash('sha256', mb_strtolower((string) data_get($payload, 'to.email'))),
            'subject_fingerprint' => hash('sha256', (string) ($payload['subject'] ?? '')),
            'attachment_evidence' => $attachments,
        ];

        return $this->nextResult ?? TransmittalDispatchResult::accepted('mock-'.$dispatchUuid, 202);
    }

    public function submissions(): array
    {
        return $this->submissions;
    }
}
