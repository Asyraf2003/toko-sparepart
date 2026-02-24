<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Telegram;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendTelegramMenuJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, array<int, array{text:string,callback_data:string}>>  $inlineKeyboard
     */
    public function __construct(
        public readonly string $chatId,
        public readonly string $text,
        public readonly array $inlineKeyboard,
    ) {}

    public function handle(): void
    {
        $enabled = (bool) config('services.telegram_ops.enabled', false);
        if (! $enabled) {
            return;
        }

        $token = (string) config('services.telegram_ops.bot_token', '');
        if (trim($token) === '') {
            return;
        }

        try {
            TelegramBotApi::sendMessageWithInlineKeyboard(
                botToken: $token,
                chatId: $this->chatId,
                text: $this->text,
                inlineKeyboard: $this->inlineKeyboard,
            );
        } catch (TelegramRateLimitedException $e) {
            // Respect Telegram retry_after to avoid silent drops.
            $this->release($e->retryAfterSeconds());

            return;
        }
    }
}