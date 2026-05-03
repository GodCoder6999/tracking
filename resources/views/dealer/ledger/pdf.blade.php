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

    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #e0e7ff; }
    thead th { padding: 4px 6px; text-align: left; font-size: 8px; font-weight: 700; color: #3730a3; border: 1px solid #c7d2fe; white-space: nowrap; }
    tbody tr { border-bottom: 1px solid #f1f5f9; }
    tbody tr:nth-child(even) { background: #fafafa; }
    tbody td { padding: 4px 6px; font-size: 8px; border: 1px solid #e2e8f0; vertical-align: top; }
    tfoot tr { background: #818cf8; }
    tfoot td { padding: 5px 6px; font-size: 9px; font-weight: 700; border: 1px solid #6366f1; color: #fff; }

    .num { text-align: right; }
    .center { text-align: center; }

    .badge { display: inline-block; padding: 1px 5px; border-radius: 10px; font-size: 7px; font-weight: 600; }
    .badge-paid      { background: #dcfce7; color: #166534; }
    .badge-partial   { background: #fef9c3; color: #854d0e; }
    .badge-unpaid    { background: #fee2e2; color: #991b1b; }
    .badge-delivered { background: #dcfce7; color: #166534; }
    .badge-sent      { background: #dbeafe; color: #1d4ed8; }
    .badge-pending   { background: #f1f5f9; color: #475569; }

    .summary-box { margin-top: 18px; border: 2px solid #4f46e5; border-radius: 4px; overflow: hidden; }
    .summary-header { background: #4f46e5; color: #fff; padding: 6px 10px; font-size: 11px; font-weight: 700; }
    .summary-grid { display: flex; }
    .summary-cell { flex: 1; padding: 8px 12px; border-right: 1px solid #c7d2fe; background: #f5f3ff; }
    .summary-cell:last-child { border-right: none; }
    .summary-cell .label { font-size: 7.5px; color: #6366f1; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
    .summary-cell .value { font-size: 13px; font-weight: 700; color: #1e1b4b; margin-top: 2px; }

    .month-section { margin-bottom: 16px; }
    .month-heading { background: #6366f1; color: #fff; padding: 4px 8px; font-size: 9px; font-weight: 700; border-radius: 3px 3px 0 0; }

    .items-list { font-size: 7.5px; color: #475569; }
    .items-list .item-row { border-bottom: 1px dotted #e2e8f0; padding: 1px 0; }
    .items-list .item-row:last-child { border-bottom: none; }

    .footer { margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 6px; font-size: 7.5px; color: #94a3b8; text-align: center; }

    .payments-mini { font-size: 7px; color: #475569; }
    .payments-mini .prow { padding: 0.5px 0; }
</style>
</head>
<body>

{{-- Page Header --}}
<div class="page-header">
    <div>
        <div class="brand">{{ config('app.name') }}</div>
        <div class="doc-title">Dealer Order Ledger</div>
    </div>
    <div class="meta">
        Dealer: <strong>{{ $dealer->name }}</strong><br>
        {{ $dealer->email }}<br>
        Generated: {{ $generatedAt->format('d M Y, h:i A') }}
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
    @if($clientName ?? null)
        Client: <strong>{{ $clientName }}</strong>
        &nbsp;&nbsp;|&nbsp;&nbsp;
    @endif
    Total Orders: <strong>{{ $orders->count() }}</strong>
    @if(!($clientName ?? null))
        &nbsp;&nbsp;|&nbsp;&nbsp;
        Unique Clients: <strong>{{ $orders->pluck('client_id')->unique()->count() }}</strong>
    @endif
</div>

{{-- Orders grouped by month --}}
@php
    $byMonth = $orders->groupBy(fn($o) => $o->order_date?->format('Y-m'));
@endphp

@foreach($byMonth as $ym => $monthOrders)
@php
    $monthLabel  = \Carbon\Carbon::parse($ym . '-01')->format('F Y');
    $mTotal      = $monthOrders->sum('total_amount');
    $mReceived   = $monthOrders->sum('total_received');
    $mDue        = $monthOrders->sum('due_amount');
    $mGst        = $monthOrders->sum('gst_amount');
    $mDiscount   = $monthOrders->sum('discount_amount');
@endphp

<div class="month-section">
    <div class="month-heading">{{ $monthLabel }} &mdash; {{ $monthOrders->count() }} order(s)</div>
    <table>
        <thead>
            <tr>
                <th style="width:90px">Order #</th>
                <th style="width:62px">Date</th>
                <th style="width:90px">Client</th>
                <th>Items</th>
                <th>Payments</th>
                <th class="num" style="width:68px">Order Total</th>
                <th class="num" style="width:50px">GST</th>
                <th class="num" style="width:58px">Discount</th>
                <th class="num" style="width:68px">Received</th>
                <th class="num" style="width:68px">Due</th>
                <th class="center" style="width:52px">Payment</th>
                <th class="center" style="width:56px">Dispatch</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthOrders as $o)
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
                <td>
                    <div class="payments-mini">
                        @foreach($o->payments as $p)
                        <div class="prow">{{ $p->received_date ? \Carbon\Carbon::parse($p->received_date)->format('d M') : ($p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->format('d M') : '—') }}: &#8377;{{ number_format((float)$p->amount, 2) }} ({{ $p->method ?? '—' }})</div>
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
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5"><strong>{{ $monthLabel }} Subtotal</strong></td>
                <td class="num">&#8377;{{ number_format($mTotal, 2) }}</td>
                <td class="num">&#8377;{{ number_format($mGst, 2) }}</td>
                <td class="num">&#8377;{{ number_format($mDiscount, 2) }}</td>
                <td class="num">&#8377;{{ number_format($mReceived, 2) }}</td>
                <td class="num">&#8377;{{ number_format($mDue, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endforeach

{{-- Summary Box --}}
<div class="summary-box">
    <div class="summary-header">Ledger Summary</div>
    <div class="summary-grid">
        <div class="summary-cell">
            <div class="label">Order Total</div>
            <div class="value">&#8377;{{ number_format($totalAmount, 2) }}</div>
        </div>
        <div class="summary-cell">
            <div class="label">Total GST</div>
            <div class="value">&#8377;{{ number_format($totalGst, 2) }}</div>
        </div>
        <div class="summary-cell">
            <div class="label">Total Discount</div>
            <div class="value">&#8377;{{ number_format($totalDisc, 2) }}</div>
        </div>
        <div class="summary-cell">
            <div class="label">Total Received</div>
            <div class="value">&#8377;{{ number_format($totalRecvd, 2) }}</div>
        </div>
        <div class="summary-cell">
            <div class="label">Outstanding Due</div>
            <div class="value" style="color:#dc2626">&#8377;{{ number_format($totalDue, 2) }}</div>
        </div>
    </div>
</div>

<div class="footer">
    This document is system-generated &mdash; {{ config('app.name') }} &mdash; {{ $generatedAt->format('d M Y') }} &mdash; Dealer: {{ $dealer->name }}
</div>

</body>
</html>
