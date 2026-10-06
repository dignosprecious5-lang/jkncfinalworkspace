<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleMeetSpaceService
{
    public function __construct(
        private GoogleCalendarAccessToken $accessToken
    ) {
    }

    /**
     * Resolves the Calendar-created Meet by its meeting code and configures its
     * actual Meet space. Calendar conferenceData cannot set admission policy.
     */
    public function configureOpenAccess(string $conferenceUrl): void
    {
        $meetingCode = $this->meetingCode($conferenceUrl);
        $space = $this->getSpace($meetingCode);

        if (data_get($space, 'config.accessType') === 'OPEN') {
            return;
        }

        $name = trim((string) ($space['name'] ?? ''));

        if (! preg_match('#^spaces/[^/]+$#', $name)) {
            throw new RuntimeException('Google Meet returned an invalid meeting space.');
        }

        $response = $this->client()->patch(
            $this->spaceUrl($name).'?updateMask=config.accessType',
            [
                'name' => $name,
                'config' => ['accessType' => 'OPEN'],
            ]
        );

        if (! $response->successful()) {
            $this->logProviderFailure('patch', $response->status(), $meetingCode, $response->json());
            throw new RuntimeException($this->failureMessage($response->status(), $response->json()));
        }

        if ($response->json('config.accessType') !== 'OPEN') {
            Log::warning('Google Meet returned a space without OPEN access.', [
                'meeting_code' => $meetingCode,
                'space' => $name,
            ]);

            throw new RuntimeException('Google Meet did not confirm OPEN meeting access.');
        }
    }

    /** @return array<string, mixed> */
    private function getSpace(string $meetingCode): array
    {
        $response = $this->client()->get(
            $this->baseUrl().'/spaces/'.rawurlencode($meetingCode)
        );

        if (! $response->successful()) {
            $this->logProviderFailure('get', $response->status(), $meetingCode, $response->json());
            throw new RuntimeException($this->failureMessage($response->status(), $response->json()));
        }

        $space = $response->json();

        if (! is_array($space)) {
            throw new RuntimeException('Google Meet returned an invalid meeting space.');
        }

        return $space;
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->accessToken->get())
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.google_calendar.timeout', 15));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.google_calendar.meet_api_base', 'https://meet.googleapis.com/v2'), '/');
    }

    private function spaceUrl(string $name): string
    {
        return $this->baseUrl().'/'.implode('/', array_map(
            fn (string $segment): string => rawurlencode($segment),
            explode('/', $name)
        ));
    }

    private function meetingCode(string $conferenceUrl): string
    {
        $parts = parse_url($conferenceUrl);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $code = trim((string) ($parts['path'] ?? ''), '/');

        if (! in_array($host, ['meet.google.com', 'www.meet.google.com'], true)
            || ! preg_match('/^[a-z]+-[a-z]+-[a-z]+$/i', $code)) {
            throw new RuntimeException('Google Calendar returned an invalid Google Meet URL.');
        }

        return strtolower($code);
    }

    /** @param array<string, mixed>|null $providerError */
    private function logProviderFailure(string $operation, int $status, string $meetingCode, ?array $providerError): void
    {
        Log::warning('Google Meet access configuration request failed.', [
            'operation' => $operation,
            'status' => $status,
            'meeting_code' => $meetingCode,
            'provider_status' => data_get($providerError, 'error.status'),
        ]);
    }

    /** @param array<string, mixed>|null $providerError */
    private function failureMessage(int $status, ?array $providerError): string
    {
        $providerMessage = strtolower((string) data_get($providerError, 'error.message'));

        if ($status === 403 && str_contains($providerMessage, 'policy')) {
            return 'Google Workspace policy does not permit OPEN meeting access.';
        }

        if (in_array($status, [401, 403], true)) {
            return 'Google Meet OPEN access was not permitted by the Google authorization or Workspace policy.';
        }

        return 'Google Meet OPEN access could not be configured.';
    }
}
