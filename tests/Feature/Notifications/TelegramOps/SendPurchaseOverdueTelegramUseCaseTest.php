<?php

use App\Application\Ports\Services\ClockPort;
use App\Application\UseCases\Notifications\Telegram\SendPurchaseOverdueTelegramUseCase;
use App\Infrastructure\Notifications\Telegram\SendTelegramMessageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches overdue digest job when there are overdue unpaid invoices', function () {
    config()->set('services.telegram_ops.enabled', true);
    config()->set('services.telegram_ops.purchase_overdue_enabled', true);
    config()->set('services.telegram_ops.chat_ids', '111');

    test()->mock(ClockPort::class, function (Mockery\MockInterface $m): void {
        $m->shouldReceive('now')->andReturn(new DateTimeImmutable('2026-02-20 10:00:00'));
    });

    DB::table('purchase_invoices')->insert([
        'supplier_name' => 'Supplier B',
        'no_faktur' => 'FAK-OVERDUE',
        'tgl_kirim' => '2026-01-10',
        'due_date' => '2026-02-10',
        'payment_status' => 'UNPAID',
        'grand_total' => 200000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Queue::fake();

    app(SendPurchaseOverdueTelegramUseCase::class)->handle();

    Queue::assertPushed(SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '111'
            && $job->dedupKey === 'purchase_overdue_digest:2026-02-20:111'
            && str_contains($job->text, 'OVERDUE PEMBELIAN');
    });
});
