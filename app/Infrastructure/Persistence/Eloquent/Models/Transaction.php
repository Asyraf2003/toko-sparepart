<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'transaction_number',
        'business_date',
        'status',
        'payment_status',
        'payment_method',
        'rounding_mode',
        'rounding_amount',
        'customer_name',
        'customer_phone',
        'vehicle_plate',
        'service_employee_id',
        'note',
        'opened_at',
        'completed_at',
        'voided_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'business_date' => 'date',
        'opened_at' => 'datetime',
        'completed_at' => 'datetime',
        'voided_at' => 'datetime',
    ];
}
