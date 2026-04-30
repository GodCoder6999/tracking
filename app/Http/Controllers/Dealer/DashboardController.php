<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $dealer = auth()->user();

        $from           = $request->filled('from')            ? $request->from            : null;
        $to             = $request->filled('to')              ? $request->to              : null;
        $clientId       = $request->filled('client_id')       ? $request->client_id       : null;
        $orderNumber    = $request->filled('order_number')    ? $request->order_number    : null;
        $paymentStatus  = $request->filled('payment_status')  ? $request->payment_status  : null;
        $dispatchStatus = $request->filled('dispatch_status') ? $request->dispatch_status : null;
        $productId      = $request->filled('product_id')      ? $request->product_id      : null;

        $base = fn() => Order::where('dealer_id', $dealer->id)
            ->when($from,           fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,             fn($q) => $q->where('order_date', '<=', $to))
            ->when($clientId,       fn($q) => $q->where('client_id', $clientId))
            ->when($orderNumber,    fn($q) => $q->where('order_number', 'like', "%{$orderNumber}%"))
            ->when($paymentStatus === 'overdue',
                fn($q) => $q->where('due_amount', '>', 0)->whereDate('order_date', '<=', Carbon::today()->subDays(30)))
            ->when($paymentStatus && $paymentStatus !== 'overdue',
                fn($q) => $q->where('payment_status', $paymentStatus))
            ->when($dispatchStatus, fn($q) => $q->where('dispatch_status', $dispatchStatus))
            ->when($productId,      fn($q) => $q->whereHas('items', fn($q2) => $q2->where('product_id', $productId)));

        $isFiltered = $from || $to || $clientId || $orderNumber || $paymentStatus || $dispatchStatus || $productId;

        $stats = [
            'clients'          => $isFiltered
                ? $base()->distinct()->count('client_id')
                : User::where('role', User::ROLE_CLIENT)->where('created_by', $dealer->id)->count(),
            'orders'           => $base()->count(),
            'revenue'          => (float) $base()->sum('total_amount'),
            'received'         => (float) $base()->sum('total_received'),
            'due'              => (float) $base()->sum('due_amount'),
            'pending_dispatch' => $base()->whereIn('dispatch_status', ['pending', 'partial'])->count(),
        ];

        $clients = User::where('role', User::ROLE_CLIENT)
            ->where('created_by', $dealer->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $recent = Order::with(['client', 'items'])
            ->where('dealer_id', $dealer->id)
            ->when($from,           fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,             fn($q) => $q->where('order_date', '<=', $to))
            ->when($clientId,       fn($q) => $q->where('client_id', $clientId))
            ->when($orderNumber,    fn($q) => $q->where('order_number', 'like', "%{$orderNumber}%"))
            ->when($paymentStatus === 'overdue',
                fn($q) => $q->where('due_amount', '>', 0)->whereDate('order_date', '<=', Carbon::today()->subDays(30)))
            ->when($paymentStatus && $paymentStatus !== 'overdue',
                fn($q) => $q->where('payment_status', $paymentStatus))
            ->when($dispatchStatus, fn($q) => $q->where('dispatch_status', $dispatchStatus))
            ->when($productId,      fn($q) => $q->whereHas('items', fn($q2) => $q2->where('product_id', $productId)))
            ->latest()
            ->when(!$isFiltered, fn($q) => $q->limit(20))
            ->get();

        return view('dealer.dashboard', compact('stats', 'clients', 'products', 'recent'));
    }
}
