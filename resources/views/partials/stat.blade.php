@props(['label', 'value', 'sub' => null, 'highlight' => false])
<div class="stat-card {{ $highlight ? 'border-amber-200/80' : '' }}">
    @if ($highlight)
        <div class="stat-accent-amber"></div>
    @else
        <div class="stat-accent"></div>
    @endif
    <div class="text-xs font-semibold uppercase tracking-widest {{ $highlight ? 'text-amber-500' : 'text-slate-400' }} mt-1">
        {{ $label }}
    </div>
    <div class="mt-2 text-2xl font-bold tracking-tight {{ $highlight ? 'text-amber-700' : 'text-slate-900' }}">
        {{ $value }}
    </div>
    @isset($sub)
        <div class="text-xs text-slate-400 mt-1">{{ $sub }}</div>
    @endisset
</div>
