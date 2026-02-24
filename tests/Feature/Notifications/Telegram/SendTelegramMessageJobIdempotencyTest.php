<?php

use App\Application\Ports\Services\TelegramSenderPort;
use App\Infrastructure\Notifications\Telegram\SendTelegramMessageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('sends telegram message only once per dedup key', function () {
    $fake = new class implements TelegramSenderPort
    {
        public int $calls = 0;

        public function sendMessage(string $chatId, string $text): void
        {
            $this->calls++;
        }
    };

    $dedupKey = 'test_dedup_key_1';
    $job = new SendTelegramMessageJob(
        chatId: '123',
        text: 'hello',
        dedupKey: $dedupKey,
        metaJson: null,
    );

    // First run should send and insert notification_states row.
    $job->handle($fake);

    expect($fake->calls)->toBe(1);
    expect(DB::table('notification_states')->where('key', $dedupKey)->count())->toBe(1);

    // Second run should be no-op (idempotent).
    $job->handle($fake);

    expect($fake->calls)->toBe(1);
    expect(DB::table('notification_states')->where('key', $dedupKey)->count())->toBe(1);
});
