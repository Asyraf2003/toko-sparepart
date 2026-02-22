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
        // idempotency: jika ada invoice seed, skip
        if (DB::table('purchase_invoices')->where('no_faktur', 'like', 'FAK-SEED-%')->exists()) {
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

        // pakai banyak produk biar avg_cost & stok lebih “real”
        $products = DB::table('products')->orderBy('id')->get(['id', 'sku', 'sell_price_current']);
        if ($products->count() < 10) {
            throw new \RuntimeException('Need at least 10 products to seed purchases. Run DevSampleProductsSeeder (50 items) first.');
        }

        $supplierNames = [
            'Supplier Demo A',
            'Supplier Demo B',
            'Supplier Demo C',
            'Supplier Demo D',
        ];

        $salesNames = [
            'Sales A',
            'Sales B',
            'Sales C',
            'Sales D',
        ];

        $base = now();

        // 8 invoice, masing-masing 5 lines => 40 purchase lines (cukup untuk UI & report)
        $invoiceCount = 8;
        $linesPerInvoice = 5;

        // buat pool product id agar variasi lebih merata
        $pool = $products->pluck('id')->all();
        shuffle($pool);

        $poolIdx = 0;

        for ($i = 1; $i <= $invoiceCount; $i++) {
            $noFaktur = sprintf('FAK-SEED-%04d', $i);
            $tglKirim = $base->copy()->subDays(random_int(0, 10))->format('Y-m-d');

            $supplier = $supplierNames[array_rand($supplierNames)];
            $sales = $salesNames[array_rand($salesNames)];

            $lines = [];

            for ($j = 0; $j < $linesPerInvoice; $j++) {
                if ($poolIdx >= count($pool)) {
                    $poolIdx = 0;
                    shuffle($pool);
                }

                $productId = (int) $pool[$poolIdx++];
                $p = $products->firstWhere('id', $productId);

                if ($p === null) {
                    continue;
                }

                $sell = (int) ($p->sell_price_current ?? 0);
                if ($sell <= 0) {
                    $sell = 10000;
                }

                // cost kira-kira 55% - 85% dari harga jual
                $cost = (int) round($sell * (random_int(55, 85) / 100));
                // rapihin ke kelipatan 100 (biar realistis)
                $cost = (int) (round($cost / 100) * 100);
                if ($cost <= 0) {
                    $cost = 5000;
                }

                $qty = random_int(2, 30);

                // discount bps (0% / 2.5% / 5% / 10% / 12.75%)
                $discOptions = [0, 250, 500, 1000, 1275];
                $discBps = (int) $discOptions[array_rand($discOptions)];

                $lines[] = new CreatePurchaseInvoiceLine(
                    productId: $productId,
                    qty: $qty,
                    unitCost: $cost,
                    discBps: $discBps,
                );
            }

            if ($lines === []) {
                continue;
            }

            $uc->handle(new CreatePurchaseInvoiceRequest(
                actorUserId: (int) $adminId,
                supplierName: $supplier,
                noFaktur: $noFaktur,
                tglKirim: $tglKirim,
                kepada: 'ADMIN',
                noPesanan: sprintf('PO-SEED-%03d', $i),
                namaSales: $sales,
                totalPajak: random_int(0, 20000),
                note: 'seed demo purchase (m5)',
                lines: $lines,
            ));
        }
    }
}
