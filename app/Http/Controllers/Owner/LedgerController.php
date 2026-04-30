<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function download(Request $request)
    {
        $from     = $request->input('from');
        $to       = $request->input('to');
        $dealerId = $request->input('dealer_id') ?: null;

        $dealers = User::where('role', User::ROLE_DEALER)
            ->when($dealerId, fn($q) => $q->where('id', $dealerId))
            ->orderBy('name')
            ->get();

        $ordersByDealer = $dealers->mapWithKeys(function ($dealer) use ($from, $to) {
            $orders = Order::with(['client', 'items', 'payments', 'dispatches'])
                ->where('dealer_id', $dealer->id)
                ->when($from, fn($q) => $q->where('order_date', '>=', $from))
                ->when($to,   fn($q) => $q->where('order_date', '<=', $to))
                ->orderBy('order_date')
                ->orderBy('order_number')
                ->get();

            return [$dealer->id => $orders];
        });

        $grandTotal    = 0;
        $grandReceived = 0;
        $grandDue      = 0;
        $grandGst      = 0;
        $grandDiscount = 0;

        foreach ($ordersByDealer as $orders) {
            $grandTotal    += $orders->sum('total_amount');
            $grandReceived += $orders->sum('total_received');
            $grandDue      += $orders->sum('due_amount');
            $grandGst      += $orders->sum('gst_amount');
            $grandDiscount += $orders->sum('discount_amount');
        }

        $pdf = Pdf::loadView('owner.ledger.pdf', [
            'dealers'        => $dealers,
            'ordersByDealer' => $ordersByDealer,
            'from'           => $from,
            'to'             => $to,
            'grandTotal'     => $grandTotal,
            'grandReceived'  => $grandReceived,
            'grandDue'       => $grandDue,
            'grandGst'       => $grandGst,
            'grandDiscount'  => $grandDiscount,
            'generatedAt'    => now(),
        ])->setPaper('a4', 'landscape');

        $label = $from && $to
            ? "ledger_{$from}_to_{$to}"
            : 'ledger_' . now()->format('Y-m-d');

        return $pdf->download("{$label}.pdf");
    }
}
