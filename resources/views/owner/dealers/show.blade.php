<x-layouts.app heading="{{ $dealer->name }}">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @include('partials.stat', ['label' => 'Clients', 'value' => $dealer->clients_count])
        @include('partials.stat', ['label' => 'Orders',  'value' => $dealer->orders_as_dealer_count])
        @include('partials.stat', ['label' => 'Email',   'value' => $dealer->email])
    </div>

    <div class="card p-0 overflow-hidden">
        <div class="p-4 border-b font-semibold">Recent Orders</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr><th class="text-left px-4 py-2">Order #</th><th>Date</th><th>Client</th><th>Amount</th><th>Status</th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($orders as $o)
                    <tr>
                        <td class="px-4 py-2"><a class="text-brand-600 hover:underline" href="{{ route('owner.orders.show', $o) }}">{{ $o->order_number }}</a></td>
                        <td>{{ $o->order_date?->format('d M Y') }}</td>
                        <td>{{ $o->client->name }}</td>
                        <td>₹{{ number_format((float) $o->total_amount, 2) }}</td>
                        <td class="space-x-1">@include('partials.badges', ['order' => $o])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">No orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
