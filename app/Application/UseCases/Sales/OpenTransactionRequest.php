<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

final readonly class OpenTransactionRequest
{
    /**
     * @param  array<string, mixed>  $fields  Patch fields (customer_name, customer_phone, vehicle_plate, note)
     */
    public function __construct(
        public int $transactionId,
        public int $actorUserId,
        public array $fields = [],
    ) {}
}
