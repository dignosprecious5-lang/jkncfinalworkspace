<?php

namespace App\Jobs;

use App\Services\Messaging\MessengerProvider;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnrichMessengerProfile implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 30;
    public int $uniqueFor = 300;

    public function __construct(public int $peerId)
    {
        $this->onQueue(config('messaging.messenger.queue', 'messaging'));
        $this->onConnection(config('messaging.messenger.queue_connection', 'database'));
    }

    public function uniqueId(): string
    {
        return (string) $this->peerId;
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(MessengerProvider $provider): void
    {
        if (! config('messaging.messenger.enabled') || blank(config('messaging.messenger.page_access_token'))) {
            return;
        }
        try {
            if ($provider->enrichProfile($this->peerId)) {
                return;
            }
        } catch (\Throwable) {
            // Never persist HTTP headers, credentials, response bodies or SQL bindings.
        }
        throw new \RuntimeException('Messenger profile enrichment unavailable; canonical messages are unaffected.');
    }
}
