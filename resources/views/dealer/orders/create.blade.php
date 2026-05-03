<x-layouts.app heading="New Order">

@php
    $GST_RATES  = [0, 3, 5, 12, 18, 28];
    $PAY_METHODS = ['cash' => 'Cash', 'upi' => 'UPI', 'bank' => 'Bank Transfer', 'cheque' => 'Cheque', 'cod' => 'Cash on Delivery', 'credit' => 'Credit'];
    $INV_TERMS   = ['due_on_receipt' => 'Due on Receipt', 'net_15' => 'Net-15', 'net_30' => 'Net-30'];
    $SHIP_METHODS = ['standard' => 'Standard', 'expedited' => 'Expedited', 'overnight' => 'Overnight', 'pickup' => 'Local Pickup'];
    $productsJs  = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'rate' => (float) $p->rate, 'dealer_cost' => (float) ($p->dealer_cost ?? 0), 'stock' => (int) $p->stock])->values();
@endphp

<form method="POST" action="{{ route('dealer.orders.store') }}" enctype="multipart/form-data"
      x-data="orderForm()" class="space-y-6 max-w-5xl">
    @csrf

    {{-- ── 1. CLIENT & ORDER INFO ── --}}
    <div class="card space-y-4">
        <h2 class="font-semibold text-slate-900">Client & Order Info</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Client <span class="text-red-500">*</span></label>
                <select name="client_id" class="input" required>
                    <option value="">Select client</option>
                    @foreach ($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} — {{ $c->phone ?? $c->email }}</option>
                    @endforeach
                </select>
                @if ($clients->isEmpty())
                    <p class="text-xs text-red-600 mt-1">No clients. <a class="underline" href="{{ route('dealer.clients.create') }}">Create one first</a>.</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Order Date <span class="text-red-500">*</span></label>
                <input type="date" name="order_date" class="input" value="{{ now()->toDateString() }}" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Shipping Method</label>
                <select name="shipping_method" class="input">
                    <option value="">— Select —</option>
                    @foreach ($SHIP_METHODS as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Requested Delivery Date</label>
                <input type="date" name="requested_delivery_date" class="input">
            </div>
        </div>
    </div>

    {{-- ── 2. SHIPPING & BILLING ADDRESS ── --}}
    <div class="card space-y-4" x-data="{ sameAsBilling: true }">
        <h2 class="font-semibold text-slate-900">Shipping & Billing Address</h2>
        <div>
            <label class="block text-sm font-medium mb-1">Shipping Address</label>
            <textarea name="shipping_address" class="input" rows="2" placeholder="Street, City, State, PIN"></textarea>
        </div>
        <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" x-model="sameAsBilling" class="rounded">
            Billing address same as shipping
        </label>
        <div x-show="!sameAsBilling" x-cloak>
            <label class="block text-sm font-medium mb-1">Billing Address</label>
            <textarea name="billing_address" class="input" rows="2" placeholder="Street, City, State, PIN"></textarea>
        </div>
        <input type="hidden" name="billing_address" x-show="sameAsBilling" x-cloak value="">
    </div>

    {{-- ── 3. ITEMS ── --}}
    <div class="card space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-slate-900">Items</h2>
            <button type="button" @click="add()" class="btn-secondary text-xs">+ Add Item</button>
        </div>

        <div class="space-y-3">
            <template x-for="(it, idx) in items" :key="idx">
                <div class="border border-slate-200 rounded-lg p-4 space-y-3 bg-slate-50">
                    {{-- Row 1: product + particulars --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Product (optional — prefills rate)</label>
                            <select :name="`items[${idx}][product_id]`" class="input" @change="onProduct(idx, $event)">
                                <option value="">— Select product —</option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}"
                                            data-rate="{{ $p->rate }}"
                                            data-cost="{{ $p->dealer_cost ?? '' }}"
                                            data-name="{{ $p->name }}"
                                            data-stock="{{ $p->stock }}">
                                        {{ $p->name }} — ₹{{ number_format((float)$p->rate,2) }}
                                        @if($p->stock > 0) ({{ $p->stock }} in stock)@else (out of stock)@endif
                                    </option>
                                @endforeach
                            </select>
                            {{-- Stock indicator --}}
                            <p class="text-xs mt-0.5"
                               x-show="it.stock !== null"
                               :class="it.stock > 0 ? 'text-green-600' : 'text-red-600'"
                               x-text="it.stock > 0 ? it.stock + ' units in stock' : 'Out of stock'">
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Particulars / Description <span class="text-red-500">*</span></label>
                            <input :name="`items[${idx}][particulars]`" x-model="it.particulars" class="input" required placeholder="Item description">
                        </div>
                    </div>

                    {{-- Row 2: qty, rate, dealer cost, discount, GST --}}
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Qty <span class="text-red-500">*</span></label>
                            <input type="number" min="1" :name="`items[${idx}][qty]`" x-model.number="it.qty" class="input" required @input="recalc(idx)">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Client Rate ₹ <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" :name="`items[${idx}][rate]`" x-model.number="it.rate" class="input" required @input="recalc(idx)">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Dealer Cost ₹</label>
                            <input type="number" step="0.01" :name="`items[${idx}][dealer_cost]`" x-model.number="it.dealer_cost" class="input" placeholder="Your cost">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Discount %</label>
                            <input type="number" step="0.01" min="0" max="100" :name="`items[${idx}][discount_percent]`" x-model.number="it.discount_percent" class="input" placeholder="0" @input="recalc(idx)">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">GST %</label>
                            <select :name="`items[${idx}][gst_rate]`" x-model.number="it.gst_rate" class="input" @change="recalc(idx)">
                                @foreach ($GST_RATES as $r)
                                    <option value="{{ $r }}">{{ $r }}%</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Row 3: computed breakdown --}}
                    <div class="flex flex-wrap gap-4 text-xs text-slate-600 border-t border-slate-200 pt-2 mt-1">
                        <span>Subtotal: ₹<span x-text="fmtN(it.qty * it.rate)"></span></span>
                        <span x-show="it.discount_percent > 0" class="text-green-700">
                            Discount: −₹<span x-text="fmtN(it.qty * it.rate * it.discount_percent / 100)"></span>
                        </span>
                        <span x-show="it.gst_rate > 0" class="text-amber-700">
                            GST (<span x-text="it.gst_rate"></span>%): +₹<span x-text="fmtN(it.gstAmt)"></span>
                        </span>
                        <span class="font-semibold text-slate-900 ml-auto">
                            Line Total: ₹<span x-text="fmtN(it.lineTotal)"></span>
                        </span>
                        <button type="button" @click="remove(idx)" x-show="items.length > 1"
                                class="text-red-600 hover:underline ml-2">Remove</button>
                    </div>
                </div>
            </template>
        </div>

        {{-- GST Calculator Summary --}}
        <div class="bg-white border border-slate-200 rounded-lg p-4 space-y-1.5 text-sm">
            <h3 class="font-semibold text-slate-700 mb-2">Price Summary</h3>
            <div class="flex justify-between"><span class="text-slate-500">Subtotal (before discount)</span><span>₹<span x-text="fmtN(grossSubtotal())"></span></span></div>
            <div class="flex justify-between text-green-700" x-show="totalDiscount() > 0">
                <span>Total Discount</span><span>−₹<span x-text="fmtN(totalDiscount())"></span></span>
            </div>
            <div class="flex justify-between text-slate-600"><span>Taxable Value</span><span>₹<span x-text="fmtN(taxableValue())"></span></span></div>
            <div class="flex justify-between text-amber-700" x-show="totalGst() > 0">
                <span>Total GST</span><span>+₹<span x-text="fmtN(totalGst())"></span></span>
            </div>
            <div class="flex justify-between font-bold text-slate-900 border-t border-slate-200 pt-2 text-base">
                <span>Grand Total</span><span>₹<span x-text="fmtN(grandTotal())"></span></span>
            </div>
            <div class="flex justify-between text-slate-500 text-xs" x-show="totalDealerMargin() !== 0">
                <span>Your Margin (estimated)</span>
                <span :class="totalDealerMargin() >= 0 ? 'text-green-700' : 'text-red-600'">
                    ₹<span x-text="fmtN(Math.abs(totalDealerMargin()))"></span>
                    (<span x-text="totalDealerMargin() >= 0 ? 'profit' : 'loss'"></span>)
                </span>
            </div>
        </div>
    </div>

    {{-- ── 4. PAYMENT ── --}}
    <div class="card space-y-4">
        <h2 class="font-semibold text-slate-900">Payment</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Token / Advance (₹)</label>
                <input type="number" step="0.01" name="token_amount" class="input" value="0" min="0">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Payment Method</label>
                <select name="payment_method" class="input">
                    <option value="">— Select —</option>
                    @foreach ($PAY_METHODS as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Invoicing Terms</label>
                <select name="invoicing_terms" class="input">
                    <option value="">— Select —</option>
                    @foreach ($INV_TERMS as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Token Payment Proof
                <span class="text-xs text-slate-400 font-normal">(screenshot / receipt — PDF/JPG/PNG/WEBP, max 5MB)</span>
            </label>
            <input type="file" name="token_proof" class="input" accept=".pdf,image/*">
        </div>
    </div>

    {{-- ── 5. NOTES & ATTACHMENTS ── --}}
    <div class="card space-y-4">
        <h2 class="font-semibold text-slate-900">Notes & Attachments</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Client Notes
                    <span class="text-xs text-slate-400 font-normal">(visible to client on invoice)</span>
                </label>
                <textarea name="notes" class="input" rows="3" placeholder="Special instructions, delivery notes, etc."></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Internal Notes
                    <span class="text-xs text-red-400 font-normal">(private — client cannot see)</span>
                </label>
                <textarea name="internal_notes" class="input" rows="3" placeholder="VIP client, packing notes, follow-up reminders…"></textarea>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Attachment
                <span class="text-xs text-slate-400 font-normal">(PO, reference doc, tax certificate — PDF/JPG/PNG/WEBP, max 10MB)</span>
            </label>
            <input type="file" name="attachment" class="input" accept=".pdf,image/*">
        </div>
    </div>

    {{-- ── SUBMIT ── --}}
    <div class="flex gap-3 justify-end">
        <a href="{{ route('dealer.orders.index') }}" class="btn-secondary">Cancel</a>
        <button class="btn-primary">Create Order</button>
    </div>
</form>

<script>
const PRODUCTS = @json($productsJs);

function orderForm() {
    return {
        items: [{ particulars: '', qty: 1, rate: 0, dealer_cost: 0, discount_percent: 0, gst_rate: 0, gstAmt: 0, lineTotal: 0, stock: null }],

        add() {
            this.items.push({ particulars: '', qty: 1, rate: 0, dealer_cost: 0, discount_percent: 0, gst_rate: 0, gstAmt: 0, lineTotal: 0, stock: null });
        },
        remove(i) { this.items.splice(i, 1); },

        recalc(i) {
            const it       = this.items[i];
            const qty      = Number(it.qty)              || 0;
            const rate     = Number(it.rate)             || 0;
            const disc     = Number(it.discount_percent) || 0;
            const gst      = Number(it.gst_rate)         || 0;
            const subtotal = qty * rate;
            const afterDis = subtotal * (1 - disc / 100);
            const gstAmt   = afterDis * gst / 100;
            it.gstAmt      = gstAmt;
            it.lineTotal   = afterDis + gstAmt;
        },

        onProduct(i, e) {
            const opt = e.target.selectedOptions[0];
            if (!opt.value) { this.items[i].stock = null; return; }
            this.items[i].rate        = parseFloat(opt.dataset.rate) || 0;
            this.items[i].dealer_cost = parseFloat(opt.dataset.cost) || 0;
            this.items[i].stock       = parseInt(opt.dataset.stock) ?? null;
            if (!this.items[i].particulars) this.items[i].particulars = opt.dataset.name;
            this.recalc(i);
        },

        grossSubtotal() { return this.items.reduce((s, it) => s + (Number(it.qty) || 0) * (Number(it.rate) || 0), 0); },
        totalDiscount() { return this.items.reduce((s, it) => {
            const sub = (Number(it.qty) || 0) * (Number(it.rate) || 0);
            return s + sub * ((Number(it.discount_percent) || 0) / 100);
        }, 0); },
        taxableValue()  { return this.grossSubtotal() - this.totalDiscount(); },
        totalGst()      { return this.items.reduce((s, it) => s + (Number(it.gstAmt) || 0), 0); },
        grandTotal()    { return this.taxableValue() + this.totalGst(); },
        totalDealerMargin() {
            const hasCost = this.items.some(it => Number(it.dealer_cost) > 0);
            if (!hasCost) return 0;
            return this.items.reduce((s, it) => {
                const sold = Number(it.lineTotal) || 0;
                const cost = (Number(it.dealer_cost) || 0) * (Number(it.qty) || 0);
                return s + sold - cost;
            }, 0);
        },

        fmtN(n) { return parseFloat(n || 0).toFixed(2); },
    };
}
</script>

</x-layouts.app>
