<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryStock extends Model
{
    protected $table = 'inventory_stocks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'on_hand_qty',
        'reserved_qty',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'int',
            'on_hand_qty' => 'int',
            'reserved_qty' => 'int',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function availableQty(): int
    {
        return (int) $this->on_hand_qty - (int) $this->reserved_qty;
    }
}
