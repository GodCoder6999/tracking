<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

class AnalyticsController extends Controller
{
    public function __invoke()
    {
        $from     = request('from');
        $to       = request('to');
        $dealerId = request('dealer_id') ?: null;

        $base = fn() => Order::query()
            ->when($from,     fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,       fn($q) => $q->where('order_date', '<=', $to))
            ->when($dealerId, fn($q) => $q->where('dealer_id', $dealerId));

        // Summary totals
        $totals = [
            'total_revenue'  => (float) $base()->sum('total_amount'),
            'total_received' => (float) $base()->sum('total_received'),
            'total_due'      => (float) $base()->sum('due_amount'),
            'total_orders'   => $base()->count(),
            'total_dealers'  => User::where('role', User::ROLE_DEALER)->count(),
            'total_clients'  => User::where('role', User::ROLE_CLIENT)->count(),
        ];

        // Last 12 months axis
        $months = collect(range(11, 0))
            ->map(fn($i) => now()->subMonths($i)->format('Y-m'))
            ->values();

        // Dealer rankings
        $dealerRankings = User::where('role', User::ROLE_DEALER)
            ->when($dealerId, fn($q) => $q->where('id', $dealerId))
            ->withSum(['ordersAsDealer as revenue' => fn($q) => $q
                ->when($from, fn($q) => $q->where('order_date', '>=', $from))
                ->when($to,   fn($q) => $q->where('order_date', '<=', $to))
            ], 'total_amount')
            ->withSum(['ordersAsDealer as received_sum' => fn($q) => $q
                ->when($from, fn($q) => $q->where('order_date', '>=', $from))
                ->when($to,   fn($q) => $q->where('order_date', '<=', $to))
            ], 'total_received')
            ->withCount([
                'ordersAsDealer as order_count',
                'clients as client_count',
            ])
            ->orderByDesc('revenue')
            ->get();

        $allDealerIds = $dealerRankings->pluck('id');

        // Monthly revenue per dealer
        $dealerMonthlyRaw = Order::selectRaw(
                "strftime('%Y-%m', order_date) as month, dealer_id, SUM(total_amount) as revenue"
            )
            ->whereIn('dealer_id', $allDealerIds)
            ->when($from, fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,   fn($q) => $q->where('order_date', '<=', $to))
            ->groupBy('month', 'dealer_id')
            ->get()
            ->groupBy('dealer_id');

        // Top 5 dealer multi-line data
        $dealerLines = $dealerRankings->take(5)->map(function ($d) use ($dealerMonthlyRaw, $months) {
            $byMonth = $dealerMonthlyRaw->get($d->id, collect())->keyBy('month');
            return [
                'name' => $d->name,
                'data' => $months->map(fn($m) => (float) ($byMonth->get($m)?->revenue ?? 0))->values(),
            ];
        })->values();

        // Network total monthly
        $networkByMonth = $base()
            ->selectRaw("strftime('%Y-%m', order_date) as month, SUM(total_amount) as revenue")
            ->groupBy('month')
            ->pluck('revenue', 'month');
        $networkLine = $months->map(fn($m) => (float) ($networkByMonth->get($m) ?? 0))->values();

        // Sparklines: last 6 months per dealer
        $spark6 = $months->slice(6)->values();
        $sparklines = $dealerRankings->mapWithKeys(function ($d) use ($dealerMonthlyRaw, $spark6) {
            $byMonth = $dealerMonthlyRaw->get($d->id, collect())->keyBy('month');
            return [$d->id => $spark6->map(fn($m) => (float) ($byMonth->get($m)?->revenue ?? 0))->values()];
        });

        // Client status per dealer
        $allClients = User::where('role', User::ROLE_CLIENT)
            ->select('id', 'created_by', 'is_active')
            ->withCount('ordersAsClient')
            ->get();

        $clientStatusByDealer = $dealerRankings
            ->map(function ($d) use ($allClients) {
                $mine = $allClients->where('created_by', $d->id);
                return [
                    'name'       => $d->name,
                    'active'     => $mine->where('is_active', true)->where('orders_as_client_count', '>', 0)->count(),
                    'onboarding' => $mine->where('is_active', true)->where('orders_as_client_count', 0)->count(),
                    'churned'    => $mine->where('is_active', false)->count(),
                ];
            })
            ->filter(fn($d) => ($d['active'] + $d['onboarding'] + $d['churned']) > 0)
            ->values();

        // Scatter: dealer efficiency
        $scatterData = $dealerRankings
            ->filter(fn($d) => $d->client_count > 0)
            ->map(fn($d) => [
                'x'      => (int) $d->client_count,
                'y'      => round((float) $d->revenue / max((int) $d->client_count, 1), 2),
                'label'  => $d->name,
                'orders' => (int) $d->order_count,
            ])->values();

        // Payment / dispatch status
        $paymentStatus  = $base()->selectRaw('payment_status, COUNT(*) as cnt')->groupBy('payment_status')->pluck('cnt', 'payment_status');
        $dispatchStatus = $base()->selectRaw('dispatch_status, COUNT(*) as cnt')->groupBy('dispatch_status')->pluck('cnt', 'dispatch_status');

        // Top products
        $topProducts = OrderItem::selectRaw('particulars, SUM(amount) as revenue, SUM(qty) as units')
            ->whereIn('order_id', function ($q) use ($from, $to, $dealerId) {
                $q->select('id')->from('orders')
                    ->when($from,     fn($q) => $q->where('order_date', '>=', $from))
                    ->when($to,       fn($q) => $q->where('order_date', '<=', $to))
                    ->when($dealerId, fn($q) => $q->where('dealer_id', $dealerId));
            })
            ->groupBy('particulars')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // Order pipeline funnel
        $funnel = [
            'Total Orders' => $base()->count(),
            'Any Payment'  => $base()->whereIn('payment_status', ['partial', 'paid'])->count(),
            'Fully Paid'   => $base()->where('payment_status', 'paid')->count(),
            'Any Dispatch' => $base()->whereIn('dispatch_status', ['partial', 'sent', 'delivered'])->count(),
            'Delivered'    => $base()->where('dispatch_status', 'delivered')->count(),
        ];

        $dealerRankingsJs = $dealerRankings->map(fn($d) => [
            'id'           => $d->id,
            'name'         => $d->name,
            'revenue'      => (float) $d->revenue,
            'order_count'  => (int)   $d->order_count,
            'client_count' => (int)   $d->client_count,
        ])->values();

        // Dealers list for filter dropdown
        $dealersList = User::where('role', User::ROLE_DEALER)->orderBy('name')->get(['id', 'name']);

        return view('owner.analytics', compact(
            'totals', 'months', 'dealerRankings', 'dealerRankingsJs', 'dealerLines', 'networkLine',
            'clientStatusByDealer', 'scatterData', 'paymentStatus', 'dispatchStatus',
            'topProducts', 'funnel', 'sparklines', 'spark6', 'dealersList'
        ));
    }
}
