<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Telegram;

use App\Application\Ports\Services\TelegramSenderPort;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class TelegramOpsSender implements TelegramSenderPort
{
    public function sendMessage(string $chatId, string $text): void
    {
        $enabled = (bool) config('services.telegram_ops.enabled', false);
        if (! $enabled) {
            return;
        }

        $token = (string) config('services.telegram_ops.bot_token', '');
        if (trim($token) === '') {
            return;
        }

        $url = 'https://api.telegram.org/bot'.$token.'/sendMessage';

        try {
            $resp = Http::timeout(10)->asForm()->post($url, [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ]);

            if (! $resp->successful()) {
                $status = $resp->status();

                Log::warning('telegram_ops_send_failed', [
                    'status' => $status,
                    'chat_id' => $chatId,
                ]);

                if ($status === 429) {
                    $retryAfter = (int) data_get($resp->json(), 'parameters.retry_after', 1);
                    if ($retryAfter < 1) {
                        $retryAfter = 1;
                    }

                    throw new TelegramRateLimitedException($retryAfter, 'telegram_ops_rate_limited');
                }

                throw new \RuntimeException('telegram_ops_send_failed: status='.$status);
            }
        } catch (\Throwable $e) {
            Log::warning('telegram_ops_send_exception', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            throw $e; // allow retry when used from queue job
        }
    }
}