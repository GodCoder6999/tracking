<x-layouts.app heading="Edit Product">
    <form method="POST" action="{{ route('owner.products.update', $product) }}" enctype="multipart/form-data" class="card space-y-4 max-w-2xl">
        @csrf @method('PUT')
        <div><label class="block text-sm font-medium mb-1">Name</label><input name="name" class="input" value="{{ old('name', $product->name) }}" required></div>
        <div><label class="block text-sm font-medium mb-1">Description</label><textarea name="description" class="input" rows="3">{{ old('description', $product->description) }}</textarea></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium mb-1">Rate (₹)</label><input type="number" step="0.01" name="rate" class="input" value="{{ old('rate', $product->rate) }}" required></div>
            <div><label class="block text-sm font-medium mb-1">Stock</label><input type="number" name="stock" class="input" value="{{ old('stock', $product->stock) }}" required></div>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($product->is_active)> Active</label>
        <div>
            <label class="block text-sm font-medium mb-1">Catalog <span class="text-slate-400 font-normal">(PDF, JPG, PNG — optional)</span></label>
            @if ($product->catalog_path)
                <div class="flex items-center gap-3 mb-2 text-sm">
                    <a href="{{ Storage::disk('public')->url($product->catalog_path) }}" target="_blank" class="text-brand-600 hover:underline">View current catalog</a>
                    <span class="text-slate-400">— upload a new file to replace it</span>
                </div>
            @endif
            <input type="file" name="catalog" accept=".pdf,.jpg,.jpeg,.png,.webp" class="input">
        </div>
        <div class="flex gap-2 justify-end">
            <a href="{{ route('owner.products.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary">Save</button>
        </div>
    </form>
</x-layouts.app>
