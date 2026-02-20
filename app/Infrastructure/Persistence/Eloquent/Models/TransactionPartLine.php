<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class TransactionPartLine extends Model
{
    protected $table = 'transaction_part_lines';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'qty',
        'unit_sell_price_frozen',
        'line_subtotal',
        'unit_cogs_frozen',
    ];
}
