<x-layouts.app heading="Import Products">

    <div class="flex items-center gap-3">
        <a href="{{ route('owner.products.index') }}" class="btn-secondary">← Back to Products</a>
        <a href="{{ route('owner.products.create') }}" class="btn-secondary">+ Add Manually</a>
    </div>

    <div class="card">
        <h2 class="font-semibold mb-1">Bulk Import Products</h2>
        <p class="text-sm text-slate-500 mb-4">Upload a CSV or JSON file. First row must be column headers.</p>

        <div class="mb-4 bg-slate-50 border rounded-lg p-3 text-xs text-slate-600 font-mono">
            <div class="font-semibold text-slate-700 mb-1 font-sans">Expected columns (name &amp; rate required):</div>
            name, description, rate, stock, is_active
            <div class="mt-2 font-sans text-slate-500"><strong>is_active</strong>: 1 or 0 (defaults to 1 if blank). <strong>stock</strong>: defaults to 0 if blank.</div>
        </div>

        <div class="mb-5 space-y-4 text-xs text-slate-600">
            <div>
                <div class="font-semibold text-slate-700 mb-1">Option 1 — CSV file</div>
                <ol class="list-decimal list-inside space-y-1">
                    <li>Open <strong>Notepad</strong> and paste this as line 1:<br>
                        <code class="bg-slate-100 px-1 py-0.5 rounded font-mono">name,description,rate,stock,is_active</code>
                    </li>
                    <li>Add one product per line below, values separated by commas:<br>
                        <code class="bg-slate-100 px-1 py-0.5 rounded font-mono">Ashwagandha Extract,Stress relief supplement,450,100,1</code>
                    </li>
                    <li>Save with a <strong>.csv</strong> extension (e.g. <code class="font-mono">products.csv</code>) and upload.</li>
                </ol>
                <p class="mt-1 text-slate-500"><strong>Shortcut:</strong> Fill data in Excel → File → Save As → <em>CSV UTF-8</em>. Use 1 for active, 0 for inactive.</p>
            </div>
            <div>
                <div class="font-semibold text-slate-700 mb-1">Option 2 — JSON file</div>
                <ol class="list-decimal list-inside space-y-1">
                    <li>Open Notepad and write an array of objects:</li>
                </ol>
                <pre class="mt-1 bg-slate-100 rounded p-2 font-mono overflow-x-auto">[
  {"name":"Ashwagandha Extract","description":"Stress relief supplement","rate":"450","stock":"100","is_active":"1"},
  {"name":"Triphala Churna","description":"Digestive health","rate":"299","stock":"150","is_active":"1"}
]</pre>
                <li class="mt-1 list-none">2. Save with a <strong>.json</strong> extension and upload. Leave <code class="font-mono">stock</code> or <code class="font-mono">is_active</code> blank to default to 0 and active respectively.</li>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded px-4 py-2 text-sm mb-4">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('owner.products.import.store') }}" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div class="flex-1 min-w-64">
                <label class="block text-xs text-slate-500 mb-1">File (CSV / JSON)</label>
                <input type="file" name="file" accept=".csv,.json" class="input" required>
            </div>
            <button class="btn-primary">Import</button>
        </form>
    </div>

    @isset($created)
    <div class="card">
        <h3 class="font-semibold mb-3 text-green-700">✓ {{ count($created) }} product(s) created</h3>
        @if (count($created))
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr><th class="text-left px-3 py-2">Name</th><th class="text-left px-3 py-2">Rate</th><th class="text-left px-3 py-2">Stock</th></tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($created as $c)
                    <tr>
                        <td class="px-3 py-2">{{ $c['name'] }}</td>
                        <td class="px-3 py-2">₹{{ number_format((float)$c['rate'], 2) }}</td>
                        <td class="px-3 py-2">{{ $c['stock'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endisset

    @isset($skipped)
    @if (count($skipped))
    <div class="card">
        <h3 class="font-semibold mb-3 text-red-600">{{ count($skipped) }} row(s) skipped</h3>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr><th class="text-left px-3 py-2">Row</th><th class="text-left px-3 py-2">Value</th><th class="text-left px-3 py-2">Reason</th></tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($skipped as $s)
                <tr>
                    <td class="px-3 py-2">{{ $s['row'] }}</td>
                    <td class="px-3 py-2 text-slate-500">{{ $s['data'] }}</td>
                    <td class="px-3 py-2 text-red-600">{{ $s['reason'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endisset

</x-layouts.app>
