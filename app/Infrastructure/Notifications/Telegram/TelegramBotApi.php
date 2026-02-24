<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TelegramBotApi
{
    public static function sendMessageWithInlineKeyboard(
        string $botToken,
        string $chatId,
        string $text,
        array $inlineKeyboard,
    ): void {
        $botToken = trim($botToken);
        if ($botToken === '') {
            return;
        }

        $url = 'https://api.telegram.org/bot'.$botToken.'/sendMessage';

        try {
            $resp = Http::timeout(10)->asJson()->post($url, [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
                'reply_markup' => [
                    'inline_keyboard' => $inlineKeyboard,
                ],
            ]);

            if (! $resp->successful()) {
                $status = $resp->status();

                Log::warning('telegram_bot_send_menu_failed', [
                    'status' => $status,
                    'chat_id' => $chatId,
                ]);

                if ($status === 429) {
                    $retryAfter = (int) data_get($resp->json(), 'parameters.retry_after', 1);
                    if ($retryAfter < 1) {
                        $retryAfter = 1;
                    }

                    throw new TelegramRateLimitedException($retryAfter, 'telegram_bot_rate_limited');
                }

                throw new \RuntimeException('telegram_bot_send_menu_failed: status='.$status);
            }
        } catch (\Throwable $e) {
            Log::warning('telegram_bot_send_menu_exception', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public static function getFilePath(string $botToken, string $fileId): string
    {
        $botToken = trim($botToken);
        if ($botToken === '') {
            return '';
        }

        $url = 'https://api.telegram.org/bot'.$botToken.'/getFile';

        $resp = Http::timeout(10)->get($url, ['file_id' => $fileId]);
        if (! $resp->successful()) {
            $status = $resp->status();

            Log::warning('telegram_bot_get_file_failed', [
                'status' => $status,
                'file_id' => $fileId,
            ]);

            if ($status === 429) {
                $retryAfter = (int) data_get($resp->json(), 'parameters.retry_after', 1);
                if ($retryAfter < 1) {
                    $retryAfter = 1;
                }

                throw new TelegramRateLimitedException($retryAfter, 'telegram_bot_get_file_rate_limited');
            }

            throw new \RuntimeException('telegram_bot_get_file_failed: status='.$status);
        }

        $filePath = (string) data_get($resp->json(), 'result.file_path', '');
        if (trim($filePath) === '') {
            throw new \RuntimeException('telegram_bot_get_file_failed: empty file_path');
        }

        return $filePath;
    }

    public static function downloadFile(string $botToken, string $filePath): string
    {
        $botToken = trim($botToken);
        if ($botToken === '' || trim($filePath) === '') {
            return '';
        }

        $url = 'https://api.telegram.org/file/bot'.$botToken.'/'.$filePath;

        $resp = Http::timeout(25)->get($url);
        if (! $resp->successful()) {
            $status = $resp->status();

            Log::warning('telegram_bot_download_failed', [
                'status' => $status,
                'file_path' => $filePath,
            ]);

            // Telegram file download usually doesn't return JSON,
            // but we still attempt to parse retry_after when possible.
            if ($status === 429) {
                $retryAfter = 1;
                try {
                    $json = $resp->json();
                    $retryAfter = (int) data_get($json, 'parameters.retry_after', 1);
                } catch (\Throwable) {
                    // ignore JSON parse failures
                }

                if ($retryAfter < 1) {
                    $retryAfter = 1;
                }

                throw new TelegramRateLimitedException($retryAfter, 'telegram_bot_download_rate_limited');
            }

            throw new \RuntimeException('telegram_bot_download_failed: status='.$status);
        }

        $body = (string) $resp->body();
        if ($body === '') {
            throw new \RuntimeException('telegram_bot_download_failed: empty body');
        }

        return $body;
    }
}