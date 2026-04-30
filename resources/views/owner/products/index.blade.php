<x-layouts.app heading="Products">
    <div class="flex justify-end gap-2">
        <a href="{{ route('owner.products.import') }}" class="btn-secondary">↑ Import</a>
        <a href="{{ route('owner.products.create') }}" class="btn-primary">+ New Product</a>
    </div>

    <div class="card p-0 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr><th class="text-left px-4 py-2">Name</th><th>Rate</th><th>Stock</th><th>Active</th><th>Catalog</th><th class="text-right px-4">Actions</th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($products as $p)
                    <tr>
                        <td class="px-4 py-2">{{ $p->name }}</td>
                        <td class="text-center">₹{{ number_format((float) $p->rate, 2) }}</td>
                        <td class="text-center">{{ $p->stock }}</td>
                        <td class="text-center">{!! $p->is_active ? '<span class="badge-green">Yes</span>' : '<span class="badge-red">No</span>' !!}</td>
                        <td class="text-center">
                            @if ($p->catalog_path)
                                <a href="{{ Storage::disk('public')->url($p->catalog_path) }}" target="_blank" class="text-brand-600 hover:underline text-xs">View</a>
                            @else
                                <span class="text-slate-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="text-right px-4 space-x-2">
                            <a href="{{ route('owner.products.edit', $p) }}" class="text-slate-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('owner.products.destroy', $p) }}" class="inline" onsubmit="return confirm('Delete product?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">No products.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $products->links() }}</div>
</x-layouts.app>
