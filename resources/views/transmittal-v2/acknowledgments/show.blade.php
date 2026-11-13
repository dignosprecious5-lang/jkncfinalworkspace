<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transmittal acknowledgment</title>
    <style>
        body { margin: 0; background: #f3f6fa; color: #172033; font: 16px/1.5 Arial, sans-serif; }
        main { max-width: 680px; margin: 48px auto; padding: 28px; background: #fff; border: 1px solid #dce3ed; border-radius: 12px; }
        h1 { margin-top: 0; font-size: 1.55rem; }
        .reference { padding: 16px; background: #f6f8fb; border-radius: 8px; margin: 20px 0; }
        label { display: block; margin: 16px 0 6px; font-weight: 700; }
        input, textarea { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #aeb9c8; border-radius: 6px; font: inherit; }
        button { margin-top: 18px; padding: 11px 18px; border: 0; border-radius: 6px; background: #123b70; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .notice { padding: 12px 14px; background: #edf7f0; border: 1px solid #b8ddc2; border-radius: 6px; }
        .error { color: #a21d27; }
    </style>
</head>
<body>
<main>
    <h1>Transmittal acknowledgment</h1>

    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif

    <div class="reference">
        <strong>Reference:</strong> {{ $transmittal->tracking_number }}<br>
        <strong>Subject:</strong> {{ $transmittal->subject }}<br>
        <strong>Addressed to:</strong> {{ $recipient->name ?: $recipient->email }}<br>
        <strong>Link expires:</strong> {{ $recipient->acknowledgment_expires_at?->format('M j, Y g:i A') }}
    </div>

    @if ($recipient->receipt)
        <section class="notice" aria-live="polite">
            <h2>Receipt confirmed</h2>
            <p>Your acknowledgment has been recorded.</p>
            <p><strong>Receipt number:</strong> {{ $recipient->receipt->receipt_no }}</p>
            <p><strong>Recorded:</strong> {{ $recipient->receipt->received_at?->format('M j, Y g:i A') }}</p>
        </section>
    @else
        <p>Confirm receipt of this transmittal. This page does not provide access to other transmittals or stored documents.</p>
        <form method="post" action="{{ route('transmittal-v2.acknowledgments.store', ['token' => request()->route('token')]) }}">
            @csrf
            <label for="signed_name">Your full name</label>
            <input id="signed_name" name="signed_name" value="{{ old('signed_name', $recipient->name) }}" autocomplete="name" maxlength="160" required>
            @error('signed_name') <p class="error">{{ $message }}</p> @enderror

            <label for="comments">Comments (optional)</label>
            <textarea id="comments" name="comments" rows="4" maxlength="2000">{{ old('comments') }}</textarea>
            @error('comments') <p class="error">{{ $message }}</p> @enderror

            <label>
                <input type="checkbox" name="confirmation_check" value="1" required>
                I confirm receipt of this transmittal.
            </label>
            @error('confirmation_check') <p class="error">{{ $message }}</p> @enderror

            <button type="submit">Confirm receipt</button>
        </form>
    @endif
</main>
</body>
</html>
