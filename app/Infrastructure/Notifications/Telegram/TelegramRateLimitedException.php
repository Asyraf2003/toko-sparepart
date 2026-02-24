<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Telegram;

final class TelegramRateLimitedException extends \RuntimeException
{
    public function __construct(
        private readonly int $retryAfterSeconds,
        string $message = 'Telegram rate limited',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function retryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
