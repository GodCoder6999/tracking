<x-layouts.app heading="Edit Dealer">
    <form method="POST" action="{{ route('owner.dealers.update', $dealer) }}" class="card space-y-4 max-w-2xl">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium mb-1">Name</label><input name="name" class="input" value="{{ old('name', $dealer->name) }}" required></div>
            <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" class="input" value="{{ old('email', $dealer->email) }}" required></div>
            <div><label class="block text-sm font-medium mb-1">Phone</label><input name="phone" class="input" value="{{ old('phone', $dealer->phone) }}"></div>
            <div><label class="block text-sm font-medium mb-1">New Password (blank = keep)</label><input type="password" name="password" class="input"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium mb-1">Address</label><input name="address" class="input" value="{{ old('address', $dealer->address) }}"></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($dealer->is_active)> Active</label>
        </div>
        <div class="flex gap-2 justify-end">
            <a href="{{ route('owner.dealers.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary">Save</button>
        </div>
    </form>
</x-layouts.app>
