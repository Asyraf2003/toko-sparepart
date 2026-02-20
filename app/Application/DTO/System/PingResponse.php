<?php

declare(strict_types=1);

namespace App\Application\DTO\System;

final readonly class PingResponse
{
    public function __construct(
        public string $nowIso,
        public string $businessDate,
        public string $timezone,
    ) {}

    /**
     * @return array{now:string,business_date:string,timezone:string}
     */
    public function toArray(): array
    {
        return [
            'now' => $this->nowIso,
            'business_date' => $this->businessDate,
            'timezone' => $this->timezone,
        ];
    }
}
