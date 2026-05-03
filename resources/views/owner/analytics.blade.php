<x-layouts.app heading="Analytics">

{{-- Chart.js + Treemap plugin (UMD, no ESM conflict) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap@3.1.0/dist/chartjs-chart-treemap.min.js"></script>

@php
    $P = ['#2563eb','#7c3aed','#16a34a','#d97706','#dc2626','#0891b2','#be185d','#065f46','#92400e','#1e40af'];
@endphp

{{-- ── FILTERS ── --}}
<div class="card">
    <form method="GET" action="{{ route('owner.analytics') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-500 font-medium">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="input">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-500 font-medium">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="input">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-500 font-medium">Seller</label>
            <select name="dealer_id" class="input min-w-[160px]">
                <option value="">All Sellers</option>
                @foreach ($dealersList as $dl)
                    <option value="{{ $dl->id }}" @selected(request('dealer_id') == $dl->id)>{{ $dl->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-primary">Apply</button>
        @if (request()->hasAny(['from','to','dealer_id']))
            <a href="{{ route('owner.analytics') }}" class="btn-secondary">Clear</a>
        @endif
    </form>
</div>

{{-- ── SUMMARY TILES ── --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
    @include('partials.stat', ['label' => 'Total Revenue',  'value' => '₹'.number_format($totals['total_revenue'],  0)])
    @include('partials.stat', ['label' => 'Total Received', 'value' => '₹'.number_format($totals['total_received'], 0)])
    @include('partials.stat', ['label' => 'Payment Due',    'value' => '₹'.number_format($totals['total_due'],      0), 'highlight' => $totals['total_due'] > 0])
    @include('partials.stat', ['label' => 'Orders',         'value' => $totals['total_orders']])
    @include('partials.stat', ['label' => 'Sellers',        'value' => $totals['total_dealers']])
    @include('partials.stat', ['label' => 'Clients',        'value' => $totals['total_clients']])
</div>

{{-- ── REVENUE TRENDS ── --}}
<x-analytics-section label="Revenue Trends" />
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="card lg:col-span-2">
        <h3 class="font-semibold text-slate-900 mb-4">Network vs Top Sellers — Monthly Revenue</h3>
        <canvas id="multiLineChart" height="110"></canvas>
    </div>
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">Order Pipeline (Funnel)</h3>
        <canvas id="funnelChart" height="180"></canvas>
    </div>
</div>

{{-- ── DEALER RANKINGS ── --}}
<x-analytics-section label="Seller Rankings" />
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">By Revenue</h3>
        <canvas id="dealerRevenueBar" height="200"></canvas>
    </div>
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">By Order Volume</h3>
        <canvas id="dealerOrdersBar" height="200"></canvas>
    </div>
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">By Client Count</h3>
        <canvas id="dealerClientsBar" height="200"></canvas>
    </div>
</div>

{{-- ── DEALER EFFICIENCY + CLIENT STATUS ── --}}
<x-analytics-section label="Seller Efficiency & Client Status" />
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-1">Scatter — Clients vs Avg Revenue per Client</h3>
        <p class="text-xs text-slate-400 mb-3">Each dot = one seller. Hover for details.</p>
        <canvas id="scatterChart" height="180"></canvas>
    </div>
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-1">Stacked — Client Status per Seller</h3>
        <p class="text-xs text-slate-400 mb-3">Active (has orders) · Onboarding (no orders yet) · Churned (inactive)</p>
        <canvas id="stackedClientChart" height="180"></canvas>
    </div>
</div>

{{-- ── ORDER STATUS ── --}}
<x-analytics-section label="Order Status Breakdown" />
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">Payment Status</h3>
        <div class="flex items-center gap-8">
            <canvas id="paymentPie" style="max-width:180px;max-height:180px"></canvas>
            <ul class="space-y-2 text-sm">
                @foreach (['paid' => ['Paid','#16a34a'], 'partial' => ['Partial','#d97706'], 'unpaid' => ['Unpaid','#dc2626']] as $k => [$lbl, $clr])
                    @if (isset($paymentStatus[$k]))
                    <li class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full inline-block" style="background:{{ $clr }}"></span>
                        {{ $lbl }}: <strong>{{ $paymentStatus[$k] }}</strong>
                    </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">Dispatch Status</h3>
        <div class="flex items-center gap-8">
            <canvas id="dispatchPie" style="max-width:180px;max-height:180px"></canvas>
            <ul class="space-y-2 text-sm">
                @foreach (['pending' => ['Pending','#94a3b8'], 'partial' => ['Partial','#d97706'], 'sent' => ['Sent','#2563eb'], 'delivered' => ['Delivered','#16a34a']] as $k => [$lbl, $clr])
                    @if (isset($dispatchStatus[$k]))
                    <li class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full inline-block" style="background:{{ $clr }}"></span>
                        {{ $lbl }}: <strong>{{ $dispatchStatus[$k] }}</strong>
                    </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</div>

{{-- ── MARKET SHARE ── --}}
<x-analytics-section label="Market Share & Products" />
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">Revenue Treemap — Seller Market Share</h3>
        <canvas id="treemapChart" height="220"></canvas>
    </div>
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">Top Products — Revenue &amp; Units</h3>
        <canvas id="productsBar" height="220"></canvas>
    </div>
</div>

{{-- ── DEALER DATA TABLE ── --}}
<x-analytics-section label="Seller Breakdown" />
<div class="card p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="dealerTable">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-3">Seller</th>
                    <th class="px-4 py-3 text-right cursor-pointer hover:text-slate-900" onclick="sortTable(1)">Revenue ▾</th>
                    <th class="px-4 py-3 text-right cursor-pointer hover:text-slate-900" onclick="sortTable(2)">Orders</th>
                    <th class="px-4 py-3 text-right cursor-pointer hover:text-slate-900" onclick="sortTable(3)">Clients</th>
                    <th class="px-4 py-3 text-right cursor-pointer hover:text-slate-900" onclick="sortTable(4)">Avg Order ₹</th>
                    <th class="px-4 py-3 text-right cursor-pointer hover:text-slate-900" onclick="sortTable(5)">Collection %</th>
                    <th class="px-4 py-3 text-center">6-mo Trend</th>
                </tr>
            </thead>
            <tbody class="divide-y" id="dealerTableBody">
                @foreach ($dealerRankings as $d)
                @php
                    $rev     = (float) $d->revenue;
                    $orders  = (int)   $d->order_count;
                    $clients = (int)   $d->client_count;
                    $avg     = $orders > 0 ? $rev / $orders : 0;
                    $totRec  = (float) $d->received_sum;
                    $collPct = $rev > 0 ? round($totRec / $rev * 100, 1) : 0;
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $d->name }}</td>
                    <td class="px-4 py-3 text-right">₹{{ number_format($rev, 0) }}</td>
                    <td class="px-4 py-3 text-right">{{ $orders }}</td>
                    <td class="px-4 py-3 text-right">{{ $clients }}</td>
                    <td class="px-4 py-3 text-right">₹{{ number_format($avg, 0) }}</td>
                    <td class="px-4 py-3 text-right">
                        <span class="{{ $collPct >= 80 ? 'text-green-700' : ($collPct >= 50 ? 'text-amber-700' : 'text-red-700') }}">
                            {{ $collPct }}%
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <canvas class="sparkline-canvas inline-block" data-dealer="{{ $d->id }}" width="80" height="30"></canvas>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
const P = @json($P);
const months     = @json($months);
const spark6     = @json($spark6);
const sparklines = @json($sparklines);

const dealerRankings       = @json($dealerRankingsJs);
const dealerLines          = @json($dealerLines);
const networkLine          = @json($networkLine);
const clientStatusByDealer = @json($clientStatusByDealer);
const scatterData          = @json($scatterData);
const topProducts          = @json($topProducts);
const funnelLabels         = @json(array_keys($funnel));
const funnelData           = @json(array_values($funnel));

const payKeys   = @json(array_keys($paymentStatus->toArray()));
const payVals   = @json(array_values($paymentStatus->toArray()));
const disKeys   = @json(array_keys($dispatchStatus->toArray()));
const disVals   = @json(array_values($dispatchStatus->toArray()));

const fmt = (n) => '₹' + parseFloat(n).toLocaleString('en-IN', { maximumFractionDigits: 0 });

// ── Multi-line: Network + Top 5 Dealers ──
new Chart(document.getElementById('multiLineChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [
            {
                label: 'Network Total',
                data: networkLine,
                borderColor: '#0f172a',
                backgroundColor: '#0f172a22',
                borderWidth: 2.5,
                fill: true,
                tension: 0.3,
            },
            ...dealerLines.map((dl, i) => ({
                label: dl.name,
                data: dl.data,
                borderColor: P[i + 1],
                backgroundColor: 'transparent',
                borderWidth: 1.5,
                tension: 0.3,
                borderDash: [4, 3],
            })),
        ],
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, ticks: { callback: fmt } } },
    },
});

// ── Funnel (horizontal bar, decreasing) ──
new Chart(document.getElementById('funnelChart'), {
    type: 'bar',
    data: {
        labels: funnelLabels,
        datasets: [{
            data: funnelData,
            backgroundColor: ['#1d4ed8','#2563eb','#3b82f6','#16a34a','#15803d'],
            borderRadius: 4,
        }],
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } },
    },
});

// ── Horizontal bars: dealer rankings ──
function hBar(id, dataKey, labelFn) {
    const sorted = [...dealerRankings].sort((a, b) => b[dataKey] - a[dataKey]);
    new Chart(document.getElementById(id), {
        type: 'bar',
        data: {
            labels: sorted.map(d => d.name),
            datasets: [{
                data: sorted.map(d => d[dataKey]),
                backgroundColor: sorted.map((_, i) => P[i % P.length]),
                borderRadius: 4,
            }],
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { callback: labelFn } } },
        },
    });
}
hBar('dealerRevenueBar',  'revenue',      fmt);
hBar('dealerOrdersBar',   'order_count',  (v) => v);
hBar('dealerClientsBar',  'client_count', (v) => v);

// ── Scatter: efficiency ──
new Chart(document.getElementById('scatterChart'), {
    type: 'scatter',
    data: {
        datasets: [{
            data: scatterData,
            backgroundColor: scatterData.map((_, i) => P[i % P.length] + 'cc'),
            pointRadius: 9,
            pointHoverRadius: 12,
        }],
    },
    options: {
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (ctx) => {
                        const d = ctx.raw;
                        return [`${d.label}`, `Clients: ${d.x}`, `Avg Rev/Client: ${fmt(d.y)}`, `Orders: ${d.orders}`];
                    },
                },
            },
        },
        scales: {
            x: { title: { display: true, text: 'Number of Clients' }, beginAtZero: true },
            y: { title: { display: true, text: 'Avg Revenue per Client (₹)' }, beginAtZero: true, ticks: { callback: fmt } },
        },
    },
});

// ── Stacked column: client status per dealer ──
new Chart(document.getElementById('stackedClientChart'), {
    type: 'bar',
    data: {
        labels: clientStatusByDealer.map(d => d.name),
        datasets: [
            { label: 'Active',     data: clientStatusByDealer.map(d => d.active),     backgroundColor: '#16a34a', borderRadius: 2 },
            { label: 'Onboarding', data: clientStatusByDealer.map(d => d.onboarding), backgroundColor: '#2563eb', borderRadius: 2 },
            { label: 'Churned',    data: clientStatusByDealer.map(d => d.churned),    backgroundColor: '#dc2626', borderRadius: 2 },
        ],
    },
    options: {
        plugins: { legend: { position: 'bottom' } },
        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
    },
});

// ── Payment donut ──
const payColorMap = { paid: '#16a34a', partial: '#d97706', unpaid: '#dc2626' };
new Chart(document.getElementById('paymentPie'), {
    type: 'doughnut',
    data: {
        labels: payKeys,
        datasets: [{ data: payVals, backgroundColor: payKeys.map(k => payColorMap[k] ?? '#94a3b8') }],
    },
    options: { plugins: { legend: { display: false } } },
});

// ── Dispatch donut ──
const disColorMap = { pending: '#94a3b8', partial: '#d97706', sent: '#2563eb', delivered: '#16a34a' };
new Chart(document.getElementById('dispatchPie'), {
    type: 'doughnut',
    data: {
        labels: disKeys,
        datasets: [{ data: disVals, backgroundColor: disKeys.map(k => disColorMap[k] ?? '#94a3b8') }],
    },
    options: { plugins: { legend: { display: false } } },
});

// ── Treemap: dealer market share ──
const treemapData = dealerRankings
    .filter(d => parseFloat(d.revenue) > 0)
    .map(d => ({ value: parseFloat(d.revenue), dealer: d.name }));

if (treemapData.length > 0) {
    new Chart(document.getElementById('treemapChart'), {
        type: 'treemap',
        data: {
            datasets: [{
                label: 'Revenue',
                tree: treemapData,
                key: 'value',
                groups: ['dealer'],
                backgroundColor: (ctx) => P[ctx.dataIndex % P.length] + 'cc',
                borderColor: (ctx) => P[ctx.dataIndex % P.length],
                borderWidth: 2,
                spacing: 3,
                labels: {
                    display: true,
                    formatter: (ctx) => [ctx.raw.g, fmt(ctx.raw.v)],
                    color: '#fff',
                    font: [{ size: 12, weight: 'bold' }, { size: 10 }],
                },
            }],
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => items[0].raw.g,
                        label: (item)  => fmt(item.raw.v),
                    },
                },
            },
        },
    });
} else {
    document.getElementById('treemapChart').parentElement.innerHTML += '<p class="text-slate-400 text-sm mt-4 text-center">No revenue data yet.</p>';
    document.getElementById('treemapChart').style.display = 'none';
}

// ── Top products: horizontal bar ──
new Chart(document.getElementById('productsBar'), {
    type: 'bar',
    data: {
        labels: topProducts.map(p => p.particulars),
        datasets: [
            {
                label: 'Revenue ₹',
                data: topProducts.map(p => parseFloat(p.revenue || 0)),
                backgroundColor: '#2563eb',
                yAxisID: 'y',
                borderRadius: 3,
            },
            {
                label: 'Units Sold',
                data: topProducts.map(p => parseInt(p.units || 0)),
                backgroundColor: '#d97706',
                yAxisID: 'y2',
                borderRadius: 3,
            },
        ],
    },
    options: {
        plugins: { legend: { position: 'bottom' } },
        scales: {
            y:  { type: 'linear', position: 'left',  beginAtZero: true, ticks: { callback: fmt } },
            y2: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } },
        },
    },
});

// ── Sparklines in table ──
document.querySelectorAll('.sparkline-canvas').forEach(canvas => {
    const id   = parseInt(canvas.dataset.dealer);
    const data = sparklines[id] || [];
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: spark6,
            datasets: [{
                data,
                borderColor: '#2563eb',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: true,
                backgroundColor: '#2563eb18',
            }],
        },
        options: {
            animation: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales:  { x: { display: false }, y: { display: false, beginAtZero: true } },
        },
    });
});

// ── Table sort ──
function sortTable(col) {
    const tbody = document.getElementById('dealerTableBody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    const asc   = tbody.dataset.sortCol == col && tbody.dataset.sortDir === 'asc';
    tbody.dataset.sortCol = col;
    tbody.dataset.sortDir = asc ? 'desc' : 'asc';
    rows.sort((a, b) => {
        const av = parseFloat(a.cells[col].textContent.replace(/[₹,%\s]/g, '')) || 0;
        const bv = parseFloat(b.cells[col].textContent.replace(/[₹,%\s]/g, '')) || 0;
        return asc ? av - bv : bv - av;
    });
    rows.forEach(r => tbody.appendChild(r));
}
</script>
</x-layouts.app>
