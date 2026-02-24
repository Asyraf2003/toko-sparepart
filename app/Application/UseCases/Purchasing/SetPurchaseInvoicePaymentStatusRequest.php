<?php

declare(strict_types=1);

namespace App\Application\UseCases\Purchasing;

final readonly class SetPurchaseInvoicePaymentStatusRequest
{
    public function __construct(
        public int $actorUserId,
        public int $purchaseInvoiceId,
        public string $paymentStatus, // 'PAID'|'UNPAID'
        public ?string $paidNote,
        public string $reason,
    ) {}
}