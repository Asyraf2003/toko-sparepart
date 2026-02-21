<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DevEnsureInventoryStocksSeeder extends Seeder
{
    public function run(): void
    {
        $productIds = DB::table('products')->orderBy('id')->pluck('id')->all();

        foreach ($productIds as $productId) {
            $exists = DB::table('inventory_stocks')->where('product_id', $productId)->exists();
            if ($exists) {
                continue;
            }

            DB::table('inventory_stocks')->insert([
                'product_id' => (int) $productId,
                'on_hand_qty' => 0,
                'reserved_qty' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
