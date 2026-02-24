<?php

use App\Infrastructure\Notifications\Telegram\SendTelegramMenuJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('telegram webhook rejects invalid secret token', function () {
    config()->set('services.telegram_ops.enabled', true);
    config()->set('services.telegram_ops.webhook_secret', 'expected-secret');

    Queue::fake();

    $resp = $this->postJson('/telegram/webhook', [
        'message' => [
            'chat' => ['id' => '123'],
            'text' => '/menu',
        ],
    ], [
        'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
    ]);

    $resp->assertStatus(403);

    Queue::assertNothingPushed();
});

test('telegram /menu enqueues SendTelegramMenuJob when chat is linked', function () {
    config()->set('services.telegram_ops.enabled', true);
    config()->set('services.telegram_ops.webhook_secret', 'expected-secret');

    $admin = User::query()->create([
        'name' => 'Admin',
        'email' => 'admin-test@local.test',
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('12345678'),
    ]);

    DB::table('telegram_links')->insert([
        'user_id' => (int) $admin->id,
        'chat_id' => '123',
        'linked_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Queue::fake();

    $resp = $this->postJson('/telegram/webhook', [
        'message' => [
            'chat' => ['id' => '123'],
            'text' => '/menu',
        ],
    ], [
        'X-Telegram-Bot-Api-Secret-Token' => 'expected-secret',
    ]);

    $resp->assertOk()->assertJson(['ok' => true]);

    Queue::assertPushedOn('notifications', SendTelegramMenuJob::class, function (SendTelegramMenuJob $job) {
        return $job->chatId === '123';
    });
});
