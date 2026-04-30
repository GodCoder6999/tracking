<x-layouts.app heading="Owner Dashboard">

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

        {{-- Row 2: Universal search --}}
        <div class="grid grid-cols-4 gap-3">
            @php
                $activeSearchType = request('client_id') ? 'client' : (request('order_number') ? 'order' : 'dealer');
            @endphp
            <select name="search_type" id="search_type" class="input text-sm">
                <option value="dealer" @selected($activeSearchType === 'dealer')>Search Dealer</option>
                <option value="client" @selected($activeSearchType === 'client')>Search Client</option>
                <option value="order"  @selected($activeSearchType === 'order')>Search Order #</option>
            </select>
            <div class="col-span-3 relative">
                <input type="text" id="search_input" class="input w-full"
                       placeholder="Type name or email…"
                       value="{{ request('search_label') }}"
                       autocomplete="off">
                <input type="hidden" name="dealer_id"    id="dealer_id_field"    value="{{ request('dealer_id') }}">
                <input type="hidden" name="client_id"    id="client_id_field"    value="{{ request('client_id') }}">
                <input type="hidden" name="order_number" id="order_number_field" value="{{ request('order_number') }}">
                <input type="hidden" name="search_label" id="search_label_field" value="{{ request('search_label') }}">
                <div id="suggestions"
                     class="absolute z-50 w-full bg-white border border-slate-200 rounded-lg shadow-lg hidden max-h-52 overflow-y-auto mt-1">
                </div>
            </div>
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
                @if(request()->hasAny(['from','to','dealer_id','client_id','order_number','payment_status','dispatch_status','product_id']))
                    <a href="{{ route('owner.dashboard') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Active filter summary --}}
    @if(request()->hasAny(['from','to','dealer_id','client_id','order_number','payment_status','dispatch_status','product_id']))
    <p class="text-xs text-slate-500 -mt-2">
        Filtered:
        @if(request('from')) from <strong>{{ \Carbon\Carbon::parse(request('from'))->format('d M Y') }}</strong>@endif
        @if(request('to')) to <strong>{{ \Carbon\Carbon::parse(request('to'))->format('d M Y') }}</strong>@endif
        @if(request('search_label')) &middot; <strong>{{ request('search_label') }}</strong>@endif
        @if(request('payment_status')) &middot; payment: <strong>{{ ucfirst(request('payment_status')) }}</strong>@endif
        @if(request('dispatch_status')) &middot; dispatch: <strong>{{ ucfirst(request('dispatch_status')) }}</strong>@endif
        @if(request('product_id')) &middot; product: <strong>{{ $products->firstWhere('id', request('product_id'))?->name }}</strong>@endif
    </p>
    @endif

    {{-- ── STAT CARDS ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @include('partials.stat', ['label' => 'Dealers',          'value' => $stats['total_dealers']])
        @include('partials.stat', ['label' => 'Clients',          'value' => $stats['total_clients']])
        @include('partials.stat', ['label' => 'Orders',           'value' => $stats['total_orders'], 'sub' => $stats['orders_today'].' today'])
        @include('partials.stat', ['label' => 'Revenue',          'value' => '₹'.number_format($stats['total_revenue'], 2)])
        @include('partials.stat', ['label' => 'Received',         'value' => '₹'.number_format($stats['total_received'], 2)])
        @include('partials.stat', ['label' => 'Pending Due',      'value' => '₹'.number_format($stats['pending_due'], 2)])
        @include('partials.stat', ['label' => 'Pending Dispatch', 'value' => $stats['pending_dispatch']])
    </div>

    {{-- ── CHARTS + TOP DEALERS ───────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card lg:col-span-2">
            <h2 class="font-semibold text-slate-900 mb-4">Monthly Revenue</h2>
            <canvas id="monthlyChart" height="120"></canvas>
        </div>

        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-slate-900">Top Dealers</h2>
                <div class="flex gap-1 text-xs">
                    <a href="{{ request()->fullUrlWithQuery(['top_sort' => 'revenue']) }}"
                       class="px-2 py-1 rounded {{ $topSort === 'revenue' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Revenue</a>
                    <a href="{{ request()->fullUrlWithQuery(['top_sort' => 'orders']) }}"
                       class="px-2 py-1 rounded {{ $topSort === 'orders' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Orders</a>
                    <a href="{{ request()->fullUrlWithQuery(['top_sort' => 'pending_dues']) }}"
                       class="px-2 py-1 rounded {{ $topSort === 'pending_dues' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Dues</a>
                </div>
            </div>
            <ul class="space-y-3 text-sm">
                @forelse ($topDealers as $d)
                    <li class="flex justify-between">
                        <span>{{ $d->name }}</span>
                        @if($topSort === 'orders')
                            <span class="text-slate-600">{{ $d->orders_as_dealer_count }} orders</span>
                        @elseif($topSort === 'pending_dues')
                            <span class="text-red-600 font-medium">₹{{ number_format((float) $d->pending_dues, 0) }}</span>
                        @else
                            <span class="text-slate-600">₹{{ number_format((float) $d->revenue, 0) }}</span>
                        @endif
                    </li>
                @empty
                    <li class="text-slate-400">No dealer data yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- ── RECENT ORDERS ───────────────────────────────────────────── --}}
    <h2 class="font-semibold text-slate-900">Recent Orders</h2>
    <div class="space-y-4">
        @forelse ($recentOrders as $o)
            @include('partials.order-card', ['o' => $o, 'cardRoute' => 'owner.orders.show', 'showDealer' => true])
        @empty
            <div class="card text-center text-slate-400 py-8">No orders yet.</div>
        @endforelse
    </div>
    @if ($recentOrders->isNotEmpty())
    <div class="text-center">
        <a href="{{ route('owner.orders.index') }}" class="text-sm text-brand-600 hover:underline">View all orders →</a>
    </div>
    @endif

    {{-- ── SCRIPTS ─────────────────────────────────────────────────── --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        // Monthly chart
        var labels  = @json($monthly->pluck('month'));
        var revenue = @json($monthly->pluck('revenue'));
        if (labels.length) {
            new Chart(document.getElementById('monthlyChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{ label: 'Revenue ₹', data: revenue, backgroundColor: '#2563eb' }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });
        }

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

        // ── Prefetch autocomplete ─────────────────────────────────────
        var dealersData = @json($dealers->map(fn($d) => ['id' => $d->id, 'label' => $d->name, 'sub' => $d->email]));
        var clientsData = @json($clients->map(fn($c) => ['id' => $c->id, 'label' => $c->name, 'sub' => $c->email]));

        var searchInput = document.getElementById('search_input');
        var searchType  = document.getElementById('search_type');
        var suggestBox  = document.getElementById('suggestions');
        var dealerField = document.getElementById('dealer_id_field');
        var clientField = document.getElementById('client_id_field');
        var orderField  = document.getElementById('order_number_field');
        var labelField  = document.getElementById('search_label_field');

        function filterLocal(arr, q) {
            q = q.toLowerCase();
            return arr.filter(function(item) {
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

        searchInput.addEventListener('input', function () {
            var q    = this.value.trim();
            var type = searchType.value;
            if (type === 'order') { suggestBox.classList.add('hidden'); return; }
            if (q.length < 2)    { suggestBox.classList.add('hidden'); return; }
            renderSuggestions(filterLocal(type === 'client' ? clientsData : dealersData, q));
        });

        suggestBox.addEventListener('click', function (e) {
            var item = e.target.closest('[data-id]');
            if (!item) return;
            searchInput.value = item.dataset.label;
            labelField.value  = item.dataset.label;
            dealerField.value = clientField.value = orderField.value = '';
            if (searchType.value === 'dealer')      dealerField.value = item.dataset.id;
            else if (searchType.value === 'client') clientField.value = item.dataset.id;
            suggestBox.classList.add('hidden');
        });

        // When type=order, search_input maps directly to order_number on submit
        document.getElementById('dashboard-filter').addEventListener('submit', function () {
            if (searchType.value === 'order') {
                orderField.value  = searchInput.value.trim();
                dealerField.value = clientField.value = labelField.value = '';
            }
        });

        searchType.addEventListener('change', function () {
            searchInput.value = '';
            labelField.value  = dealerField.value = clientField.value = orderField.value = '';
            suggestBox.classList.add('hidden');
            searchInput.placeholder = this.value === 'order' ? 'Type order number…' : 'Type name or email…';
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#search_input') && !e.target.closest('#suggestions')) {
                suggestBox.classList.add('hidden');
            }
        });
    })();
    </script>
</x-layouts.app>
