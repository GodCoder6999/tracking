<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\UserImportService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('name')->paginate(20);
        return view('owner.products.index', compact('products'));
    }

    public function importForm()
    {
        return view('owner.products.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:5120']]);
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'json'])) {
            return back()->withErrors(['file' => 'File must be CSV or JSON.']);
        }

        $rows    = UserImportService::parse($request->file('file'));
        $created = [];
        $skipped = [];

        foreach ($rows as $i => $row) {
            $name = trim($row['name'] ?? '');
            $rate = trim($row['rate'] ?? '');

            if ($name === '' || $rate === '') {
                $skipped[] = ['row' => $i + 2, 'data' => $name ?: '(empty)', 'reason' => 'name and rate are required'];
                continue;
            }

            if (!is_numeric($rate) || $rate < 0) {
                $skipped[] = ['row' => $i + 2, 'data' => $name, 'reason' => 'rate must be a non-negative number'];
                continue;
            }

            $stock    = isset($row['stock']) && is_numeric($row['stock']) ? (int) $row['stock'] : 0;
            $isActive = isset($row['is_active']) ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN) : true;

            Product::create([
                'name'        => $name,
                'description' => $row['description'] ?? null,
                'rate'        => (float) $rate,
                'stock'       => $stock,
                'is_active'   => $isActive,
            ]);

            $created[] = ['name' => $name, 'rate' => $rate, 'stock' => $stock];
        }

        return view('owner.products.import', compact('created', 'skipped'));
    }

    public function create()
    {
        return view('owner.products.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('catalog')) {
            $data['catalog_path'] = $request->file('catalog')->store('catalogs', 'public');
        }
        Product::create($data);
        return redirect()->route('owner.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product)
    {
        return view('owner.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request);
        if ($request->hasFile('catalog')) {
            if ($product->catalog_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->catalog_path);
            }
            $data['catalog_path'] = $request->file('catalog')->store('catalogs', 'public');
        }
        $product->update($data);
        return redirect()->route('owner.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        if ($product->catalog_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->catalog_path);
        }
        $product->delete();
        return back()->with('status', 'Product deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'rate'        => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
            'catalog'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
