<x-layouts.app heading="New Seller">
    <form method="POST" action="{{ route('owner.dealers.store') }}" class="card space-y-4 max-w-2xl">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium mb-1">Name</label><input name="name" class="input" value="{{ old('name') }}" required></div>
            <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" class="input" value="{{ old('email') }}" required></div>
            <div><label class="block text-sm font-medium mb-1">Phone</label><input name="phone" class="input" value="{{ old('phone') }}"></div>
            <div><label class="block text-sm font-medium mb-1">Password</label><input type="password" name="password" class="input" required></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium mb-1">Address</label><input name="address" class="input" value="{{ old('address') }}"></div>
        </div>
        <div class="flex gap-2 justify-end">
            <a href="{{ route('owner.dealers.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary">Create Seller</button>
        </div>
    </form>
</x-layouts.app>
