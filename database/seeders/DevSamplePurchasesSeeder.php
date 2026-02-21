<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceLine;
use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceRequest;
use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceUseCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DevSamplePurchasesSeeder extends Seeder
{
    public function run(): void
    {
        // idempotency: if seed invoice exists, skip
        if (DB::table('purchase_invoices')->where('no_faktur', 'FAK-SEED-0001')->exists()) {
            return;
        }

        $adminId = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orderBy('id')
            ->value('id');

        if ($adminId === null) {
            throw new \RuntimeException('Admin user not found. Ensure DefaultUsersSeeder runs first.');
        }

        /** @var CreatePurchaseInvoiceUseCase $uc */
        $uc = app(CreatePurchaseInvoiceUseCase::class);

        $products = DB::table('products')->orderBy('id')->limit(5)->get(['id', 'sku']);
        if ($products->count() < 2) {
            throw new \RuntimeException('Need at least 2 products to seed purchases.');
        }

        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        // Invoice 1: mixed discount + header tax
        $p1 = (int) $products[0]->id;
        $p2 = (int) $products[1]->id;

        $uc->handle(new CreatePurchaseInvoiceRequest(
            actorUserId: (int) $adminId,
            supplierName: 'Supplier Demo A',
            noFaktur: 'FAK-SEED-0001',
            tglKirim: $today,
            kepada: 'ADMIN',
            noPesanan: 'PO-SEED-001',
            namaSales: 'Sales A',
            totalPajak: 15000,
            note: 'seed demo purchase (m5)',
            lines: [
                // disc 5.00% => 500 bps
                new CreatePurchaseInvoiceLine(productId: $p1, qty: 10, unitCost: 30000, discBps: 500),
                new CreatePurchaseInvoiceLine(productId: $p2, qty: 5, unitCost: 12000, discBps: 0),
            ],
        ));

        // Invoice 2: decimal discount + different header tax
        $p3 = (int) ($products[2]->id ?? $p1);

        $uc->handle(new CreatePurchaseInvoiceRequest(
            actorUserId: (int) $adminId,
            supplierName: 'Supplier Demo B',
            noFaktur: 'FAK-SEED-0002',
            tglKirim: $yesterday,
            kepada: 'ADMIN',
            noPesanan: 'PO-SEED-002',
            namaSales: 'Sales B',
            totalPajak: 8000,
            note: 'seed demo purchase (m5)',
            lines: [
                // disc 2.50% => 250 bps
                new CreatePurchaseInvoiceLine(productId: $p2, qty: 7, unitCost: 11000, discBps: 250),
                // disc 12.75% => 1275 bps
                new CreatePurchaseInvoiceLine(productId: $p3, qty: 3, unitCost: 90000, discBps: 1275),
            ],
        ));
    }
}
