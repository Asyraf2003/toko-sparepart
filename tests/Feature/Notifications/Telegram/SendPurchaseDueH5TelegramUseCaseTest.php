<?php

use App\Application\Ports\Services\ClockPort;
use App\Application\UseCases\Notifications\Telegram\SendPurchaseDueH5TelegramUseCase;
use App\Infrastructure\Notifications\Telegram\SendTelegramMessageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches due H-5 digest job when there are matching unpaid invoices', function () {
    config()->set('services.telegram_ops.enabled', true);
    config()->set('services.telegram_ops.purchase_due_enabled', true);
    config()->set('services.telegram_ops.chat_ids', '111');

    // Fake clock: today = 2026-02-20, target due = 2026-02-25
    app()->singleton(ClockPort::class, function () {
        return new class implements ClockPort
        {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-02-20 10:00:00');
            }

            public function todayBusinessDate(): string
            {
                return '2026-02-20';
            }
        };
    });

    // One unpaid invoice due in 5 days
    DB::table('purchase_invoices')->insert([
        'supplier_name' => 'Supplier A',
        'no_faktur' => 'FAK-001',
        'tgl_kirim' => '2026-01-25',
        'due_date' => '2026-02-25',
        'payment_status' => 'UNPAID',
        'total_bruto' => 0,
        'total_diskon' => 0,
        'total_pajak' => 0,
        'grand_total' => 15000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Queue::fake();

    app(SendPurchaseDueH5TelegramUseCase::class)->handle();

    Queue::assertPushed(SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '111'
            && str_contains($job->dedupKey, 'purchase_due_digest:2026-02-20:111');
    });
});

it('does not dispatch twice in same day for same chat (dedup)', function () {
    config()->set('services.telegram_ops.enabled', true);
    config()->set('services.telegram_ops.purchase_due_enabled', true);
    config()->set('services.telegram_ops.chat_ids', '111');

    app()->singleton(ClockPort::class, function () {
        return new class implements ClockPort
        {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-02-20 10:00:00');
            }

            public function todayBusinessDate(): string
            {
                return '2026-02-20';
            }
        };
    });

    DB::table('purchase_invoices')->insert([
        'supplier_name' => 'Supplier A',
        'no_faktur' => 'FAK-001',
        'tgl_kirim' => '2026-01-25',
        'due_date' => '2026-02-25',
        'payment_status' => 'UNPAID',
        'total_bruto' => 0,
        'total_diskon' => 0,
        'total_pajak' => 0,
        'grand_total' => 15000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Pre-insert dedup key
    DB::table('notification_states')->insert([
        'key' => 'purchase_due_digest:2026-02-20:111',
        'sent_at' => now(),
        'meta_json' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Queue::fake();

    app(SendPurchaseDueH5TelegramUseCase::class)->handle();

    Queue::assertNothingPushed();
});
