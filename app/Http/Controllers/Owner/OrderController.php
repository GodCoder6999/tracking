<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['client', 'dealer', 'items'])
            ->when($request->filled('dealer_id'),       fn($q) => $q->where('dealer_id', $request->dealer_id))
            ->when($request->filled('payment_status'),  fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('dispatch_status'), fn($q) => $q->where('dispatch_status', $request->dispatch_status))
            ->when($request->filled('q'),               fn($q) => $q->where('order_number', 'like', '%'.$request->q.'%'))
            ->when($request->filled('from'),            fn($q) => $q->where('order_date', '>=', $request->from))
            ->when($request->filled('to'),              fn($q) => $q->where('order_date', '<=', $request->to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $dealers = User::where('role', User::ROLE_DEALER)->orderBy('name')->get(['id', 'name']);

        return view('owner.orders.index', compact('orders', 'dealers'));
    }

    public function show(Order $order)
    {
        $order->load(['client', 'dealer', 'items.product', 'payments', 'dispatches.dispatchItems']);
        return view('owner.orders.show', compact('order'));
    }
}
