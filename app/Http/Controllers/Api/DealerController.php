<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use App\Support\OrderMath;
use App\Support\OrderNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DealerController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $dealer = $request->user();

        $from           = $request->filled('from')            ? $request->from            : null;
        $to             = $request->filled('to')              ? $request->to              : null;
        $clientId       = $request->filled('client_id')       ? $request->client_id       : null;
        $orderNumber    = $request->filled('order_number')    ? $request->order_number    : null;
        $paymentStatus  = $request->filled('payment_status')  ? $request->payment_status  : null;
        $dispatchStatus = $request->filled('dispatch_status') ? $request->dispatch_status : null;
        $productId      = $request->filled('product_id')      ? $request->product_id      : null;

        $base = fn() => Order::where('dealer_id', $dealer->id)
            ->when($from,           fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,             fn($q) => $q->where('order_date', '<=', $to))
            ->when($clientId,       fn($q) => $q->where('client_id', $clientId))
            ->when($orderNumber,    fn($q) => $q->where('order_number', 'like', "%{$orderNumber}%"))
            ->when($paymentStatus === 'overdue',
                fn($q) => $q->where('due_amount', '>', 0)->whereDate('order_date', '<=', Carbon::today()->subDays(30)))
            ->when($paymentStatus && $paymentStatus !== 'overdue',
                fn($q) => $q->where('payment_status', $paymentStatus))
            ->when($dispatchStatus, fn($q) => $q->where('dispatch_status', $dispatchStatus))
            ->when($productId,      fn($q) => $q->whereHas('items', fn($q2) => $q2->where('product_id', $productId)));

        $stats = [
            'clients'          => User::where('role', User::ROLE_CLIENT)->where('created_by', $dealer->id)->count(),
            'orders'           => $base()->count(),
            'revenue'          => (float) $base()->sum('total_amount'),
            'received'         => (float) $base()->sum('total_received'),
            'due'              => (float) $base()->sum('due_amount'),
            'pending_dispatch' => $base()->whereIn('dispatch_status', ['pending', 'partial'])->count(),
        ];

        $clients = User::where('role', User::ROLE_CLIENT)
            ->where('created_by', $dealer->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $recent = Order::with(['client:id,name', 'items'])
            ->where('dealer_id', $dealer->id)
            ->when($from,           fn($q) => $q->where('order_date', '>=', $from))
            ->when($to,             fn($q) => $q->where('order_date', '<=', $to))
            ->when($clientId,       fn($q) => $q->where('client_id', $clientId))
            ->when($orderNumber,    fn($q) => $q->where('order_number', 'like', "%{$orderNumber}%"))
            ->when($paymentStatus === 'overdue',
                fn($q) => $q->where('due_amount', '>', 0)->whereDate('order_date', '<=', Carbon::today()->subDays(30)))
            ->when($paymentStatus && $paymentStatus !== 'overdue',
                fn($q) => $q->where('payment_status', $paymentStatus))
            ->when($dispatchStatus, fn($q) => $q->where('dispatch_status', $dispatchStatus))
            ->when($productId,      fn($q) => $q->whereHas('items', fn($q2) => $q2->where('product_id', $productId)))
            ->latest()
            ->limit(20)
            ->get();

        return response()->json(compact('stats', 'clients', 'products', 'recent'));
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = Order::with(['client:id,name', 'items'])
            ->where('dealer_id', $request->user()->id)
            ->when($request->filled('q'),               fn($q) => $q->where('order_number', 'like', '%'.$request->q.'%'))
            ->when($request->filled('payment_status'),  fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('dispatch_status'), fn($q) => $q->where('dispatch_status', $request->dispatch_status))
            ->when($request->filled('from'),            fn($q) => $q->where('order_date', '>=', $request->from))
            ->when($request->filled('to'),              fn($q) => $q->where('order_date', '<=', $request->to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json($orders);
    }

    public function orderShow(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->dealer_id === $request->user()->id, 403);
        $order->load(['client', 'items.product', 'payments', 'dispatches']);
        return response()->json($order);
    }

    public function orderStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id'                  => ['required', 'exists:users,id'],
            'order_date'                 => ['required', 'date'],
            'token_amount'               => ['nullable', 'numeric', 'min:0'],
            'notes'                      => ['nullable', 'string'],
            'internal_notes'             => ['nullable', 'string'],
            'shipping_address'           => ['nullable', 'string', 'max:500'],
            'billing_address'            => ['nullable', 'string', 'max:500'],
            'payment_method'             => ['nullable', 'string', 'max:30'],
            'invoicing_terms'            => ['nullable', 'string', 'max:30'],
            'shipping_method'            => ['nullable', 'string', 'max:30'],
            'requested_delivery_date'    => ['nullable', 'date'],
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
            ->where('created_by', $request->user()->id)
            ->firstOrFail();

        $order = DB::transaction(function () use ($data, $client, $request) {
            $order = Order::create([
                'order_number'            => OrderNumber::next(),
                'client_id'               => $client->id,
                'dealer_id'               => $request->user()->id,
                'order_date'              => $data['order_date'],
                'token_amount'            => $data['token_amount'] ?? 0,
                'notes'                   => $data['notes'] ?? null,
                'internal_notes'          => $data['internal_notes'] ?? null,
                'shipping_address'        => $data['shipping_address'] ?? null,
                'billing_address'         => $data['billing_address'] ?? null,
                'payment_method'          => $data['payment_method'] ?? null,
                'invoicing_terms'         => $data['invoicing_terms'] ?? null,
                'shipping_method'         => $data['shipping_method'] ?? null,
                'requested_delivery_date' => $data['requested_delivery_date'] ?? null,
                'total_amount'            => 0,
            ]);

            foreach ($data['items'] as $item) {
                $qty         = (int) $item['qty'];
                $rate        = (float) $item['rate'];
                $discountPct = (float) ($item['discount_percent'] ?? 0);
                $gstRate     = (float) ($item['gst_rate'] ?? 0);
                $subtotal    = $qty * $rate;
                $discounted  = round($subtotal * (1 - $discountPct / 100), 4);
                $gstAmount   = round($discounted * $gstRate / 100, 2);
                $amount      = round($discounted + $gstAmount, 2);

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

        return response()->json($order->load(['client', 'items']), 201);
    }

    public function orderUpdateLabel(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->dealer_id === $request->user()->id, 403);
        $data = $request->validate(['label_status' => ['required', 'in:pending,printed,attached']]);
        $order->update($data);
        $order->client->notify(new OrderStatusUpdated($order, "Label status: {$data['label_status']}"));
        return response()->json($order);
    }

    public function clients(Request $request): JsonResponse
    {
        $clients = User::where('role', User::ROLE_CLIENT)
            ->where('created_by', $request->user()->id)
            ->orderBy('name')
            ->get();

        return response()->json($clients);
    }

    public function clientStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'unique:users,email'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $client = User::create([
            ...$data,
            'password'   => bcrypt(\Str::random(16)),
            'role'       => User::ROLE_CLIENT,
            'created_by' => $request->user()->id,
            'is_active'  => true,
        ]);

        return response()->json($client, 201);
    }

    public function clientUpdate(Request $request, User $client): JsonResponse
    {
        abort_unless($client->created_by === $request->user()->id && $client->isClient(), 403);

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $client->update($data);

        return response()->json($client);
    }

    public function products(Request $request): JsonResponse
    {
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'rate', 'dealer_cost']);
        return response()->json($products);
    }
}
