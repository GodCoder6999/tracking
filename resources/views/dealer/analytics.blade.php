<x-layouts.app heading="Analytics">

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

@php
    $P = ['#2563eb','#7c3aed','#16a34a','#d97706','#dc2626','#0891b2','#be185d','#065f46','#92400e','#1e40af'];
@endphp

{{-- ── FILTERS ── --}}
<div class="card">
    <form method="GET" action="{{ route('dealer.analytics') }}" class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-500 font-medium">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="input">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-500 font-medium">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="input">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-500 font-medium">Client</label>
            <select name="client_id" class="input min-w-[160px]">
                <option value="">All Clients</option>
                @foreach ($clientsList as $cl)
                    <option value="{{ $cl['id'] }}" @selected(request('client_id') == $cl['id'])>{{ $cl['name'] }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-primary">Apply</button>
        @if (request()->hasAny(['from','to','client_id']))
            <a href="{{ route('dealer.analytics') }}" class="btn-secondary">Clear</a>
        @endif
    </form>
</div>

{{-- ── SUMMARY TILES ── --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
    @include('partials.stat', ['label' => 'Total Revenue',  'value' => '₹'.number_format($totals['total_revenue'],  0)])
    @include('partials.stat', ['label' => 'Total Received', 'value' => '₹'.number_format($totals['total_received'], 0)])
    @include('partials.stat', ['label' => 'Pending Due',    'value' => '₹'.number_format($totals['total_due'],      0), 'highlight' => $totals['total_due'] > 0])
    @include('partials.stat', ['label' => 'Orders',         'value' => $totals['total_orders']])
    @include('partials.stat', ['label' => 'My Clients',     'value' => $totals['total_clients']])
</div>

{{-- ── REVENUE TRENDS ── --}}
<x-analytics-section label="Revenue Trends" />
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="card lg:col-span-2">
        <h3 class="font-semibold text-slate-900 mb-4">Monthly Revenue (Last 12 Months)</h3>
        <canvas id="monthlyLineChart" height="110"></canvas>
    </div>
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">Order Pipeline</h3>
        <canvas id="funnelChart" height="180"></canvas>
    </div>
</div>

{{-- ── ORDER STATUS ── --}}
<x-analytics-section label="Order Status" />
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

{{-- ── CLIENT ANALYSIS ── --}}
<x-analytics-section label="Client Analysis" />
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">Revenue by Client</h3>
        <canvas id="clientRevenueBar" height="200"></canvas>
    </div>
    <div class="card">
        <h3 class="font-semibold text-slate-900 mb-4">Due by Client</h3>
        <canvas id="clientDueBar" height="200"></canvas>
    </div>
</div>

{{-- ── TOP PRODUCTS ── --}}
<x-analytics-section label="Products" />
<div class="card">
    <h3 class="font-semibold text-slate-900 mb-4">Top Products — Revenue &amp; Units</h3>
    <canvas id="productsBar" height="120"></canvas>
</div>

{{-- ── CLIENT DATA TABLE ── --}}
<x-analytics-section label="Client Breakdown" />
<div class="card p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="clientTable">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right cursor-pointer hover:text-slate-900" onclick="sortTable(2)">Orders</th>
                    <th class="px-4 py-3 text-right cursor-pointer hover:text-slate-900" onclick="sortTable(3)">Revenue ▾</th>
                    <th class="px-4 py-3 text-right cursor-pointer hover:text-slate-900" onclick="sortTable(4)">Received</th>
                    <th class="px-4 py-3 text-right cursor-pointer hover:text-slate-900" onclick="sortTable(5)">Due</th>
                    <th class="px-4 py-3 text-right cursor-pointer hover:text-slate-900" onclick="sortTable(6)">Collection %</th>
                </tr>
            </thead>
            <tbody class="divide-y" id="clientTableBody">
                @foreach ($clientStats as $c)
                @php
                    $collPct = $c['revenue'] > 0 ? round($c['received'] / $c['revenue'] * 100, 1) : 0;
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $c['name'] }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $c['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $c['active'] ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">{{ $c['orders'] }}</td>
                    <td class="px-4 py-3 text-right">₹{{ number_format($c['revenue'], 0) }}</td>
                    <td class="px-4 py-3 text-right">₹{{ number_format($c['received'], 0) }}</td>
                    <td class="px-4 py-3 text-right {{ $c['due'] > 0 ? 'text-red-600 font-medium' : '' }}">₹{{ number_format($c['due'], 0) }}</td>
                    <td class="px-4 py-3 text-right">
                        <span class="{{ $collPct >= 80 ? 'text-green-700' : ($collPct >= 50 ? 'text-amber-700' : 'text-red-700') }}">
                            {{ $collPct }}%
                        </span>
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
const monthlyLine = @json($monthlyLine);
const clientStats = @json($clientStats);
const topProducts = @json($topProducts);
const funnelLabels = @json(array_keys($funnel));
const funnelData   = @json(array_values($funnel));
const payKeys = @json(array_keys($paymentStatus->toArray()));
const payVals = @json(array_values($paymentStatus->toArray()));
const disKeys = @json(array_keys($dispatchStatus->toArray()));
const disVals = @json(array_values($dispatchStatus->toArray()));

const fmt = (n) => '₹' + parseFloat(n).toLocaleString('en-IN', { maximumFractionDigits: 0 });

// ── Monthly revenue line ──
new Chart(document.getElementById('monthlyLineChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Revenue',
            data: monthlyLine,
            borderColor: '#2563eb',
            backgroundColor: '#2563eb22',
            borderWidth: 2.5,
            fill: true,
            tension: 0.3,
        }],
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: fmt } } },
    },
});

// ── Funnel ──
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

// ── Client revenue bar ──
const sortedByRev = [...clientStats].sort((a, b) => b.revenue - a.revenue);
new Chart(document.getElementById('clientRevenueBar'), {
    type: 'bar',
    data: {
        labels: sortedByRev.map(c => c.name),
        datasets: [{
            data: sortedByRev.map(c => c.revenue),
            backgroundColor: sortedByRev.map((_, i) => P[i % P.length]),
            borderRadius: 4,
        }],
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { callback: fmt } } },
    },
});

// ── Client due bar ──
const sortedByDue = [...clientStats].filter(c => c.due > 0).sort((a, b) => b.due - a.due);
if (sortedByDue.length > 0) {
    new Chart(document.getElementById('clientDueBar'), {
        type: 'bar',
        data: {
            labels: sortedByDue.map(c => c.name),
            datasets: [{
                data: sortedByDue.map(c => c.due),
                backgroundColor: '#dc2626',
                borderRadius: 4,
            }],
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { callback: fmt } } },
        },
    });
} else {
    document.getElementById('clientDueBar').parentElement.innerHTML += '<p class="text-slate-400 text-sm text-center mt-4">No outstanding dues.</p>';
    document.getElementById('clientDueBar').style.display = 'none';
}

// ── Top products ──
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

// ── Table sort ──
function sortTable(col) {
    const tbody = document.getElementById('clientTableBody');
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
