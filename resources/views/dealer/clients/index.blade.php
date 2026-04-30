<x-layouts.app heading="My Clients">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex gap-2 flex-1 max-w-sm" id="client-search-form">
            <input type="search" id="client-q" name="q" value="{{ request('q') }}" class="input" placeholder="Search name, email, phone…"
                   oninput="liveSearch(this)">
            <button class="btn-primary">Search</button>
            @if(request('q'))<a href="{{ route('dealer.clients.index') }}" class="btn-secondary">Clear</a>@endif
        </form>
        <script>
            var _cst;
            function liveSearch(el) { clearTimeout(_cst); _cst = setTimeout(function(){ el.form.submit(); }, 350); }
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('client-q');
                if (el && el.value) { el.focus(); var l = el.value.length; el.setSelectionRange(l, l); }
            });
        </script>
        <div class="flex gap-2">
            <a href="{{ route('dealer.clients.import') }}" class="btn-secondary">↑ Import</a>
            <a href="{{ route('dealer.clients.create') }}" class="btn-primary">+ New Client</a>
        </div>
    </div>

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
                    <input type="date" name="from" class="input">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">To Date</label>
                    <input type="date" name="to" class="input">
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

    <div class="card p-0 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr><th class="text-left px-4 py-2">Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Revenue</th><th class="text-right px-4"></th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($clients as $c)
                    <tr>
                        <td class="px-4 py-2">{{ $c->name }}</td>
                        <td>{{ $c->email }}</td>
                        <td>{{ $c->phone ?? '—' }}</td>
                        <td class="text-center">{{ $c->orders_as_client_count }}</td>
                        <td class="text-center">₹{{ number_format((float) $c->revenue, 0) }}</td>
                        <td class="text-right px-4 space-x-2">
                            <a href="{{ route('dealer.clients.edit', $c) }}" class="text-slate-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('dealer.clients.destroy', $c) }}" class="inline" onsubmit="return confirm('Delete client?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">No clients yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $clients->links() }}</div>
</x-layouts.app>
