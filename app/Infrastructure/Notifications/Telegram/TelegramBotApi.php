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
                Log::warning('telegram_bot_send_menu_failed', [
                    'status' => $resp->status(),
                    'chat_id' => $chatId,
                ]);
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
            Log::warning('telegram_bot_get_file_failed', [
                'status' => $resp->status(),
                'file_id' => $fileId,
            ]);

            return '';
        }

        $filePath = (string) data_get($resp->json(), 'result.file_path', '');

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
            Log::warning('telegram_bot_download_failed', [
                'status' => $resp->status(),
                'file_path' => $filePath,
            ]);

            return '';
        }

        return (string) $resp->body();
    }
}
