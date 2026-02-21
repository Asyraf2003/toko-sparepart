<?php

declare(strict_types=1);

namespace App\Application\UseCases\Purchasing;

final readonly class CreatePurchaseInvoiceRequest
{
    /**
     * @param  list<CreatePurchaseInvoiceLine>  $lines
     */
    public function __construct(
        public int $actorUserId,
        public string $supplierName,
        public string $noFaktur,
        public string $tglKirim, // Y-m-d
        public ?string $kepada,
        public ?string $noPesanan,
        public ?string $namaSales,
        public int $totalPajak, // header-level tax (rupiah integer), allocated into cost basis
        public ?string $note,
        public array $lines,
    ) {}
}
