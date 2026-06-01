<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notice of Meeting</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    @php
        $isGuest = strtolower((string) ($attendee->source_type ?? '')) === 'guest'
            || strtolower((string) ($attendee->position ?? '')) === 'guest';
    @endphp

    <p>Dear {{ $attendee->name ?: ($isGuest ? 'Guest' : 'Attendee') }},</p>

    @if ($isGuest)
        <p>You are invited as a guest to attend the meeting stated in this notice. Your attendance is requested for reference, participation, or observation purposes as may be applicable.</p>
    @else
        <p>Please see the attached Notice of Meeting for your reference.</p>
    @endif

    <p>
        <strong>Notice No.:</strong> {{ $notice->notice_number ?: '-' }}<br>
        <strong>Meeting:</strong> {{ trim(($notice->type_of_meeting ?: '') . ' ' . ($notice->governing_body ?: '')) ?: '-' }}<br>
        <strong>Date:</strong> {{ optional($notice->date_of_meeting)->format('F d, Y') ?: '-' }}<br>
        <strong>Time:</strong> {{ $notice->time_started ? \Carbon\Carbon::parse($notice->time_started)->format('h:i A') : '-' }}<br>
        <strong>Location:</strong> {{ $notice->location ?: '-' }}
    </p>

    <p>Kindly review the attached PDF notice.</p>

    <p>
        Very truly yours,<br>
        <strong>{{ $notice->secretary ?: 'Corporate Secretary' }}</strong>
    </p>
</body>
</html>