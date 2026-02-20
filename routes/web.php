<?php

use App\Interfaces\Web\Controllers\Auth\LoginController;
use App\Interfaces\Web\Controllers\System\PingController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/__hex/ping', PingController::class);

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth');

Route::get('/', function () {
    $user = request()->user();

    if ($user === null) {
        return redirect('/login');
    }

    return $user->role === User::ROLE_ADMIN
        ? redirect('/admin')
        : redirect('/cashier');
});

require __DIR__.'/admin.php';
require __DIR__.'/cashier.php';
