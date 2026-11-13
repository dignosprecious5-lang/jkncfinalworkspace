<?php

namespace App\Enums;

/** Immutable attempt-ledger event types; these are not queue/job states. */
enum TransmittalEmailDispatchAttemptEvent: string
{
    case SubmissionStarted = 'submission_started';
    case ProviderAccepted = 'provider_accepted';
    case ProviderRejected = 'provider_rejected';
    case OutcomeUnknown = 'outcome_unknown';
}
