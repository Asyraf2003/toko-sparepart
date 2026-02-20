<?php

declare(strict_types=1);

namespace App\Infrastructure\Clock;

use App\Application\Ports\Services\ClockPort;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

final class SystemClock implements ClockPort
{
    public function now(): DateTimeImmutable
    {
        $tz = (string) config('app.timezone', 'UTC');

        return CarbonImmutable::now($tz);
    }

    public function todayBusinessDate(): string
    {
        return $this->now()->format('Y-m-d');
    }
}
