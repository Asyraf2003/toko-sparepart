<?php

declare(strict_types=1);

namespace App\Application\UseCases\System;

use App\Application\DTO\System\PingResponse;
use App\Application\Ports\Services\ClockPort;

final readonly class PingUseCase
{
    public function __construct(
        private ClockPort $clock,
    ) {}

    public function handle(): PingResponse
    {
        $now = $this->clock->now();

        return new PingResponse(
            nowIso: $now->format(DATE_ATOM),
            businessDate: $this->clock->todayBusinessDate(),
            timezone: $now->getTimezone()->getName(),
        );
    }
}
