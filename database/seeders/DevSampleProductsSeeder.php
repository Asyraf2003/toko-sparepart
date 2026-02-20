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
            ['sku' => 'OLI-10W40', 'name' => 'Oli 10W-40', 'price' => 45000, 'threshold' => 3, 'active' => true, 'stock' => 12],
            ['sku' => 'BUSI-NGK',  'name' => 'Busi NGK',   'price' => 18000, 'threshold' => 5, 'active' => true, 'stock' => 30],
            ['sku' => 'KAMPAS-R',  'name' => 'Kampas Rem', 'price' => 65000, 'threshold' => 3, 'active' => true, 'stock' => 8],
            ['sku' => 'RANTAI-428','name' => 'Rantai 428', 'price' => 125000,'threshold' => 2, 'active' => true, 'stock' => 5],
            ['sku' => 'FILTER-U',  'name' => 'Filter Udara','price' => 22000,'threshold' => 3, 'active' => true, 'stock' => 15],
        ];

        foreach ($items as $it) {
            // Create product + auto create inventory_stock row
            $createProduct->handle(new CreateProductRequest(
                sku: $it['sku'],
                name: $it['name'],
                sellPriceCurrent: (int) $it['price'],
                minStockThreshold: (int) $it['threshold'],
                isActive: (bool) $it['active'],
            ));

            $productId = DB::table('products')->where('sku', $it['sku'])->value('id');
            if ($productId === null) {
                throw new \RuntimeException("Product not found after create: {$it['sku']}");
            }

            // Seed stock via UseCase so ledger tercatat
            $adjustStock->handle(new AdjustStockRequest(
                productId: (int) $productId,
                qtyDelta: (int) $it['stock'],
                actorUserId: (int) $adminId,
                note: 'seed initial stock',
                refType: 'seed',
                refId: 'DevSampleProductsSeeder',
            ));
        }
    }
}