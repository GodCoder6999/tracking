<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Order $order)
    {
        abort_unless($order->client_id === auth()->id(), 403);
        $order->load(['dealer', 'items.product', 'payments', 'dispatches']);
        return view('client.orders.show', compact('order'));
    }

    public function uploadProof(Request $request, Order $order)
    {
        abort_unless($order->client_id === auth()->id(), 403);

        $request->validate([
            'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($order->client_proof_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($order->client_proof_path);
        }

        $path = $request->file('proof')->store('proofs', 'public');

        $order->update([
            'client_proof_path' => $path,
            'client_proofed_at' => now(),
        ]);

        return back()->with('status', 'Payment proof uploaded.');
    }
}
