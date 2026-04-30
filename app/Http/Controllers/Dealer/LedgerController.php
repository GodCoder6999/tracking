<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function download(Request $request)
    {
        $from   = $request->input('from');
        $to     = $request->input('to');
        $dealer = auth()->user();

        $orders = Order::with(['client', 'items', 'payments', 'dispatches'])
            ->where('dealer_id', $dealer->id)
            ->when($from, fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,   fn($q) => $q->where('order_date', '<=', $to))
            ->orderBy('order_date')
            ->orderBy('order_number')
            ->get();

        $pdf = Pdf::loadView('dealer.ledger.pdf', [
            'dealer'      => $dealer,
            'orders'      => $orders,
            'from'        => $from,
            'to'          => $to,
            'totalAmount' => $orders->sum('total_amount'),
            'totalRecvd'  => $orders->sum('total_received'),
            'totalDue'    => $orders->sum('due_amount'),
            'totalGst'    => $orders->sum('gst_amount'),
            'totalDisc'   => $orders->sum('discount_amount'),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $label = $from && $to
            ? "dealer_ledger_{$from}_to_{$to}"
            : 'dealer_ledger_' . now()->format('Y-m-d');

        return $pdf->download("{$label}.pdf");
    }
}
