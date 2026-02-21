<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\UseCases\Catalog\CreateProductRequest;
use App\Application\UseCases\Catalog\CreateProductUseCase;
use App\Application\UseCases\Inventory\AdjustStockRequest;
use App\Application\UseCases\Inventory\AdjustStockUseCase;
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

        /** @var AdjustStockUseCase $adjustStock */
        $adjustStock = app(AdjustStockUseCase::class);

        $items = [
            ['sku' => 'OLI-10W40',  'name' => 'Oli 10W-40',    'price' => 45000,  'threshold' => 3, 'active' => true, 'stock' => 12],
            ['sku' => 'BUSI-NGK',   'name' => 'Busi NGK',      'price' => 18000,  'threshold' => 5, 'active' => true, 'stock' => 30],
            ['sku' => 'KAMPAS-R',   'name' => 'Kampas Rem',    'price' => 65000,  'threshold' => 3, 'active' => true, 'stock' => 8],
            ['sku' => 'RANTAI-428', 'name' => 'Rantai 428',    'price' => 125000, 'threshold' => 2, 'active' => true, 'stock' => 5],
            ['sku' => 'FILTER-U',   'name' => 'Filter Udara',  'price' => 22000,  'threshold' => 3, 'active' => true, 'stock' => 15],
        ];

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

            // Seed stock via UseCase so ledger tercatat
            $adjustStock->handle(new AdjustStockRequest(
                productId: (int) $productId,
                qtyDelta: (int) $delta,
                actorUserId: (int) $adminId,
                note: 'seed initial stock',
                refType: self::REF_TYPE,
                refId: null,
            ));
        }
    }
}
