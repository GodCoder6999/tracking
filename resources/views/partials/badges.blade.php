@props(['order'])
@php
    $p = ['unpaid' => 'badge-red',    'partial' => 'badge-yellow', 'paid'      => 'badge-green'];
    $d = ['pending' => 'badge-red',   'partial' => 'badge-yellow', 'sent'      => 'badge-blue', 'delivered' => 'badge-green'];
    $l = ['pending' => 'badge-gray',  'printed' => 'badge-yellow', 'attached'  => 'badge-green'];
@endphp

<span class="{{ $l[$order->label_status]    ?? 'badge-gray' }}">
    <svg class="w-2.5 h-2.5" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="3"/></svg>
    Label: {{ ucfirst($order->label_status) }}
</span>
<span class="{{ $p[$order->payment_status]  ?? 'badge-gray' }}">
    <svg class="w-2.5 h-2.5" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="3"/></svg>
    {{ ucfirst($order->payment_status) }}
</span>
<span class="{{ $d[$order->dispatch_status] ?? 'badge-gray' }}">
    <svg class="w-2.5 h-2.5" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="3"/></svg>
    {{ ucfirst($order->dispatch_status) }}
</span>
