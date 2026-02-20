<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:CASHIER'])->prefix('cashier')->group(function () {
    Route::get('/', fn () => 'CASHIER OK');
});
