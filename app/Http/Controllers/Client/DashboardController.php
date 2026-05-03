<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $client = auth()->user();
        $from   = $request->filled('from') ? $request->from : null;
        $to     = $request->filled('to')   ? $request->to   : null;
        $search = $request->filled('q')    ? trim($request->q) : null;

        $base = fn() => Order::where('client_id', $client->id)
            ->when($from, fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,   fn($q) => $q->where('order_date', '<=', $to));

        $stats = [
            'total_orders'     => $base()->count(),
            'pending_due'      => (float) $base()->sum('due_amount'),
            'pending_dispatch' => $base()->whereIn('dispatch_status', ['pending', 'partial'])->count(),
            'delivered'        => $base()->where('dispatch_status', Order::DISPATCH_DELIVERED)->count(),
        ];

        $orders = Order::with(['dealer', 'items'])
            ->where('client_id', $client->id)
            ->when($from,   fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,     fn($q) => $q->where('order_date', '<=', $to))
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('items', fn($q) => $q->where('particulars', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(20)->withQueryString();

        $notifications = $client->unreadNotifications()->limit(10)->get();

        return view('client.dashboard', compact('orders', 'stats', 'notifications'));
    }

    public function markNotificationsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    }
}
