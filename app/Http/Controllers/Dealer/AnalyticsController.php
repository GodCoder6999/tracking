<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

class AnalyticsController extends Controller
{
    public function __invoke()
    {
        $dealer   = auth()->user();
        $did      = $dealer->id;
        $from     = request('from');
        $to       = request('to');
        $clientId = request('client_id') ?: null;

        $base = fn() => Order::where('dealer_id', $did)
            ->when($from,     fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,       fn($q) => $q->where('order_date', '<=', $to))
            ->when($clientId, fn($q) => $q->where('client_id', $clientId));

        $totals = [
            'total_revenue'  => (float) $base()->sum('total_amount'),
            'total_received' => (float) $base()->sum('total_received'),
            'total_due'      => (float) $base()->sum('due_amount'),
            'total_orders'   => $base()->count(),
            'total_clients'  => User::where('role', User::ROLE_CLIENT)->where('created_by', $did)->count(),
        ];

        $months = collect(range(11, 0))
            ->map(fn($i) => now()->subMonths($i)->format('Y-m'))
            ->values();

        $monthlyRaw = $base()
            ->selectRaw("strftime('%Y-%m', order_date) as month, SUM(total_amount) as revenue")
            ->groupBy('month')
            ->pluck('revenue', 'month');
        $monthlyLine = $months->map(fn($m) => (float) ($monthlyRaw->get($m) ?? 0))->values();

        $paymentStatus  = $base()->selectRaw('payment_status, COUNT(*) as cnt')->groupBy('payment_status')->pluck('cnt', 'payment_status');
        $dispatchStatus = $base()->selectRaw('dispatch_status, COUNT(*) as cnt')->groupBy('dispatch_status')->pluck('cnt', 'dispatch_status');

        $funnel = [
            'Total Orders' => $base()->count(),
            'Any Payment'  => $base()->whereIn('payment_status', ['partial', 'paid'])->count(),
            'Fully Paid'   => $base()->where('payment_status', 'paid')->count(),
            'Any Dispatch' => $base()->whereIn('dispatch_status', ['partial', 'sent', 'delivered'])->count(),
            'Delivered'    => $base()->where('dispatch_status', 'delivered')->count(),
        ];

        $myClients = User::where('role', User::ROLE_CLIENT)
            ->where('created_by', $did)
            ->select('id', 'name', 'is_active')
            ->orderBy('name')
            ->get();

        $clientOrderStats = $base()
            ->selectRaw('client_id, SUM(total_amount) as revenue, SUM(total_received) as received, SUM(due_amount) as due, COUNT(*) as orders')
            ->groupBy('client_id')
            ->get()
            ->keyBy('client_id');

        $clientStats = $myClients->map(fn($c) => [
            'id'       => $c->id,
            'name'     => $c->name,
            'active'   => (bool) $c->is_active,
            'revenue'  => (float) ($clientOrderStats->get($c->id)?->revenue  ?? 0),
            'received' => (float) ($clientOrderStats->get($c->id)?->received ?? 0),
            'due'      => (float) ($clientOrderStats->get($c->id)?->due      ?? 0),
            'orders'   => (int)   ($clientOrderStats->get($c->id)?->orders   ?? 0),
        ])->values();

        $topProducts = OrderItem::selectRaw('particulars, SUM(amount) as revenue, SUM(qty) as units')
            ->whereIn('order_id', function ($q) use ($did, $from, $to, $clientId) {
                $q->select('id')->from('orders')
                    ->where('dealer_id', $did)
                    ->when($from,     fn($q) => $q->where('order_date', '>=', $from))
                    ->when($to,       fn($q) => $q->where('order_date', '<=', $to))
                    ->when($clientId, fn($q) => $q->where('client_id', $clientId));
            })
            ->groupBy('particulars')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $clientsList = $myClients->map(fn($c) => ['id' => $c->id, 'name' => $c->name]);

        return view('dealer.analytics', compact(
            'totals', 'months', 'monthlyLine', 'paymentStatus', 'dispatchStatus',
            'funnel', 'clientStats', 'topProducts', 'clientsList'
        ));
    }
}
