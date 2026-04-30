@php
    $showDealer ??= false;
    $cardRoute  ??= 'dealer.orders.show';
@endphp
<div class="card hover-card p-0 overflow-hidden cursor-pointer group"
     onclick="window.location='{{ route($cardRoute, $o) }}'"
     title="Open {{ $o->order_number }}">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5 border-b border-slate-100"
         style="background:linear-gradient(to right,#f8fafc,#fff)">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 min-w-0">
            <a href="{{ route($cardRoute, $o) }}"
               class="font-bold text-brand-600 hover:text-brand-500 text-sm tracking-tight transition-colors">
                {{ $o->order_number }}
            </a>
            <span class="text-xs text-slate-400">{{ $o->order_date?->format('d M Y') }}</span>
            @if (isset($o->client))
                <span class="text-xs font-medium text-slate-600">{{ $o->client->name }}</span>
            @endif
            @if ($showDealer && isset($o->dealer))
                <span class="text-xs text-slate-400">via {{ $o->dealer->name }}</span>
            @endif
        </div>
        <div class="flex items-center gap-1.5 flex-wrap">
            @include('partials.badges', ['order' => $o])
        </div>
    </div>

    {{-- Product tiles --}}
    @if ($o->items->isNotEmpty())
    <div class="flex flex-wrap gap-2.5 px-5 py-3.5">
        @foreach ($o->items as $item)
        <div class="flex items-center gap-2.5 border border-slate-100 rounded-xl px-3 py-2 bg-white
                    hover:border-brand-200 hover:bg-brand-50/40 transition-all duration-150"
             style="max-width:210px">
            <img src="https://picsum.photos/seed/{{ urlencode(strtolower(trim($item->particulars))) }}/48/48"
                 class="w-12 h-12 rounded-lg object-cover flex-shrink-0"
                 alt="{{ $item->particulars }}"
                 loading="lazy"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="w-12 h-12 rounded-lg bg-brand-100 text-brand-600 font-bold text-xs
                        items-center justify-center flex-shrink-0 hidden">
                {{ strtoupper(substr($item->particulars, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <div class="text-xs font-semibold text-slate-800 truncate" title="{{ $item->particulars }}">
                    {{ $item->particulars }}
                </div>
                <div class="text-xs text-slate-500 mt-0.5">×{{ $item->qty }} &middot; ₹{{ number_format((float)$item->rate, 0) }}</div>
                @if ((float)$item->discount_percent > 0)
                    <div class="text-xs text-emerald-600 font-medium">−{{ number_format((float)$item->discount_percent, 0) }}%</div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Footer --}}
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-slate-100 bg-slate-50/50">
        <div class="flex flex-wrap gap-x-5 gap-y-1 text-sm">
            <span class="text-slate-500">Total
                <strong class="text-slate-900 font-bold ml-1">₹{{ number_format((float) $o->total_amount, 2) }}</strong>
            </span>
            <span class="text-slate-500">Due
                <strong class="{{ (float)$o->due_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }} font-bold ml-1">
                    ₹{{ number_format((float) $o->due_amount, 2) }}
                </strong>
            </span>
        </div>
        <span class="text-xs font-semibold text-brand-500 group-hover:text-brand-600 transition-colors">
            View Order →
        </span>
    </div>
</div>
