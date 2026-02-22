<?php

declare(strict_types=1);

return [
    'cash_quick_pay' => (bool) env('CASHIER_CASH_QUICK_PAY', false),
    'cash_auto_fill_received' => (bool) env('CASHIER_CASH_AUTO_FILL_RECEIVED', false),
];
