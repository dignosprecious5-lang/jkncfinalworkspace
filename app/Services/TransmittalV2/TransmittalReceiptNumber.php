<?php

namespace App\Services\TransmittalV2;

final class TransmittalReceiptNumber
{
    /**
     * The existing unique receipt_no index is the final concurrency arbiter.
     * A cryptographically random suffix avoids a read-then-increment race.
     */
    public function generate(): string
    {
        return 'TRR-'.strtoupper(bin2hex(random_bytes(16)));
    }
}
