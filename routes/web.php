<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Client\DashboardController as ClientDashboard;
use App\Http\Controllers\Client\OrderController as ClientOrderController;
use App\Http\Controllers\Dealer\AnalyticsController as DealerAnalyticsController;
use App\Http\Controllers\Dealer\ClientController as DealerClientController;
use App\Http\Controllers\Dealer\DashboardController as DealerDashboard;
use App\Http\Controllers\Dealer\DispatchController;
use App\Http\Controllers\Dealer\LedgerController as DealerLedgerController;
use App\Http\Controllers\Dealer\OrderController as DealerOrderController;
use App\Http\Controllers\Dealer\PaymentController;
use App\Http\Controllers\Owner\AnalyticsController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboard;
use App\Http\Controllers\Owner\DealerController;
use App\Http\Controllers\Owner\LedgerController as OwnerLedgerController;
use App\Http\Controllers\Owner\OrderController as OwnerOrderController;
use App\Http\Controllers\Owner\ProductController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');


Route::middleware('guest:dealer')->group(function () {
    Route::get('/dealer/login',  [LoginController::class, 'showDealer'])->name('login.dealer');
    Route::post('/dealer/login', [LoginController::class, 'dealer']);
});

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
    ->middleware('auth:owner,dealer,client')
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
});

Route::prefix('dealer')->name('dealer.')->middleware('auth:dealer')->group(function () {
    Route::get('/', DealerDashboard::class)->name('dashboard');

    Route::get('clients/import',  [DealerClientController::class, 'importForm'])->name('clients.import');
    Route::post('clients/import', [DealerClientController::class, 'import'])->name('clients.import.store');
    Route::resource('clients', DealerClientController::class)->except(['show']);

    Route::get('orders',                 [DealerOrderController::class, 'index'])->name('orders.index');
    Route::get('ledger',                 [DealerLedgerController::class, 'download'])->name('ledger.download');
    Route::get('analytics',              DealerAnalyticsController::class)->name('analytics');

    Route::get('orders/create',          [DealerOrderController::class, 'create'])->name('orders.create');
    Route::post('orders',                [DealerOrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}',         [DealerOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/label', [DealerOrderController::class, 'updateLabel'])->name('orders.label');

    Route::post('orders/{order}/payments',               [PaymentController::class, 'store'])->name('orders.payments.store');
    Route::delete('orders/{order}/payments/{paymentId}', [PaymentController::class, 'destroy'])->name('orders.payments.destroy');

    Route::post('orders/{order}/dispatches',           [DispatchController::class, 'store'])->name('orders.dispatches.store');
    Route::post('orders/{order}/dispatches/delivered', [DispatchController::class, 'markDelivered'])->name('orders.dispatches.delivered');
});

Route::prefix('client')->name('client.')->middleware('auth:client')->group(function () {
    Route::get('/',                    ClientDashboard::class)->name('dashboard');
    Route::post('/notifications/read', [ClientDashboard::class, 'markNotificationsRead'])->name('notifications.read');
    Route::get('orders/{order}',       [ClientOrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/proof', [ClientOrderController::class, 'uploadProof'])->name('orders.proof');
});
