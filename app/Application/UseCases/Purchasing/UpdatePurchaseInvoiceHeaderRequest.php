<?php

declare(strict_types=1);

namespace App\Application\UseCases\Purchasing;

final readonly class UpdatePurchaseInvoiceHeaderRequest
{
    public function __construct(
        public int $actorUserId,
        public int $purchaseInvoiceId,

        public string $supplierName,
        public string $noFaktur,
        public string $tglKirim, // Y-m-d

        public ?string $kepada,
        public ?string $noPesanan,
        public ?string $namaSales,
        public ?string $note,

        public string $reason, // mandatory for audit
    ) {}
}
