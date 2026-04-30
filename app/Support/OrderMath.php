<?php

namespace App\Support;

use App\Models\Order;

class OrderMath
{
    public static function recompute(Order $order): void
    {
        $order->loadMissing(['items', 'payments', 'dispatches']);

        $total          = (float) $order->items->sum('amount');
        $gstAmount      = (float) $order->items->sum('gst_amount');
        $discountAmount = $order->items->reduce(function ($carry, $item) {
            $subtotal = (float) $item->qty * (float) $item->rate;
            return $carry + round($subtotal * ((float) $item->discount_percent / 100), 2);
        }, 0.0);
        $received       = (float) $order->payments->sum('amount');
        $token          = (float) $order->token_amount;
        $totalRec       = $received + $token;
        $due            = max(0, $total - $totalRec);

        $order->total_amount    = $total;
        $order->gst_amount      = $gstAmount;
        $order->discount_amount = $discountAmount;
        $order->amount_received = $received;
        $order->due_amount      = $due;
        $order->total_received  = $totalRec;

        if ($total <= 0) {
            $order->payment_status = Order::PAYMENT_UNPAID;
        } elseif ($totalRec <= 0) {
            $order->payment_status = Order::PAYMENT_UNPAID;
        } elseif ($totalRec + 0.009 < $total) {
            $order->payment_status = Order::PAYMENT_PARTIAL;
        } else {
            $order->payment_status = Order::PAYMENT_PAID;
            if (! $order->full_amount_date) {
                $order->full_amount_date = now()->toDateString();
            }
            if (! $order->total_received_date) {
                $order->total_received_date = now()->toDateString();
            }
        }

        $totalQty    = (int) $order->items->sum('qty');
        $dispatched  = (int) $order->dispatches->sum('dispatch_qty');

        if ($totalQty <= 0 || $dispatched <= 0) {
            $order->dispatch_status = Order::DISPATCH_PENDING;
        } elseif ($dispatched < $totalQty) {
            $order->dispatch_status = Order::DISPATCH_PARTIAL;
        } else {
            if ($order->dispatch_status !== Order::DISPATCH_DELIVERED) {
                $order->dispatch_status = Order::DISPATCH_SENT;
            }
        }

        $order->save();
    }
}
