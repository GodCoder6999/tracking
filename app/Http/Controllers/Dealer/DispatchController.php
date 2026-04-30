<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderStatusUpdated;
use App\Support\OrderMath;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function store(Request $request, Order $order)
    {
        abort_unless($order->dealer_id === auth()->id(), 403);

        $data = $request->validate([
            'dispatch_qty'    => ['required', 'integer', 'min:1'],
            'dispatch_date'   => ['required', 'date'],
            'courier'         => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'tracking_url'    => ['nullable', 'url', 'max:500'],
            'notes'           => ['nullable', 'string'],
            'bill'            => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $totalQty  = (int) $order->items()->sum('qty');
        $dispatched = (int) $order->dispatches()->sum('dispatch_qty');
        $remaining  = max(0, $totalQty - $dispatched);
        $thisQty    = min($data['dispatch_qty'], $remaining ?: $data['dispatch_qty']);

        if ($request->hasFile('bill')) {
            $data['bill_path'] = $request->file('bill')->store('bills', 'public');
        }
        unset($data['bill']);

        $data['dispatch_qty'] = $thisQty;
        $data['due_qty']      = max(0, $totalQty - $dispatched - $thisQty);

        $order->dispatches()->create($data);
        OrderMath::recompute($order);

        $order->client->notify(new OrderStatusUpdated($order, "Dispatched {$thisQty} units"));

        return back()->with('status', 'Dispatch recorded.');
    }

    public function markDelivered(Order $order)
    {
        abort_unless($order->dealer_id === auth()->id(), 403);
        $order->update(['dispatch_status' => Order::DISPATCH_DELIVERED]);
        $order->client->notify(new OrderStatusUpdated($order, 'Delivered'));
        return back()->with('status', 'Marked delivered.');
    }
}
