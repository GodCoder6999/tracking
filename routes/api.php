<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DealerController;
use Illuminate\Support\Facades\Route;

// public — device check + login
Route::post('/device/check', [AuthController::class, 'checkDevice']);
Route::post('/auth/login',   [AuthController::class, 'login']);

// protected — require valid Bearer token + approved device
Route::middleware(['api.token', 'device.approved'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('dealer')->group(function () {
        Route::get('/dashboard',  [DealerController::class, 'dashboard']);
        Route::get('/products',   [DealerController::class, 'products']);
        Route::get('/analytics',  [DealerController::class, 'analytics']);

        Route::get('/clients',              [DealerController::class, 'clients']);
        Route::post('/clients',             [DealerController::class, 'clientStore']);
        Route::put('/clients/{client}',     [DealerController::class, 'clientUpdate']);
        Route::delete('/clients/{client}',  [DealerController::class, 'clientDestroy']);

        Route::get('/orders',                             [DealerController::class, 'orders']);
        Route::post('/orders',                            [DealerController::class, 'orderStore']);
        Route::get('/orders/{order}',                     [DealerController::class, 'orderShow']);
        Route::patch('/orders/{order}/label',             [DealerController::class, 'orderUpdateLabel']);
        Route::post('/orders/{order}/payments',           [DealerController::class, 'paymentStore']);
        Route::delete('/orders/{order}/payments/{paymentId}', [DealerController::class, 'paymentDestroy']);
        Route::post('/orders/{order}/dispatches',         [DealerController::class, 'dispatchStore']);
        Route::post('/orders/{order}/dispatches/delivered', [DealerController::class, 'dispatchMarkDelivered']);
    });
});
