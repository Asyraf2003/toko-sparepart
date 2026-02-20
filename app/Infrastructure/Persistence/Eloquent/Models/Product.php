<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Product extends Model
{
    protected $table = 'products';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'name',
        'sell_price_current',
        'min_stock_threshold',
        'is_active',
        'avg_cost',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'sell_price_current' => 'int',
            'min_stock_threshold' => 'int',
            'is_active' => 'bool',
            'avg_cost' => 'int',
        ];
    }

    public function stock(): HasOne
    {
        return $this->hasOne(InventoryStock::class, 'product_id');
    }
}
