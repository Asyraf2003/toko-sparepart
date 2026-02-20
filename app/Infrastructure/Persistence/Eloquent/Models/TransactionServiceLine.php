<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class TransactionServiceLine extends Model
{
    protected $table = 'transaction_service_lines';

    protected $fillable = [
        'transaction_id',
        'description',
        'price_manual',
    ];
}
