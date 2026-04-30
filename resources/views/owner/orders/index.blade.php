<x-layouts.app heading="All Orders">
    <form method="GET" class="card grid grid-cols-2 md:grid-cols-7 gap-3">
        <input name="q" placeholder="Order #" class="input" value="{{ request('q') }}">
        <select name="dealer_id" class="input">
            <option value="">All Sellers</option>
            @foreach($dealers as $d)
                <option value="{{ $d->id }}" @selected(request('dealer_id') == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
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
            @if(request()->hasAny(['q','payment_status','dispatch_status','from','to','dealer_id']))
                <a href="{{ route('owner.orders.index') }}" class="btn-secondary">Clear</a>
            @endif
        </div>
    </form>

    <div class="space-y-4">
        @forelse ($orders as $o)
            @include('partials.order-card', ['o' => $o, 'cardRoute' => 'owner.orders.show', 'showDealer' => true])
        @empty
            <div class="card text-center text-slate-400 py-8">No orders found.</div>
        @endforelse
    </div>
    <div>{{ $orders->links() }}</div>
</x-layouts.app>
