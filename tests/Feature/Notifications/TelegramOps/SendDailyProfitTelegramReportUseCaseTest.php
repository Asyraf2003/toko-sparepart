<?php

use App\Application\Ports\Services\ClockPort;
use App\Application\UseCases\Notifications\Telegram\SendDailyProfitTelegramReportUseCase;
use App\Infrastructure\Notifications\Telegram\SendTelegramMessageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('dispatches profit daily job when enabled (even if no sales data)', function () {
    config()->set('services.telegram_ops.enabled', true);
    config()->set('services.telegram_ops.profit_enabled', true);
    config()->set('services.telegram_ops.chat_ids', '111');

    test()->mock(ClockPort::class, function (MockInterface $m): void {
        $m->shouldReceive('now')->andReturn(new DateTimeImmutable('2026-02-20 18:00:00'));
    });

    Queue::fake();

    app(SendDailyProfitTelegramReportUseCase::class)->handle();

    Queue::assertPushed(SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '111'
            && $job->dedupKey === 'profit_daily:2026-02-20:111'
            && str_contains($job->text, 'PROFIT HARIAN');
    });
});
