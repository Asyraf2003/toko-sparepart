<?php

use App\Interfaces\Web\Controllers\Admin\TelegramBotAdminController;
use App\Interfaces\Web\Controllers\Admin\TelegramPaymentProofController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:'.User::ROLE_ADMIN])
    ->prefix('/admin')
    ->group(function (): void {
        Route::get('/telegram', [TelegramBotAdminController::class, 'index']);
        Route::post('/telegram/pairing-token', [TelegramBotAdminController::class, 'createPairingToken']);

        Route::get('/telegram/payment-proofs', [TelegramPaymentProofController::class, 'index']);
        Route::get('/telegram/payment-proofs/{id}', [TelegramPaymentProofController::class, 'show']);
        Route::get('/telegram/payment-proofs/{id}/download', [TelegramPaymentProofController::class, 'download']);

        Route::post('/telegram/payment-proofs/{id}/approve', [TelegramPaymentProofController::class, 'approve']);
        Route::post('/telegram/payment-proofs/{id}/reject', [TelegramPaymentProofController::class, 'reject']);
    });
