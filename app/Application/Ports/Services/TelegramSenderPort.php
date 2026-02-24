<?php

declare(strict_types=1);

namespace App\Application\Ports\Services;

interface TelegramSenderPort
{
    public function sendMessage(string $chatId, string $text): void;
}
