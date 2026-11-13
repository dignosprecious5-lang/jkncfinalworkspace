<?php

namespace App\Services\TransmittalV2;

interface TransmittalDispatchProvider
{
    public function submit(string $dispatchUuid, array $payload): TransmittalDispatchResult;
}
