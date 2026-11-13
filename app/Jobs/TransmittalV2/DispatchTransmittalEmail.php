<?php

namespace App\Jobs\TransmittalV2;

use App\Services\TransmittalV2\TransmittalV2DispatchWorker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Inactive by default; one queue attempt prevents Laravel from replaying uncertain submits. */
final class DispatchTransmittalEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 90;

    public function __construct(public readonly int $dispatchId) {}

    public function handle(TransmittalV2DispatchWorker $worker): void
    {
        $worker->handle($this->dispatchId);
    }
}
