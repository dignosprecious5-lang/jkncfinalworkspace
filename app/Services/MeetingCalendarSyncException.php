<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class MeetingCalendarSyncException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $eventId,
        public readonly ?string $conferenceUrl,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
