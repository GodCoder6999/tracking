<x-layouts.app heading="Sellers">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex gap-2 flex-1 max-w-sm" id="dealer-search-form">
            <input type="search" id="dealer-q" name="q" value="{{ request('q') }}" class="input" placeholder="Search name, email, phone…"
                   oninput="liveSearch(this)">
            <button class="btn-primary">Search</button>
            @if(request('q'))<a href="{{ route('owner.dealers.index') }}" class="btn-secondary">Clear</a>@endif
        </form>
        <script>
            var _dst;
            function liveSearch(el) { clearTimeout(_dst); _dst = setTimeout(function(){ el.form.submit(); }, 350); }
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('dealer-q');
                if (el && el.value) { el.focus(); var l = el.value.length; el.setSelectionRange(l, l); }
            });
        </script>
        <div class="flex gap-2">
            <a href="{{ route('owner.dealers.import') }}" class="btn-secondary">↑ Import</a>
            <a href="{{ route('owner.dealers.create') }}" class="btn-primary">+ New Seller</a>
        </div>
    </div>

    <div class="card p-0 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr><th class="text-left px-4 py-2">Name</th><th class="text-left">Email</th><th>Clients</th><th>Orders</th><th>Revenue</th><th class="text-right px-4">Actions</th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($dealers as $d)
                    <tr>
                        <td class="px-4 py-2">
                            <a href="{{ route('owner.dealers.show', $d) }}" class="text-brand-600 hover:underline">{{ $d->name }}</a>
                            @unless ($d->is_active)<span class="badge-red ml-2">Inactive</span>@endunless
                        </td>
                        <td>{{ $d->email }}</td>
                        <td class="text-center">{{ $d->clients_count }}</td>
                        <td class="text-center">{{ $d->orders_as_dealer_count }}</td>
                        <td class="text-center">₹{{ number_format((float) $d->revenue, 0) }}</td>
                        <td class="text-right px-4 space-x-2">
                            <a href="{{ route('owner.dealers.edit', $d) }}" class="text-slate-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('owner.dealers.destroy', $d) }}" class="inline" onsubmit="return confirm('Delete seller?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">No sellers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $dealers->links() }}</div>
</x-layouts.app>
