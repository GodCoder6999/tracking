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
