<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Telegram;

use App\Application\DTO\Notifications\LowStockAlertMessage;
use App\Application\Ports\Services\LowStockNotifierPort;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class TelegramLowStockNotifier implements LowStockNotifierPort
{
    public function notifyLowStock(LowStockAlertMessage $msg): void
    {
        $enabled = (bool) config('services.telegram_low_stock.enabled', false);
        if (! $enabled) {
            return;
        }

        $token = (string) config('services.telegram_low_stock.bot_token', '');
        if (trim($token) === '') {
            return;
        }

        $chatIdsRaw = (string) config('services.telegram_low_stock.chat_ids', '');
        $chatIds = $this->parseChatIds($chatIdsRaw);
        if (count($chatIds) === 0) {
            return;
        }

        $text = $this->buildText($msg);
        $url = 'https://api.telegram.org/bot'.$token.'/sendMessage';

        foreach ($chatIds as $chatId) {
            try {
                $resp = Http::timeout(5)->asForm()->post($url, [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]);

                if (! $resp->successful()) {
                    Log::warning('telegram_low_stock_send_failed', [
                        'status' => $resp->status(),
                        'product_id' => $msg->productId,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('telegram_low_stock_send_exception', [
                    'product_id' => $msg->productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function buildText(LowStockAlertMessage $msg): string
    {
        $ts = $msg->occurredAt->format('Y-m-d H:i:s');

        return implode("\n", [
            '⚠️ LOW STOCK',
            $msg->sku.' — '.$msg->name,
            'Available: '.$msg->availableQty,
            'Threshold: '.$msg->threshold,
            'Trigger: '.$msg->triggerType,
            'Time: '.$ts,
        ]);
    }

    /**
     * @return list<string>
     */
    private function parseChatIds(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $raw));
        $out = [];
        foreach ($parts as $p) {
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return array_values(array_unique($out));
    }
}
