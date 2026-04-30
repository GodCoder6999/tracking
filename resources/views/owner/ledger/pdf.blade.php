<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; background: #fff; }

    .page-header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-end; }
    .page-header .brand { font-size: 20px; font-weight: 700; color: #4f46e5; letter-spacing: -0.5px; }
    .page-header .doc-title { font-size: 13px; font-weight: 600; color: #334155; }
    .page-header .meta { font-size: 8px; color: #64748b; text-align: right; }

    .period-bar { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 4px; padding: 5px 10px; margin-bottom: 14px; font-size: 8.5px; color: #475569; }
    .period-bar strong { color: #1e293b; }

    .dealer-block { margin-bottom: 20px; page-break-inside: avoid; }
    .dealer-heading { background: #4f46e5; color: #fff; padding: 5px 10px; font-size: 10px; font-weight: 700; border-radius: 3px 3px 0 0; margin-bottom: 0; }

    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #e0e7ff; }
    thead th { padding: 4px 5px; text-align: left; font-size: 8px; font-weight: 700; color: #3730a3; border: 1px solid #c7d2fe; white-space: nowrap; }
    tbody tr { border-bottom: 1px solid #f1f5f9; }
    tbody tr:nth-child(even) { background: #fafafa; }
    tbody td { padding: 3.5px 5px; font-size: 8px; border: 1px solid #e2e8f0; vertical-align: top; }
    tfoot tr { background: #e0e7ff; }
    tfoot td { padding: 4px 5px; font-size: 8px; font-weight: 700; border: 1px solid #c7d2fe; color: #1e1b4b; }

    .num { text-align: right; }
    .center { text-align: center; }

    .badge { display: inline-block; padding: 1px 5px; border-radius: 10px; font-size: 7px; font-weight: 600; }
    .badge-paid     { background: #dcfce7; color: #166534; }
    .badge-partial  { background: #fef9c3; color: #854d0e; }
    .badge-unpaid   { background: #fee2e2; color: #991b1b; }
    .badge-delivered{ background: #dcfce7; color: #166534; }
    .badge-sent     { background: #dbeafe; color: #1d4ed8; }
    .badge-pending  { background: #f1f5f9; color: #475569; }

    .grand-total { margin-top: 18px; border: 2px solid #4f46e5; border-radius: 4px; overflow: hidden; }
    .grand-total-header { background: #4f46e5; color: #fff; padding: 6px 10px; font-size: 11px; font-weight: 700; }
    .grand-total table thead tr { background: #c7d2fe; }
    .grand-total table thead th { color: #1e1b4b; border-color: #a5b4fc; }
    .grand-total table tfoot tr { background: #818cf8; }
    .grand-total table tfoot td { color: #fff; border-color: #6366f1; font-size: 9px; }

    .footer { margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 6px; font-size: 7.5px; color: #94a3b8; text-align: center; }

    .items-list { font-size: 7.5px; color: #475569; }
    .items-list .item-row { border-bottom: 1px dotted #e2e8f0; padding: 1px 0; }
    .items-list .item-row:last-child { border-bottom: none; }
</style>
</head>
<body>

{{-- Page Header --}}
<div class="page-header">
    <div>
        <div class="brand">{{ config('app.name') }}</div>
        <div class="doc-title">Order Ledger Report</div>
    </div>
    <div class="meta">
        Generated: {{ $generatedAt->format('d M Y, h:i A') }}<br>
        Prepared for: Owner
    </div>
</div>

{{-- Period Bar --}}
<div class="period-bar">
    @if($from || $to)
        Period: <strong>{{ $from ? \Carbon\Carbon::parse($from)->format('d M Y') : 'Beginning' }}</strong>
        &nbsp;&rarr;&nbsp;
        <strong>{{ $to ? \Carbon\Carbon::parse($to)->format('d M Y') : 'Today' }}</strong>
    @else
        Period: <strong>All time</strong>
    @endif
    &nbsp;&nbsp;|&nbsp;&nbsp;
    Total Dealers: <strong>{{ $dealers->count() }}</strong>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    Total Orders: <strong>{{ $ordersByDealer->sum(fn($o) => $o->count()) }}</strong>
</div>

{{-- Per-Dealer Sections --}}
@foreach($dealers as $dealer)
    @php $orders = $ordersByDealer[$dealer->id]; @endphp
    @if($orders->isEmpty()) @continue @endif

    @php
        $dTotal    = $orders->sum('total_amount');
        $dReceived = $orders->sum('total_received');
        $dDue      = $orders->sum('due_amount');
        $dGst      = $orders->sum('gst_amount');
        $dDiscount = $orders->sum('discount_amount');
    @endphp

    <div class="dealer-block">
        <div class="dealer-heading">{{ $dealer->name }} &mdash; {{ $orders->count() }} order(s)</div>
        <table>
            <thead>
                <tr>
                    <th style="width:90px">Order #</th>
                    <th style="width:62px">Date</th>
                    <th style="width:90px">Client</th>
                    <th>Items</th>
                    <th class="num" style="width:68px">Order Total</th>
                    <th class="num" style="width:52px">GST</th>
                    <th class="num" style="width:58px">Discount</th>
                    <th class="num" style="width:68px">Received</th>
                    <th class="num" style="width:68px">Due</th>
                    <th class="center" style="width:52px">Payment</th>
                    <th class="center" style="width:56px">Dispatch</th>
                    <th style="width:80px">Courier</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $o)
                <tr>
                    <td><strong>{{ $o->order_number }}</strong></td>
                    <td>{{ $o->order_date?->format('d M Y') }}</td>
                    <td>{{ $o->client->name ?? '—' }}</td>
                    <td>
                        <div class="items-list">
                            @foreach($o->items as $item)
                            <div class="item-row">{{ $item->particulars }} &times; {{ $item->qty }} @ &#8377;{{ number_format((float)$item->rate, 2) }}</div>
                            @endforeach
                        </div>
                    </td>
                    <td class="num">&#8377;{{ number_format((float)$o->total_amount, 2) }}</td>
                    <td class="num">&#8377;{{ number_format((float)$o->gst_amount, 2) }}</td>
                    <td class="num">&#8377;{{ number_format((float)$o->discount_amount, 2) }}</td>
                    <td class="num">&#8377;{{ number_format((float)$o->total_received, 2) }}</td>
                    <td class="num">&#8377;{{ number_format((float)$o->due_amount, 2) }}</td>
                    <td class="center">
                        <span class="badge badge-{{ $o->payment_status }}">{{ ucfirst($o->payment_status) }}</span>
                    </td>
                    <td class="center">
                        <span class="badge badge-{{ $o->dispatch_status }}">{{ ucfirst($o->dispatch_status) }}</span>
                    </td>
                    <td>
                        @foreach($o->dispatches as $d)
                            @if($d->courier)
                                <div style="font-size:7px">{{ $d->courier }}{{ $d->tracking_number ? ' / '.$d->tracking_number : '' }}</div>
                            @endif
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"><strong>{{ $dealer->name }} Subtotal</strong></td>
                    <td class="num">&#8377;{{ number_format($dTotal, 2) }}</td>
                    <td class="num">&#8377;{{ number_format($dGst, 2) }}</td>
                    <td class="num">&#8377;{{ number_format($dDiscount, 2) }}</td>
                    <td class="num">&#8377;{{ number_format($dReceived, 2) }}</td>
                    <td class="num">&#8377;{{ number_format($dDue, 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
@endforeach

{{-- Grand Total --}}
<div class="grand-total">
    <div class="grand-total-header">Grand Total — All Dealers</div>
    <table>
        <thead>
            <tr>
                <th style="width:200px">Summary</th>
                <th class="num">Order Total</th>
                <th class="num">Total GST</th>
                <th class="num">Total Discount</th>
                <th class="num">Total Received</th>
                <th class="num">Total Due</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td><strong>NETWORK GRAND TOTAL</strong></td>
                <td class="num"><strong>&#8377;{{ number_format($grandTotal, 2) }}</strong></td>
                <td class="num"><strong>&#8377;{{ number_format($grandGst, 2) }}</strong></td>
                <td class="num"><strong>&#8377;{{ number_format($grandDiscount, 2) }}</strong></td>
                <td class="num"><strong>&#8377;{{ number_format($grandReceived, 2) }}</strong></td>
                <td class="num"><strong>&#8377;{{ number_format($grandDue, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="footer">
    This document is system-generated &mdash; {{ config('app.name') }} &mdash; {{ $generatedAt->format('d M Y') }}
</div>

</body>
</html>
