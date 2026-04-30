<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderMath;
use App\Support\OrderNumber;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['client', 'items'])
            ->where('dealer_id', auth()->id())
            ->when($request->filled('q'),               fn($q) => $q->where('order_number', 'like', '%'.$request->q.'%'))
            ->when($request->filled('payment_status'),  fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('dispatch_status'), fn($q) => $q->where('dispatch_status', $request->dispatch_status))
            ->when($request->filled('from'),            fn($q) => $q->where('order_date', '>=', $request->from))
            ->when($request->filled('to'),              fn($q) => $q->where('order_date', '<=', $request->to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dealer.orders.index', compact('orders'));
    }

    public function create()
    {
        $clients  = User::where('role', User::ROLE_CLIENT)->where('created_by', auth()->id())->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        return view('dealer.orders.create', compact('clients', 'products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'                  => ['required', 'exists:users,id'],
            'order_date'                 => ['required', 'date'],
            'token_amount'               => ['nullable', 'numeric', 'min:0'],
            'token_proof'                => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'notes'                      => ['nullable', 'string'],
            'internal_notes'             => ['nullable', 'string'],
            'shipping_address'           => ['nullable', 'string', 'max:500'],
            'billing_address'            => ['nullable', 'string', 'max:500'],
            'payment_method'             => ['nullable', 'string', 'max:30'],
            'invoicing_terms'            => ['nullable', 'string', 'max:30'],
            'shipping_method'            => ['nullable', 'string', 'max:30'],
            'requested_delivery_date'    => ['nullable', 'date'],
            'attachment'                 => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.product_id'         => ['nullable', 'exists:products,id'],
            'items.*.particulars'        => ['required', 'string', 'max:180'],
            'items.*.qty'                => ['required', 'integer', 'min:1'],
            'items.*.rate'               => ['required', 'numeric', 'min:0'],
            'items.*.dealer_cost'        => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.gst_rate'           => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $client = User::where('id', $data['client_id'])
            ->where('role', User::ROLE_CLIENT)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $attachmentPath  = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('order-attachments', 'public');
        }

        $tokenProofPath = null;
        if ($request->hasFile('token_proof')) {
            $tokenProofPath = $request->file('token_proof')->store('token-proofs', 'public');
        }

        $order = DB::transaction(function () use ($data, $client, $attachmentPath, $tokenProofPath) {
            $order = Order::create([
                'order_number'            => OrderNumber::next(),
                'client_id'               => $client->id,
                'dealer_id'               => auth()->id(),
                'order_date'              => $data['order_date'],
                'token_amount'            => $data['token_amount'] ?? 0,
                'token_proof_path'        => $tokenProofPath,
                'notes'                   => $data['notes'] ?? null,
                'internal_notes'          => $data['internal_notes'] ?? null,
                'shipping_address'        => $data['shipping_address'] ?? null,
                'billing_address'         => $data['billing_address'] ?? null,
                'payment_method'          => $data['payment_method'] ?? null,
                'invoicing_terms'         => $data['invoicing_terms'] ?? null,
                'shipping_method'         => $data['shipping_method'] ?? null,
                'requested_delivery_date' => $data['requested_delivery_date'] ?? null,
                'attachment_path'         => $attachmentPath,
                'total_amount'            => 0,
            ]);

            foreach ($data['items'] as $item) {
                $qty             = (int) $item['qty'];
                $rate            = (float) $item['rate'];
                $discountPct     = (float) ($item['discount_percent'] ?? 0);
                $gstRate         = (float) ($item['gst_rate'] ?? 0);
                $subtotal        = $qty * $rate;
                $discounted      = round($subtotal * (1 - $discountPct / 100), 4);
                $gstAmount       = round($discounted * $gstRate / 100, 2);
                $amount          = round($discounted + $gstAmount, 2);

                $order->items()->create([
                    'product_id'       => $item['product_id'] ?? null,
                    'particulars'      => $item['particulars'],
                    'qty'              => $qty,
                    'rate'             => $rate,
                    'dealer_cost'      => isset($item['dealer_cost']) ? (float) $item['dealer_cost'] : null,
                    'discount_percent' => $discountPct,
                    'gst_rate'         => $gstRate,
                    'gst_amount'       => $gstAmount,
                    'amount'           => $amount,
                ]);
            }

            OrderMath::recompute($order);
            return $order;
        });

        $order->client->notify(new OrderStatusUpdated($order, 'Order created'));

        return redirect()->route('dealer.orders.show', $order)->with('status', 'Order created.');
    }

    public function show(Order $order)
    {
        $this->authorizeOrder($order);
        $order->load(['client', 'items.product', 'payments', 'dispatches']);
        return view('dealer.orders.show', compact('order'));
    }

    public function updateLabel(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        $data = $request->validate(['label_status' => ['required', 'in:pending,printed,attached']]);
        $order->update($data);
        $order->client->notify(new OrderStatusUpdated($order, "Label status: {$data['label_status']}"));
        return back()->with('status', 'Label status updated.');
    }

    protected function authorizeOrder(Order $order): void
    {
        abort_unless($order->dealer_id === auth()->id(), 403);
    }
}
