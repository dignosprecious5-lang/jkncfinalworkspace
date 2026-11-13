<?php

namespace App\Services\TransmittalV2;

/** V2 adapter over ORDO's existing Brevo HTTP transport. */
final class BrevoTransmittalDispatchProvider implements TransmittalDispatchProvider
{
    public function __construct(private readonly BrevoTransmittalService $transport) {}

    public function submit(string $dispatchUuid, array $payload): TransmittalDispatchResult
    {
        return $this->transport->submitOutboxPayload($dispatchUuid, $payload);
    }
}
