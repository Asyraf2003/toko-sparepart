<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockLedger extends Model
{
    protected $table = 'stock_ledgers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'type',
        'qty_delta',
        'ref_type',
        'ref_id',
        'actor_user_id',
        'occurred_at',
        'note',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'int',
            'qty_delta' => 'int',
            'ref_id' => 'int',
            'actor_user_id' => 'int',
            'occurred_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
