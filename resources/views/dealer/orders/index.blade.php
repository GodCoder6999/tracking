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

    <div class="space-y-4">
        @forelse ($orders as $o)
            @include('partials.order-card', ['o' => $o, 'cardRoute' => 'dealer.orders.show'])
        @empty
            <div class="card text-center text-slate-400 py-8">No orders.</div>
        @endforelse
    </div>
    <div>{{ $orders->links() }}</div>
</x-layouts.app>
