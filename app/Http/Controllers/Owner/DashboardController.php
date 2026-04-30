<?php

namespace App\Http\Controllers\Owner;

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
        $from           = $request->filled('from')            ? $request->from            : null;
        $to             = $request->filled('to')              ? $request->to              : null;
        $dealerId       = $request->filled('dealer_id')       ? $request->dealer_id       : null;
        $clientId       = $request->filled('client_id')       ? $request->client_id       : null;
        $orderNumber    = $request->filled('order_number')    ? $request->order_number    : null;
        $paymentStatus  = $request->filled('payment_status')  ? $request->payment_status  : null;
        $dispatchStatus = $request->filled('dispatch_status') ? $request->dispatch_status : null;
        $productId      = $request->filled('product_id')      ? $request->product_id      : null;
        $topSort        = $request->input('top_sort', 'revenue');

        $base = fn() => Order::query()
            ->when($from,           fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,             fn($q) => $q->where('order_date', '<=', $to))
            ->when($dealerId,       fn($q) => $q->where('dealer_id', $dealerId))
            ->when($clientId,       fn($q) => $q->where('client_id', $clientId))
            ->when($orderNumber,    fn($q) => $q->where('order_number', 'like', "%{$orderNumber}%"))
            ->when($paymentStatus === 'overdue',
                fn($q) => $q->where('due_amount', '>', 0)->whereDate('order_date', '<=', Carbon::today()->subDays(30)))
            ->when($paymentStatus && $paymentStatus !== 'overdue',
                fn($q) => $q->where('payment_status', $paymentStatus))
            ->when($dispatchStatus, fn($q) => $q->where('dispatch_status', $dispatchStatus))
            ->when($productId,      fn($q) => $q->whereHas('items', fn($q2) => $q2->where('product_id', $productId)));

        $anyFilter = $from || $to || $dealerId || $clientId || $orderNumber || $paymentStatus || $dispatchStatus || $productId;

        $stats = [
            'total_dealers'    => $anyFilter
                ? $base()->distinct()->count('dealer_id')
                : User::where('role', User::ROLE_DEALER)->count(),
            'total_clients'    => $anyFilter
                ? $base()->distinct()->count('client_id')
                : User::where('role', User::ROLE_CLIENT)->count(),
            'total_orders'     => $base()->count(),
            'orders_today'     => Order::whereDate('order_date', Carbon::today())->count(),
            'total_revenue'    => (float) $base()->sum('total_amount'),
            'total_received'   => (float) $base()->sum('total_received'),
            'pending_due'      => (float) $base()->sum('due_amount'),
            'pending_dispatch' => $base()->whereIn('dispatch_status', ['pending', 'partial'])->count(),
        ];

        $monthly = $base()
            ->whereNotNull('order_date')
            ->orderBy('order_date')
            ->get(['order_date', 'total_amount'])
            ->groupBy(fn($o) => $o->order_date->format('Y-m'))
            ->map(fn($g, $m) => (object)['month' => $m, 'revenue' => $g->sum('total_amount')])
            ->sortKeys()
            ->values()
            ->take(-12);

        $dealers  = User::where('role', User::ROLE_DEALER)->orderBy('name')->get(['id', 'name', 'email']);
        $clients  = User::where('role', User::ROLE_CLIENT)->orderBy('name')->get(['id', 'name', 'email']);
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $topDealersBase = User::where('role', User::ROLE_DEALER)
            ->withCount('ordersAsDealer')
            ->withSum('ordersAsDealer as revenue', 'total_amount')
            ->withSum(['ordersAsDealer as pending_dues' => fn($q) => $q->where('payment_status', '!=', Order::PAYMENT_PAID)], 'due_amount');

        $topDealers = match ($topSort) {
            'orders'       => $topDealersBase->orderByDesc('orders_as_dealer_count'),
            'pending_dues' => $topDealersBase->orderByDesc('pending_dues'),
            default        => $topDealersBase->orderByDesc('revenue'),
        };
        $topDealers = $topDealers->limit(5)->get();

        $recentOrders = Order::with(['client', 'dealer', 'items'])
            ->when($from,           fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,             fn($q) => $q->where('order_date', '<=', $to))
            ->when($dealerId,       fn($q) => $q->where('dealer_id', $dealerId))
            ->when($clientId,       fn($q) => $q->where('client_id', $clientId))
            ->when($orderNumber,    fn($q) => $q->where('order_number', 'like', "%{$orderNumber}%"))
            ->when($paymentStatus === 'overdue',
                fn($q) => $q->where('due_amount', '>', 0)->whereDate('order_date', '<=', Carbon::today()->subDays(30)))
            ->when($paymentStatus && $paymentStatus !== 'overdue',
                fn($q) => $q->where('payment_status', $paymentStatus))
            ->when($dispatchStatus, fn($q) => $q->where('dispatch_status', $dispatchStatus))
            ->when($productId,      fn($q) => $q->whereHas('items', fn($q2) => $q2->where('product_id', $productId)))
            ->latest()
            ->limit(20)
            ->get();

        return view('owner.dashboard', compact(
            'stats', 'monthly', 'dealers', 'clients', 'products',
            'topDealers', 'recentOrders', 'topSort'
        ));
    }
}
