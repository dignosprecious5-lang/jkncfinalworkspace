<?php

use App\Services\GoogleMeetSpaceService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.google_calendar', [
        'access_token' => 'meet-access-token',
        'meet_api_base' => 'https://meet.test/v2',
        'timeout' => 5,
    ]);
    Http::preventStrayRequests();
});

test('calendar-created Meet URL resolves to its canonical space and sets OPEN access', function () {
    Http::fake(function ($request) {
        if ($request->method() === 'GET') {
            return Http::response([
                'name' => 'spaces/CalendarCreatedSpace',
                'meetingUri' => 'https://meet.google.com/abc-defg-hij',
                'config' => ['accessType' => 'RESTRICTED'],
            ]);
        }

        return Http::response([
            'name' => 'spaces/CalendarCreatedSpace',
            'config' => ['accessType' => 'OPEN'],
        ]);
    });

    app(GoogleMeetSpaceService::class)->configureOpenAccess('https://meet.google.com/abc-defg-hij');

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://meet.test/v2/spaces/abc-defg-hij'
        && $request->hasHeader('Authorization', 'Bearer meet-access-token'));
    Http::assertSent(fn ($request) => $request->method() === 'PATCH'
        && $request->url() === 'https://meet.test/v2/spaces/CalendarCreatedSpace?updateMask=config.accessType'
        && $request->data() === [
            'name' => 'spaces/CalendarCreatedSpace',
            'config' => ['accessType' => 'OPEN'],
        ]);
});

test('OPEN Meet spaces are idempotent and are not patched again', function () {
    Http::fake(fn () => Http::response([
        'name' => 'spaces/CalendarCreatedSpace',
        'config' => ['accessType' => 'OPEN'],
    ]));

    app(GoogleMeetSpaceService::class)->configureOpenAccess('https://meet.google.com/abc-defg-hij');

    Http::assertSentCount(1);
    Http::assertNotSent(fn ($request) => $request->method() === 'PATCH');
});

test('Meet authorization or Workspace policy failures are sanitized', function () {
    Http::fake(fn () => Http::response([
        'error' => ['status' => 'PERMISSION_DENIED', 'message' => 'internal provider detail'],
    ], 403));

    expect(fn () => app(GoogleMeetSpaceService::class)
        ->configureOpenAccess('https://meet.google.com/abc-defg-hij'))
        ->toThrow(\RuntimeException::class, 'Google Meet OPEN access was not permitted by the Google authorization or Workspace policy.');
});
