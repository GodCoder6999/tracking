<x-layouts.app heading="My Orders">
    <div class="flex justify-end">
        <a href="{{ route('dealer.orders.create') }}" class="btn-primary">+ New Order</a>
    </div>

    <form method="GET" class="card grid grid-cols-2 md:grid-cols-6 gap-3">
        <input name="q" placeholder="Order #" class="input" value="{{ request('q') }}">
        <select name="payment_status" class="input">
            <option value="">Any payment</option>
            @foreach (['unpaid', 'partial', 'paid'] as $s)<option value="{{ $s }}" @selected(request('payment_status')===$s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <select name="dispatch_status" class="input">
            <option value="">Any dispatch</option>
            @foreach (['pending', 'partial', 'sent', 'delivered'] as $s)<option value="{{ $s }}" @selected(request('dispatch_status')===$s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <input type="date" name="from" class="input" value="{{ request('from') }}" placeholder="From date">
        <input type="date" name="to"   class="input" value="{{ request('to') }}"   placeholder="To date">
        <div class="flex gap-2">
            <button class="btn-primary flex-1">Filter</button>
            @if(request()->hasAny(['q','payment_status','dispatch_status','from','to']))
                <a href="{{ route('dealer.orders.index') }}" class="btn-secondary">Clear</a>
            @endif
        </div>
    </form>

    {{-- Ledger Download --}}
    <div class="card" x-data="{ open: false }">
        <button type="button" @click="open = !open"
                class="flex items-center gap-2 w-full text-left font-semibold text-slate-700 text-sm">
            <svg class="w-4 h-4 text-brand-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            Download Ledger (PDF)
            <svg class="w-3.5 h-3.5 ml-auto transition-transform duration-200" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-transition class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs text-slate-500 mb-3">Select a date range to download your order ledger as a PDF (grouped month-wise).</p>
            <form method="GET" action="{{ route('dealer.ledger.download') }}" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">From Date</label>
                    <input type="date" name="from" class="input" value="{{ request('from') }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">To Date</label>
                    <input type="date" name="to" class="input" value="{{ request('to') }}">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($orders as $o)
            @include('partials.order-card', ['o' => $o, 'cardRoute' => 'dealer.orders.show'])
        @empty
            <div class="card text-center text-slate-400 py-8">No orders.</div>
        @endforelse
    </div>
    <div>{{ $orders->links() }}</div>
</x-layouts.app>
