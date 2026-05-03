<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Client\DashboardController as ClientDashboard;
use App\Http\Controllers\Client\OrderController as ClientOrderController;
use App\Http\Controllers\Owner\AnalyticsController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboard;
use App\Http\Controllers\Owner\DealerController;
use App\Http\Controllers\Owner\DeviceController;
use App\Http\Controllers\Owner\LedgerController as OwnerLedgerController;
use App\Http\Controllers\Owner\OrderController as OwnerOrderController;
use App\Http\Controllers\Owner\ProductController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');


Route::middleware('guest:client')->group(function () {
    Route::get('/client/login',  [LoginController::class, 'showClient'])->name('login.client');
    Route::post('/client/login', [LoginController::class, 'client']);
});

$slug = env('OWNER_GATE_SLUG', 'owner-gate-7k9m2x');
Route::middleware('guest:owner')->group(function () use ($slug) {
    Route::get('/'.$slug,  [LoginController::class, 'showOwner'])->name('login.owner');
    Route::post('/'.$slug, [LoginController::class, 'owner']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth:owner,client')
    ->name('logout');

Route::prefix('owner')->name('owner.')->middleware('auth:owner')->group(function () {
    Route::get('/', OwnerDashboard::class)->name('dashboard');

    Route::get('dealers/import',  [DealerController::class, 'importForm'])->name('dealers.import');
    Route::post('dealers/import', [DealerController::class, 'import'])->name('dealers.import.store');
    Route::resource('dealers',  DealerController::class);
    Route::get('products/import',  [ProductController::class, 'importForm'])->name('products.import');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import.store');
    Route::resource('products', ProductController::class)->except(['show']);

    Route::get('orders',         [OwnerOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OwnerOrderController::class, 'show'])->name('orders.show');
    Route::get('ledger',         [OwnerLedgerController::class, 'download'])->name('ledger.download');
    Route::get('analytics',      AnalyticsController::class)->name('analytics');

    Route::get('devices',                    [DeviceController::class, 'index'])->name('devices.index');
    Route::post('devices/{device}/approve',  [DeviceController::class, 'approve'])->name('devices.approve');
    Route::post('devices/{device}/reject',   [DeviceController::class, 'reject'])->name('devices.reject');
    Route::post('devices/{device}/revoke',   [DeviceController::class, 'revoke'])->name('devices.revoke');
});

Route::prefix('client')->name('client.')->middleware('auth:client')->group(function () {
    Route::get('/',                    ClientDashboard::class)->name('dashboard');
    Route::post('/notifications/read', [ClientDashboard::class, 'markNotificationsRead'])->name('notifications.read');
    Route::get('orders/{order}',       [ClientOrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/proof', [ClientOrderController::class, 'uploadProof'])->name('orders.proof');
});
