<?php

use App\Interfaces\Web\Controllers\Telegram\TelegramWebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->withoutMiddleware([VerifyCsrfToken::class]);