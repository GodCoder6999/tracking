@props(['route', 'icon' => null])
@php $active = request()->routeIs($route.'*') || request()->routeIs($route); @endphp
<a href="{{ url($attributes->get('href', '#')) }}"
   {{ $attributes->merge(['class' => 'block px-3 py-2 rounded-md '.($active ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800')]) }}>
    {{ $slot }}
</a>
