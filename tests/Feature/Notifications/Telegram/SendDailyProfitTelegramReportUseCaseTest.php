<?php

use App\Application\Ports\Services\ClockPort;
use App\Application\UseCases\Notifications\Telegram\SendDailyProfitTelegramReportUseCase;
use App\Infrastructure\Notifications\Telegram\SendTelegramMessageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches profit daily job when enabled (even if no sales data)', function () {
    config()->set('services.telegram_ops.enabled', true);
    config()->set('services.telegram_ops.profit_enabled', true);
    config()->set('services.telegram_ops.chat_ids', '111');

    app()->singleton(ClockPort::class, function () {
        return new class implements ClockPort
        {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-02-20 18:00:00');
            }

            public function todayBusinessDate(): string
            {
                return '2026-02-20';
            }
        };
    });

    Queue::fake();

    app(SendDailyProfitTelegramReportUseCase::class)->handle();

    Queue::assertPushed(SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '111'
            && $job->dedupKey === 'profit_daily:2026-02-20:111';
    });

    // optional: ensure dedup not already inserted before job runs
    expect(DB::table('notification_states')->where('key', 'profit_daily:2026-02-20:111')->count())->toBe(0);
});
