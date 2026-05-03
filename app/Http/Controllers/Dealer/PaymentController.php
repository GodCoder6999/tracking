<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderStatusUpdated;
use App\Support\OrderMath;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Order $order)
    {
        abort_unless($order->dealer_id === auth()->id(), 403);

        $data = $request->validate([
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'payment_date'  => ['required', 'date'],
            'received_date' => ['nullable', 'date'],
            'method'        => ['nullable', 'string', 'max:60'],
            'notes'         => ['nullable', 'string'],
            'screenshot'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('screenshot')) {
            $data['screenshot_path'] = $request->file('screenshot')->store('payments', 'public');
        }
        unset($data['screenshot']);

        $order->payments()->create($data);
        OrderMath::recompute($order);

        $order->client->notify(new OrderStatusUpdated($order, 'Payment recorded ₹'.number_format($data['amount'], 2)));

        return back()->with('status', 'Payment added.');
    }

    public function destroy(Order $order, int $paymentId)
    {
        abort_unless($order->dealer_id === auth()->id(), 403);

        $payment = $order->payments()->findOrFail($paymentId);
        $payment->delete();
        OrderMath::recompute($order);

        return back()->with('status', 'Payment removed.');
    }
}
