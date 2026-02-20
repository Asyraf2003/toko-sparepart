<?php

declare(strict_types=1);

namespace App\Application\Ports\Services;

use DateTimeImmutable;

interface ClockPort
{
    public function now(): DateTimeImmutable;

    /**
     * Business date in Asia/Makassar (format: YYYY-MM-DD).
     */
    public function todayBusinessDate(): string;
}
