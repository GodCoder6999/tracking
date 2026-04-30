<x-layouts.app heading="New Product">
    <form method="POST" action="{{ route('owner.products.store') }}" enctype="multipart/form-data" class="card space-y-4 max-w-2xl">
        @csrf
        <div><label class="block text-sm font-medium mb-1">Name</label><input name="name" class="input" value="{{ old('name') }}" required></div>
        <div><label class="block text-sm font-medium mb-1">Description</label><textarea name="description" class="input" rows="3">{{ old('description') }}</textarea></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium mb-1">Rate (₹)</label><input type="number" step="0.01" name="rate" class="input" value="{{ old('rate', 0) }}" required></div>
            <div><label class="block text-sm font-medium mb-1">Stock</label><input type="number" name="stock" class="input" value="{{ old('stock', 0) }}" required></div>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
        <div>
            <label class="block text-sm font-medium mb-1">Catalog <span class="text-slate-400 font-normal">(PDF, JPG, PNG — optional)</span></label>
            <input type="file" name="catalog" accept=".pdf,.jpg,.jpeg,.png,.webp" class="input">
        </div>
        <div class="flex gap-2 justify-end">
            <a href="{{ route('owner.products.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary">Create</button>
        </div>
    </form>
</x-layouts.app>
