<x-layouts.app heading="Seller Dashboard">

    {{-- ── FILTERS ────────────────────────────────────────────────── --}}
    <form method="GET" class="card space-y-3" id="dashboard-filter">

        {{-- Row 1: Date presets + custom range --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <select id="preset" class="input text-sm">
                <option value="">Custom Range</option>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="last7">Last 7 Days</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="ytd">Year to Date</option>
            </select>
            <input type="date" name="from" id="from" class="input" value="{{ request('from') }}" placeholder="From date">
            <input type="date" name="to"   id="to"   class="input" value="{{ request('to') }}"   placeholder="To date">
            <div></div>
        </div>

        {{-- Row 2: Client name search + Order # search --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="relative">
                <input type="text" id="client_search" class="input w-full"
                       placeholder="Search client by name or email…"
                       value="{{ request('search_label') }}"
                       autocomplete="off">
                <input type="hidden" name="client_id"    id="client_id_field"    value="{{ request('client_id') }}">
                <input type="hidden" name="search_label" id="search_label_field" value="{{ request('search_label') }}">
                <div id="suggestions"
                     class="absolute z-50 w-full bg-white border border-slate-200 rounded-lg shadow-lg hidden max-h-52 overflow-y-auto mt-1">
                </div>
            </div>
            <input type="text" name="order_number" class="input"
                   placeholder="Search order number…"
                   value="{{ request('order_number') }}">
        </div>

        {{-- Row 3: Status filters + product + apply --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <select name="payment_status" class="input text-sm">
                <option value="">All Payment Status</option>
                <option value="paid"    @selected(request('payment_status') === 'paid')>Fully Paid</option>
                <option value="partial" @selected(request('payment_status') === 'partial')>Partially Paid</option>
                <option value="unpaid"  @selected(request('payment_status') === 'unpaid')>Unpaid / Pending</option>
                <option value="overdue" @selected(request('payment_status') === 'overdue')>Overdue (30 d+)</option>
            </select>
            <select name="dispatch_status" class="input text-sm">
                <option value="">All Order Status</option>
                <option value="pending"   @selected(request('dispatch_status') === 'pending')>Pending Dispatch</option>
                <option value="partial"   @selected(request('dispatch_status') === 'partial')>Partially Shipped</option>
                <option value="sent"      @selected(request('dispatch_status') === 'sent')>Shipped</option>
                <option value="delivered" @selected(request('dispatch_status') === 'delivered')>Delivered</option>
            </select>
            <select name="product_id" class="input text-sm">
                <option value="">All Products</option>
                @foreach ($products as $p)
                    <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn-primary flex-1">Apply</button>
                @if(request()->hasAny(['from','to','client_id','search_label','order_number','payment_status','dispatch_status','product_id']))
                    <a href="{{ route('dealer.dashboard') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Active filter summary --}}
    @php $anyFilter = request()->hasAny(['from','to','client_id','search_label','order_number','payment_status','dispatch_status','product_id']); @endphp
    @if($anyFilter)
    <p class="text-xs text-slate-500 -mt-2">
        Filtered:
        @if(request('from')) from <strong>{{ \Carbon\Carbon::parse(request('from'))->format('d M Y') }}</strong>@endif
        @if(request('to')) to <strong>{{ \Carbon\Carbon::parse(request('to'))->format('d M Y') }}</strong>@endif
        @if(request('search_label')) &middot; client: <strong>{{ request('search_label') }}</strong>@endif
        @if(request('order_number')) &middot; order: <strong>{{ request('order_number') }}</strong>@endif
        @if(request('payment_status')) &middot; payment: <strong>{{ ucfirst(request('payment_status')) }}</strong>@endif
        @if(request('dispatch_status')) &middot; status: <strong>{{ ucfirst(request('dispatch_status')) }}</strong>@endif
        @if(request('product_id')) &middot; product: <strong>{{ $products->firstWhere('id', request('product_id'))?->name }}</strong>@endif
        &middot; <strong>{{ $stats['orders'] }}</strong> order(s) found
    </p>
    @endif

    {{-- ── STAT CARDS ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        @include('partials.stat', ['label' => $anyFilter ? 'Clients (filtered)' : 'My Clients', 'value' => $stats['clients']])
        @include('partials.stat', ['label' => $anyFilter ? 'Orders (filtered)'  : 'Orders',     'value' => $stats['orders']])
        @include('partials.stat', ['label' => 'Revenue',          'value' => '₹'.number_format($stats['revenue'], 2)])
        @include('partials.stat', ['label' => 'Received',         'value' => '₹'.number_format($stats['received'], 2)])
        @include('partials.stat', ['label' => 'Payment Due',      'value' => '₹'.number_format($stats['due'], 2)])
        @include('partials.stat', ['label' => 'Pending Dispatch', 'value' => $stats['pending_dispatch']])
    </div>

    {{-- ── RECENT / FILTERED ORDERS ───────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-slate-900">
            @if($anyFilter)
                Filtered Orders <span class="text-slate-400 font-normal text-sm">({{ $recent->count() }})</span>
            @else
                Recent Orders
            @endif
        </h2>
        <a href="{{ route('dealer.orders.create') }}" class="btn-primary">+ New Order</a>
    </div>

    <div class="space-y-4">
        @forelse ($recent as $o)
            @include('partials.order-card', ['o' => $o, 'cardRoute' => 'dealer.orders.show'])
        @empty
            <div class="card text-center text-slate-400 py-8">No orders match the selected filters.</div>
        @endforelse
    </div>

    @if ($recent->isNotEmpty() && !$anyFilter)
    <div class="text-center">
        <a href="{{ route('dealer.orders.index') }}" class="text-sm text-brand-600 hover:underline">View all orders →</a>
    </div>
    @endif

    {{-- ── SCRIPTS ─────────────────────────────────────────────────── --}}
    <script>
    (function () {
        // ── Date presets ──────────────────────────────────────────────
        var fromEl = document.getElementById('from');
        var toEl   = document.getElementById('to');
        var fmt    = function(d) { return d.toISOString().split('T')[0]; };

        document.getElementById('preset').addEventListener('change', function () {
            var today = new Date(); today.setHours(0,0,0,0);
            switch (this.value) {
                case 'today':      fromEl.value = fmt(today); toEl.value = fmt(today); break;
                case 'yesterday':  { var y = new Date(today); y.setDate(y.getDate()-1); fromEl.value = fmt(y); toEl.value = fmt(y); break; }
                case 'last7':      { var s = new Date(today); s.setDate(s.getDate()-6); fromEl.value = fmt(s); toEl.value = fmt(today); break; }
                case 'this_month': fromEl.value = fmt(new Date(today.getFullYear(), today.getMonth(), 1)); toEl.value = fmt(today); break;
                case 'last_month': { var lm = new Date(today.getFullYear(), today.getMonth()-1, 1); var lme = new Date(today.getFullYear(), today.getMonth(), 0); fromEl.value = fmt(lm); toEl.value = fmt(lme); break; }
                case 'ytd':        fromEl.value = fmt(new Date(today.getFullYear(), 0, 1)); toEl.value = fmt(today); break;
            }
        });

        // ── Prefetch client autocomplete ──────────────────────────────
        var clientsData = @json($clients->map(fn($c) => ['id' => $c->id, 'label' => $c->name, 'sub' => $c->email]));

        var clientSearch = document.getElementById('client_search');
        var suggestBox   = document.getElementById('suggestions');
        var clientField  = document.getElementById('client_id_field');
        var labelField   = document.getElementById('search_label_field');

        function filterLocal(q) {
            q = q.toLowerCase();
            return clientsData.filter(function(item) {
                return item.label.toLowerCase().indexOf(q) !== -1 || item.sub.toLowerCase().indexOf(q) !== -1;
            }).slice(0, 8);
        }

        function renderSuggestions(results) {
            if (!results.length) { suggestBox.classList.add('hidden'); return; }
            suggestBox.innerHTML = results.map(function(item) {
                return '<div class="px-3 py-2 hover:bg-slate-100 cursor-pointer text-sm border-b last:border-0"' +
                       ' data-id="' + item.id + '" data-label="' + item.label.replace(/"/g, '&quot;') + '">' +
                       '<span class="font-medium">' + item.label + '</span>' +
                       (item.sub ? '<span class="text-slate-400 ml-2 text-xs">' + item.sub + '</span>' : '') +
                       '</div>';
            }).join('');
            suggestBox.classList.remove('hidden');
        }

        clientSearch.addEventListener('input', function () {
            var q = this.value.trim();
            if (q.length < 2) { suggestBox.classList.add('hidden'); return; }
            renderSuggestions(filterLocal(q));
        });

        suggestBox.addEventListener('click', function (e) {
            var item = e.target.closest('[data-id]');
            if (!item) return;
            clientSearch.value = item.dataset.label;
            labelField.value   = item.dataset.label;
            clientField.value  = item.dataset.id;
            suggestBox.classList.add('hidden');
        });

        // Clear client_id if user clears the text input manually
        clientSearch.addEventListener('change', function () {
            if (!this.value.trim()) {
                clientField.value = '';
                labelField.value  = '';
            }
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#client_search') && !e.target.closest('#suggestions')) {
                suggestBox.classList.add('hidden');
            }
        });
    })();
    </script>
</x-layouts.app>
