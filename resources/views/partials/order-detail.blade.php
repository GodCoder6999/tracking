@php
    $isDealer      = ($mode ?? 'view') === 'dealer';
    $isClient      = ($mode ?? 'view') === 'client';
    $readonly      = ! $isDealer;
    $totalQty      = (int) $order->items->sum('qty');
    $dispatchedQty = (int) $order->dispatches->sum('dispatch_qty');
    $remainingQty  = max(0, $totalQty - $dispatchedQty);

    $courierLinks = [
        'delhivery'         => 'https://www.delhivery.com/track-v2/package/',
        'bluedart'          => 'https://www.bluedart.com/tracking?trackFor=0&field1=',
        'dtdc'              => 'https://www.dtdc.in/tracking.asp?txtwbno=',
        'xpressbees'        => 'https://www.xpressbees.com/shipment/tracking/?awbNo=',
        'fedex'             => 'https://www.fedex.com/fedextrack/?trknbr=',
        'dhl'               => 'https://www.dhl.com/in-en/home/tracking.html?tracking-id=',
        'shadowfax'         => 'https://tracker.shadowfax.in/?awb=',
        'ekart'             => 'https://ekartlogistics.com/shipmenttrack/',
    ];
@endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @include('partials.stat', ['label' => 'Total',      'value' => '₹'.number_format((float) $order->total_amount, 2)])
    @include('partials.stat', ['label' => 'Received',   'value' => '₹'.number_format((float) $order->total_received, 2)])
    @include('partials.stat', ['label' => 'Due',        'value' => '₹'.number_format((float) $order->due_amount, 2)])
    @include('partials.stat', ['label' => 'Token Paid', 'value' => '₹'.number_format((float) $order->token_amount, 2)])
</div>
@if ((float)$order->gst_amount > 0 || (float)$order->discount_amount > 0)
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @php $taxable = (float)$order->total_amount - (float)$order->gst_amount; @endphp
    @include('partials.stat', ['label' => 'Taxable Value', 'value' => '₹'.number_format($taxable, 2)])
    @include('partials.stat', ['label' => 'GST',           'value' => '₹'.number_format((float) $order->gst_amount, 2)])
    @if ((float)$order->discount_amount > 0)
    @include('partials.stat', ['label' => 'Discount Given','value' => '₹'.number_format((float) $order->discount_amount, 2)])
    @endif
</div>
@endif
<div class="grid grid-cols-3 gap-4">
    @include('partials.stat', ['label' => 'Units Ordered',    'value' => $totalQty.' units'])
    @include('partials.stat', ['label' => 'Units Dispatched', 'value' => $dispatchedQty.' units'])
    @include('partials.stat', ['label' => 'Units Remaining',  'value' => $remainingQty.' units', 'highlight' => $remainingQty > 0])
</div>

<div class="card">
    <div class="flex flex-wrap gap-2 items-center justify-between">
        <div class="space-x-1">@include('partials.badges', ['order' => $order])</div>
        <div class="text-sm text-slate-500">
            Order date: {{ $order->order_date?->format('d M Y') }}
            @if ($order->full_amount_date)&middot; Full amount: {{ $order->full_amount_date->format('d M Y') }}@endif
            @if ($order->total_received_date)&middot; Last received: {{ $order->total_received_date->format('d M Y') }}@endif
        </div>
    </div>
    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div><div class="text-slate-500">Client</div><div class="font-medium">{{ $order->client->name }}</div><div class="text-slate-500">{{ $order->client->phone }} &middot; {{ $order->client->email }}</div></div>
        <div><div class="text-slate-500">Dealer</div><div class="font-medium">{{ $order->dealer->name }}</div><div class="text-slate-500">{{ $order->dealer->email }}</div></div>
    </div>
    @if ($order->shipping_address || $order->billing_address)
    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        @if ($order->shipping_address)<div><div class="text-slate-500">Shipping Address</div><div class="whitespace-pre-line">{{ $order->shipping_address }}</div></div>@endif
        @if ($order->billing_address)<div><div class="text-slate-500">Billing Address</div><div class="whitespace-pre-line">{{ $order->billing_address }}</div></div>@endif
    </div>
    @endif
    @php $meta = array_filter(['Method' => $order->payment_method ? ucfirst(str_replace('_',' ',$order->payment_method)) : null, 'Terms' => $order->invoicing_terms ? str_replace('_',' ',ucwords($order->invoicing_terms,'_')) : null, 'Shipping' => $order->shipping_method ? ucfirst($order->shipping_method) : null, 'Delivery by' => $order->requested_delivery_date?->format('d M Y')]); @endphp
    @if ($meta)<div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">@foreach($meta as $k=>$v)<span class="text-slate-500">{{ $k }}:</span> <span class="mr-3">{{ $v }}</span>@endforeach</div>@endif
    @if ($order->notes)<div class="mt-3 text-sm"><span class="text-slate-500">Client Notes:</span> {{ $order->notes }}</div>@endif
    @if ($isDealer && $order->internal_notes)<div class="mt-2 text-sm bg-amber-50 border border-amber-200 rounded px-3 py-2"><span class="text-amber-700 font-medium">Internal Note:</span> {{ $order->internal_notes }}</div>@endif
    @if ($order->token_proof_path)
    <div class="mt-3 text-sm"><span class="text-slate-500">Token Proof:</span> <a target="_blank" class="text-brand-600 hover:underline" href="{{ asset('storage/'.$order->token_proof_path) }}">View</a></div>
    @endif
    @if ($order->attachment_path)
    <div class="mt-3 text-sm"><span class="text-slate-500">Attachment:</span> <a target="_blank" class="text-brand-600 hover:underline" href="{{ asset('storage/'.$order->attachment_path) }}">View Document</a></div>
    @endif

    {{-- Catalog tiles --}}
    @if ($order->items->isNotEmpty())
    <div class="mt-4 pt-4 border-t">
        <div class="text-xs text-slate-500 mb-2 font-medium uppercase tracking-wide">Order Items</div>
        <div class="flex flex-wrap gap-3">
            @foreach ($order->items as $item)
            <div class="flex items-center gap-3 border border-slate-200 rounded-lg px-3 py-2 bg-slate-50" style="max-width:220px">
                <img src="https://picsum.photos/seed/{{ urlencode(strtolower(trim($item->particulars))) }}/56/56"
                     class="w-14 h-14 rounded object-cover flex-shrink-0"
                     alt="{{ $item->particulars }}"
                     loading="lazy"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="w-14 h-14 rounded bg-blue-100 text-blue-700 font-bold text-sm items-center justify-center flex-shrink-0 hidden">
                    {{ strtoupper(substr($item->particulars, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <div class="text-xs font-medium text-slate-800 truncate" title="{{ $item->particulars }}">{{ $item->particulars }}</div>
                    <div class="text-xs text-slate-500">×{{ $item->qty }} @ ₹{{ number_format((float)$item->rate, 0) }}</div>
                    <div class="text-xs font-semibold text-slate-800">₹{{ number_format((float)$item->amount, 2) }}</div>
                    @if ((float)$item->discount_percent > 0)
                    <div class="text-xs text-green-600">−{{ number_format((float)$item->discount_percent, 0) }}% disc</div>
                    @endif
                    @if ((float)$item->gst_rate > 0)
                    <div class="text-xs text-amber-600">GST {{ number_format((float)$item->gst_rate, 0) }}%</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@if ($isClient)
<div class="card">
    <h3 class="font-semibold mb-3">Upload Payment Proof</h3>
    @if ($order->client_proof_path)
        <p class="text-sm text-slate-500 mb-3">
            Last uploaded {{ $order->client_proofed_at?->format('d M Y') }}.
            <a target="_blank" class="text-brand-600 hover:underline" href="{{ asset('storage/'.$order->client_proof_path) }}">View</a>
        </p>
    @endif
    <form method="POST" action="{{ route('client.orders.proof', $order) }}" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-end">
        @csrf
        <input type="file" name="proof" accept=".pdf,image/*" class="input" required>
        <button class="btn-primary">{{ $order->client_proof_path ? 'Replace Proof' : 'Upload Proof' }}</button>
    </form>
</div>
@endif

@if ($isDealer && $order->client_proof_path)
<div class="card">
    <h3 class="font-semibold mb-1">Payment Proof from Client</h3>
    <p class="text-sm text-slate-500 mb-3">Uploaded {{ $order->client_proofed_at?->format('d M Y H:i') }}</p>
    <a target="_blank" class="btn-secondary inline-block" href="{{ asset('storage/'.$order->client_proof_path) }}">View Screenshot</a>
</div>
@endif

@if (! $readonly)
<div class="card">
    <h3 class="font-semibold mb-3">Label Status</h3>
    <form method="POST" action="{{ route('dealer.orders.label', $order) }}" class="flex gap-2 items-end">
        @csrf @method('PATCH')
        <select name="label_status" class="input max-w-xs">
            @foreach (['pending', 'printed', 'attached'] as $s)<option value="{{ $s }}" @selected($order->label_status===$s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <button class="btn-primary">Update</button>
    </form>
</div>
@endif

<div class="card p-0 overflow-hidden">
    <div class="p-4 border-b font-semibold">Items</div>

    {{-- Catalog tiles --}}
    @if ($order->items->isNotEmpty())
    <div class="flex flex-wrap gap-3 px-4 py-4 border-b bg-slate-50">
        @foreach ($order->items as $item)
        <div class="flex items-center gap-3 border border-slate-200 rounded-lg px-3 py-2 bg-white" style="max-width:220px">
            <img src="https://picsum.photos/seed/{{ urlencode(strtolower(trim($item->particulars))) }}/56/56"
                 class="w-14 h-14 rounded object-cover flex-shrink-0"
                 alt="{{ $item->particulars }}"
                 loading="lazy"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="w-14 h-14 rounded bg-blue-100 text-blue-700 font-bold text-sm items-center justify-center flex-shrink-0 hidden">
                {{ strtoupper(substr($item->particulars, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <div class="text-xs font-medium text-slate-800 truncate" title="{{ $item->particulars }}">{{ $item->particulars }}</div>
                <div class="text-xs text-slate-500">×{{ $item->qty }} @ ₹{{ number_format((float)$item->rate, 0) }}</div>
                @if ((float)$item->discount_percent > 0)
                <div class="text-xs text-green-600">−{{ number_format((float)$item->discount_percent, 0) }}% disc</div>
                @endif
                @if ((float)$item->gst_rate > 0)
                <div class="text-xs text-amber-600">GST {{ number_format((float)$item->gst_rate, 0) }}%</div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr>
                <th class="text-left px-4 py-2">Date</th>
                <th class="text-left">Particulars</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Disc%</th>
                <th>GST%</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach ($order->items as $item)
                <tr>
                    <td class="px-4 py-2">{{ $order->order_date?->format('d M Y') }}</td>
                    <td>{{ $item->particulars }}</td>
                    <td class="text-center">{{ $item->qty }}</td>
                    <td class="text-center">₹{{ number_format((float) $item->rate, 2) }}</td>
                    <td class="text-center">{{ (float)$item->discount_percent > 0 ? number_format((float)$item->discount_percent,1).'%' : '—' }}</td>
                    <td class="text-center">{{ (float)$item->gst_rate > 0 ? number_format((float)$item->gst_rate,0).'%' : '—' }}</td>
                    <td class="text-center">₹{{ number_format((float) $item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-slate-50 font-semibold">
            <tr><td colspan="6" class="px-4 py-2 text-right">Total</td><td class="text-center">₹{{ number_format((float) $order->total_amount, 2) }}</td></tr>
        </tfoot>
    </table>
</div>

<div class="card p-0 overflow-hidden">
    <div class="p-4 border-b font-semibold flex items-center justify-between">
        <span>Payments</span>
        @if (! $readonly)<span class="text-xs text-slate-500">Upload screenshot (PDF/JPG/PNG/WEBP, max 5MB)</span>@endif
    </div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr><th class="text-left px-4 py-2">Payment Date</th><th>Received Date</th><th>Amount</th><th>Method</th><th>Screenshot</th>@unless ($readonly)<th></th>@endunless</tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($order->payments as $p)
                <tr>
                    <td class="px-4 py-2">{{ $p->payment_date?->format('d M Y') }}</td>
                    <td class="text-center">{{ $p->received_date?->format('d M Y') ?? '—' }}</td>
                    <td class="text-center">₹{{ number_format((float) $p->amount, 2) }}</td>
                    <td class="text-center">{{ $p->method ?? '—' }}</td>
                    <td class="text-center">@if ($p->screenshot_path)<a target="_blank" class="text-brand-600 hover:underline" href="{{ asset('storage/'.$p->screenshot_path) }}">View</a>@else — @endif</td>
                    @unless ($readonly)
                    <td class="text-right px-4">
                        <form method="POST" action="{{ route('dealer.orders.payments.destroy', [$order, $p->id]) }}" onsubmit="return confirm('Remove payment?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">Remove</button>
                        </form>
                    </td>
                    @endunless
                </tr>
            @empty
                <tr><td colspan="{{ $readonly ? 5 : 6 }}" class="px-4 py-4 text-center text-slate-400">No payments yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if (! $readonly)
    <form method="POST" action="{{ route('dealer.orders.payments.store', $order) }}" enctype="multipart/form-data" class="p-4 grid grid-cols-1 md:grid-cols-5 gap-3 border-t bg-slate-50">
        @csrf
        <input type="number" step="0.01" name="amount" class="input" placeholder="Amount ₹" required>
        <input type="date" name="payment_date" class="input" value="{{ now()->toDateString() }}" required>
        <input type="date" name="received_date" class="input" placeholder="Received date">
        <select name="method" class="input">
            <option value="">Method</option>
            <option value="UPI">UPI</option>
            <option value="Cash">Cash</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cheque">Cheque</option>
            <option value="NEFT">NEFT</option>
            <option value="RTGS">RTGS</option>
            <option value="IMPS">IMPS</option>
            <option value="DD">Demand Draft</option>
        </select>
        <input type="file" name="screenshot" class="input" accept=".pdf,image/*">
        <div class="md:col-span-5 flex justify-end"><button class="btn-primary">Add Payment</button></div>
    </form>
    @endif
</div>

<div class="card p-0 overflow-hidden">
    <div class="p-4 border-b font-semibold flex items-center justify-between">
        <span>Dispatches</span>
        @if (! $readonly)<span class="text-xs text-slate-500">Upload bill (PDF/JPG/PNG/WEBP, max 10MB)</span>@endif
    </div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
            <tr><th class="text-left px-4 py-2">Date</th><th>Qty</th><th>Due Qty</th><th>Courier</th><th>Tracking</th><th>Bill</th></tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($order->dispatches as $d)
                @php
                    $trackUrl = null;
                    if ($d->tracking_number) {
                        $key = strtolower(trim($d->courier ?? ''));
                        $base = $courierLinks[$key] ?? null;
                        if ($base) $trackUrl = $base . urlencode($d->tracking_number);
                    }
                @endphp
                <tr>
                    <td class="px-4 py-2">{{ $d->dispatch_date?->format('d M Y') }}</td>
                    <td class="text-center">{{ $d->dispatch_qty }}</td>
                    <td class="text-center">{{ $d->due_qty }}</td>
                    <td class="text-center">{{ $d->courier ?? '—' }}</td>
                    <td class="text-center">
                        @php $finalUrl = $d->tracking_url ?: $trackUrl; @endphp
                        @if ($finalUrl)
                            <a target="_blank" class="text-brand-600 hover:underline" href="{{ $finalUrl }}">{{ $d->tracking_number ?: 'Track' }}</a>
                        @elseif ($d->tracking_number)
                            {{ $d->tracking_number }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-center">@if ($d->bill_path)<a target="_blank" class="text-brand-600 hover:underline" href="{{ asset('storage/'.$d->bill_path) }}">View</a>@else — @endif</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-4 text-center text-slate-400">No dispatches yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if (! $readonly)
    <div class="border-t bg-slate-50">
        <form id="dispatch-form-{{ $order->id }}" method="POST" action="{{ route('dealer.orders.dispatches.store', $order) }}" enctype="multipart/form-data" class="p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
            @csrf
            <input type="number" name="dispatch_qty" class="input" placeholder="Qty" required>
            <input type="date" name="dispatch_date" class="input" value="{{ now()->toDateString() }}" required>
            <input name="courier" class="input" placeholder="Courier" list="courier-list-{{ $order->id }}" autocomplete="off">
            <datalist id="courier-list-{{ $order->id }}">
                <option value="Delhivery">
                <option value="BlueDart">
                <option value="DTDC">
                <option value="Xpressbees">
                <option value="Ekart">
                <option value="FedEx">
                <option value="DHL">
                <option value="Shadowfax">
                <option value="India Post">
                <option value="Amazon Logistics">
            </datalist>
            <input name="tracking_number" class="input" placeholder="Tracking #">
            <input type="url" name="tracking_url" class="input md:col-span-2" placeholder="Tracking URL for client (optional, overrides auto-link)">
            <input type="file" name="bill" class="input md:col-span-3" accept=".pdf,image/*">
        </form>
        <div class="px-4 pb-4 flex justify-between">
            <form method="POST" action="{{ route('dealer.orders.dispatches.delivered', $order) }}" onsubmit="return confirm('Mark delivered?')">
                @csrf
                <button class="btn-secondary">Mark Delivered</button>
            </form>
            <button form="dispatch-form-{{ $order->id }}" class="btn-primary">Record Dispatch</button>
        </div>
    </div>
    @endif
</div>
