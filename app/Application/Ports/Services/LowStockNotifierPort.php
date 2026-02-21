<?php

declare(strict_types=1);

namespace App\Application\Ports\Services;

use App\Application\DTO\Notifications\LowStockAlertMessage;

interface LowStockNotifierPort
{
    public function notifyLowStock(LowStockAlertMessage $msg): void;
}
