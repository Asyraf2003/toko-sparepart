<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use DateTimeImmutable;
use Tests\TestCase;

final class HexagonalPortsTest extends TestCase
{
    public function test_clock_port_resolves_and_returns_date_time_immutable(): void
    {
        $clock = $this->app->make(ClockPort::class);

        $now = $clock->now();
        $this->assertInstanceOf(DateTimeImmutable::class, $now);

        $today = $clock->todayBusinessDate();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $today);
    }

    public function test_transaction_manager_port_runs_callback(): void
    {
        $tm = $this->app->make(TransactionManagerPort::class);

        $out = $tm->run(fn () => 123);

        $this->assertSame(123, $out);
    }
}
