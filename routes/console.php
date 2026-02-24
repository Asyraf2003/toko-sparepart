<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Application\UseCases\Notifications\Telegram\SendDailyProfitTelegramReportUseCase;
use App\Application\UseCases\Notifications\Telegram\SendPurchaseDueH5TelegramUseCase;
use App\Application\UseCases\Notifications\Telegram\SendPurchaseOverdueTelegramUseCase;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- SCHEDULE: Telegram Ops ---
// NOTE: scheduler timezone is pinned to Asia/Makassar to match business requirement.
Schedule::call(function (): void {
    app(SendDailyProfitTelegramReportUseCase::class)->handle();
})
    ->cron('0 18 * * 1-6') // 18:00 Mon-Sat
    ->timezone('Asia/Makassar')
    ->name('telegram_ops_profit_daily');

Schedule::call(function (): void {
    app(SendPurchaseDueH5TelegramUseCase::class)->handle();
})
    ->dailyAt((string) config('services.telegram_ops.purchase_due_reminder_time', '09:00'))
    ->timezone('Asia/Makassar')
    ->name('telegram_ops_purchase_due_h5');

Schedule::call(function (): void {
    app(SendPurchaseOverdueTelegramUseCase::class)->handle();
})
    ->dailyAt((string) config('services.telegram_ops.purchase_overdue_reminder_time', '09:05'))
    ->timezone('Asia/Makassar')
    ->name('telegram_ops_purchase_overdue');