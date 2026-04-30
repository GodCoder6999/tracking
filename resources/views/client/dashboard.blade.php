<x-layouts.app heading="My Orders">

    {{-- Filters: date range + order/product search --}}
    <form method="GET" class="card grid grid-cols-2 md:grid-cols-4 gap-3">
        <input type="date" name="from" class="input" value="{{ request('from') }}" placeholder="From date">
        <input type="date" name="to"   class="input" value="{{ request('to') }}"   placeholder="To date">
        <input type="search" id="order-q" name="q" class="input md:col-span-2" value="{{ request('q') }}" placeholder="Search order ID or product name…"
               oninput="liveSearch(this)">
        <div class="flex gap-2 md:col-span-4">
            <button class="btn-primary">Search</button>
            @if(request()->hasAny(['from','to','q']))
                <a href="{{ route('client.dashboard') }}" class="btn-secondary">Clear</a>
            @endif
        </div>
    </form>

    @if(request()->hasAny(['from','to','q']))
    <p class="text-xs text-slate-500 -mt-2">
        Showing orders
        @if(request('q')) matching <strong>"{{ request('q') }}"</strong>@endif
        @if(request('from')) from <strong>{{ \Carbon\Carbon::parse(request('from'))->format('d M Y') }}</strong>@endif
        @if(request('to')) to <strong>{{ \Carbon\Carbon::parse(request('to'))->format('d M Y') }}</strong>@endif
    </p>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @include('partials.stat', ['label' => 'Total Orders',     'value' => $stats['total_orders']])
        @include('partials.stat', ['label' => 'Delivered',        'value' => $stats['delivered']])
        @include('partials.stat', ['label' => 'Pending Dispatch', 'value' => $stats['pending_dispatch']])
        @include('partials.stat', ['label' => 'Pending Due',      'value' => '₹'.number_format($stats['pending_due'], 2)])
    </div>

    @if ($notifications->isNotEmpty())
        <div class="card">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">Updates</h2>
                <form method="POST" action="{{ route('client.notifications.read') }}">@csrf<button class="text-xs text-slate-500 hover:underline">Mark all read</button></form>
            </div>
            <ul class="space-y-2 text-sm">
                @foreach ($notifications as $n)
                    <li class="flex justify-between border-b last:border-0 pb-2">
                        <span>{{ $n->data['order_number'] ?? '' }} — {{ $n->data['message'] ?? '' }}</span>
                        <span class="text-slate-400 text-xs">{{ $n->created_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($orders as $o)
            @include('partials.order-card', ['o' => $o, 'cardRoute' => 'client.orders.show', 'showDealer' => true])
        @empty
            <div class="card text-center text-slate-400 py-8">No orders.</div>
        @endforelse
    </div>
    <div>{{ $orders->links() }}</div>
    <script>
        var _ost;
        function liveSearch(el) { clearTimeout(_ost); _ost = setTimeout(function(){ el.form.submit(); }, 350); }
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('order-q');
            if (el && el.value) { el.focus(); var l = el.value.length; el.setSelectionRange(l, l); }
        });
    </script>
</x-layouts.app>
