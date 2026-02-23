<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\UseCases\Catalog\CreateProductRequest;
use App\Application\UseCases\Catalog\CreateProductUseCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DevSampleProductsSeeder extends Seeder
{
    private const REF_TYPE = 'seed:DevSampleProductsSeeder';

    public function run(): void
    {
        $adminId = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orderBy('id')
            ->value('id');

        if ($adminId === null) {
            throw new \RuntimeException('Admin user not found. Ensure DefaultUsersSeeder runs first.');
        }

        /** @var CreateProductUseCase $createProduct */
        $createProduct = app(CreateProductUseCase::class);

        // SKU "wajib" (dipakai UI/tests)
        $items = [
            ['sku' => 'SP-ABC',     'name' => 'Sparepart ABC', 'price' => 10000,  'threshold' => 3, 'active' => true, 'stock' => 20],
            ['sku' => 'OLI-10W40',  'name' => 'Oli 10W-40',    'price' => 45000,  'threshold' => 3, 'active' => true, 'stock' => 12],
            ['sku' => 'BUSI-NGK',   'name' => 'Busi NGK',      'price' => 18000,  'threshold' => 5, 'active' => true, 'stock' => 30],
            ['sku' => 'KAMPAS-R',   'name' => 'Kampas Rem',    'price' => 65000,  'threshold' => 3, 'active' => true, 'stock' => 8],
            ['sku' => 'RANTAI-428', 'name' => 'Rantai 428',    'price' => 125000, 'threshold' => 2, 'active' => true, 'stock' => 5],
            ['sku' => 'FILTER-U',   'name' => 'Filter Udara',  'price' => 22000,  'threshold' => 3, 'active' => true, 'stock' => 15],
        ];

        // Tambahin sampai total 50 product (acak tapi stabil sku)
        $targetCount = 50;
        $existingSkus = [];
        foreach ($items as $it) {
            $existingSkus[(string) $it['sku']] = true;
        }

        $types = [
            ['prefix' => 'SP',  'label' => 'Sparepart', 'min' => 8000,  'max' => 80000],
            ['prefix' => 'OLI', 'label' => 'Oli',       'min' => 35000, 'max' => 90000],
            ['prefix' => 'FLT', 'label' => 'Filter',    'min' => 12000, 'max' => 60000],
            ['prefix' => 'BAN', 'label' => 'Ban',       'min' => 150000, 'max' => 450000],
            ['prefix' => 'ELK', 'label' => 'Elektrik',  'min' => 12000, 'max' => 150000],
        ];

        $words = [
            'Standard', 'Racing', 'Premium', 'OEM', 'Heavy Duty',
            'Matic', 'Bebek', 'Sport', 'Universal', 'Original',
        ];

        $seq = 1;
        while (count($items) < $targetCount) {
            $t = $types[array_rand($types)];
            $sku = sprintf('%s-%04d', (string) $t['prefix'], $seq++);
            if (isset($existingSkus[$sku])) {
                continue;
            }
            $existingSkus[$sku] = true;

            $w1 = $words[array_rand($words)];
            $w2 = $words[array_rand($words)];
            $name = trim((string) $t['label'].' '.$w1.' '.$w2);

            $price = random_int((int) $t['min'], (int) $t['max']);
            // rapihin harga biar "kelihatan toko" (kelipatan 1000)
            $price = (int) (round($price / 1000) * 1000);
            if ($price <= 0) {
                $price = 10000;
            }

            $threshold = random_int(2, 8);
            $stock = random_int(0, 40);
            $active = true;

            $items[] = [
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'threshold' => $threshold,
                'active' => $active,
                'stock' => $stock,
            ];
        }

        foreach ($items as $it) {
            $sku = (string) $it['sku'];

            $productId = DB::table('products')->where('sku', $sku)->value('id');

            if ($productId === null) {
                // Create product + auto create inventory_stock row (per contract)
                $createProduct->handle(new CreateProductRequest(
                    sku: $sku,
                    name: (string) $it['name'],
                    sellPriceCurrent: (int) $it['price'],
                    minStockThreshold: (int) $it['threshold'],
                    isActive: (bool) $it['active'],
                ));

                $productId = DB::table('products')->where('sku', $sku)->value('id');
                if ($productId === null) {
                    throw new \RuntimeException("Product not found after create: {$sku}");
                }

                // Set avg_cost sekali jika masih 0/null (biar report profit tidak nol semua).
                $avgCost = DB::table('products')->where('id', (int) $productId)->value('avg_cost');
                $avgCostInt = (int) ($avgCost ?? 0);
                if ($avgCostInt <= 0) {
                    $sell = (int) $it['price'];
                    $cost = (int) max(0, (int) round($sell * (random_int(60, 85) / 100)));
                    DB::table('products')->where('id', (int) $productId)->update([
                        'avg_cost' => $cost,
                        'updated_at' => now(),
                    ]);
                }
            } else {
                // Idempotent update (do NOT overwrite avg_cost: it's moving average output)
                DB::table('products')->where('id', (int) $productId)->update([
                    'name' => (string) $it['name'],
                    'sell_price_current' => (int) $it['price'],
                    'min_stock_threshold' => (int) $it['threshold'],
                    'is_active' => (bool) $it['active'],
                    'updated_at' => now(),
                ]);
            }

            // Ensure inventory row exists without overwriting quantities
            $invExists = DB::table('inventory_stocks')->where('product_id', (int) $productId)->exists();
            if (! $invExists) {
                DB::table('inventory_stocks')->insert([
                    'product_id' => (int) $productId,
                    'on_hand_qty' => 0,
                    'reserved_qty' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Seed initial stock ONCE per product (avoid repeated ADJUSTMENT when seeding multiple times)
            $alreadyAdjusted = DB::table('stock_ledgers')
                ->where('product_id', (int) $productId)
                ->where('type', 'ADJUSTMENT')
                ->where('ref_type', self::REF_TYPE)
                ->exists();

            if ($alreadyAdjusted) {
                continue;
            }

            $inv = DB::table('inventory_stocks')->where('product_id', (int) $productId)->first(['on_hand_qty']);
            $currentOnHand = (int) ($inv->on_hand_qty ?? 0);
            $targetOnHand = (int) $it['stock'];

            $delta = $targetOnHand - $currentOnHand;
            if ($delta === 0) {
                continue;
            }
        }
    }
}
