<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCalendarAccessToken
{
    private ?string $accessToken = null;

    public function get(): string
    {
        if (filled($this->accessToken)) {
            return $this->accessToken;
        }

        $refreshToken = trim((string) config('services.google_calendar.refresh_token'));

        if ($refreshToken !== '') {
            return $this->accessToken = $this->refresh($refreshToken);
        }

        // Kept only for a safe transition from the original integration. New
        // production installs must use the durable refresh-token configuration.
        $legacyToken = trim((string) config('services.google_calendar.access_token'));

        if ($legacyToken !== '') {
            return $this->accessToken = $legacyToken;
        }

        throw new RuntimeException('Google Calendar credentials are not configured.');
    }

    private function refresh(string $refreshToken): string
    {
        $clientId = trim((string) config('services.google_calendar.client_id'));
        $clientSecret = trim((string) config('services.google_calendar.client_secret'));

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Google Calendar OAuth refresh credentials are incomplete.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout((int) config('services.google_calendar.timeout', 15))
            ->post(rtrim((string) config('services.google_calendar.oauth_base'), '/').'/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->successful() || blank($response->json('access_token'))) {
            throw new RuntimeException('Google Calendar authorization could not be refreshed.');
        }

        return (string) $response->json('access_token');
    }
}
