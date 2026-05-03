<x-layouts.app heading="Order {{ $order->order_number }}">
    @include('partials.order-detail', ['order' => $order, 'mode' => 'client'])
</x-layouts.app>
