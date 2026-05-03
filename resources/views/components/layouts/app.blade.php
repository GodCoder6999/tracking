@props(['heading' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $heading ?? config('app.name') }} &middot; {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50/80 text-slate-800">
<div class="flex min-h-screen">

    {{-- ── Sidebar ────────────────────────────────────────────────── --}}
    <aside class="sidebar w-64 flex flex-col flex-shrink-0 sticky top-0 h-screen overflow-y-auto">

        {{-- Logo --}}
        <div class="px-5 py-5 flex items-center gap-3 border-b border-white/[0.07]">
            <div class="sidebar-logo w-10 h-10 rounded-xl flex items-center justify-center font-extrabold text-white text-xl tracking-tight">
                T
            </div>
            <div>
                <div class="text-sm font-bold text-white tracking-wide">{{ config('app.name') }}</div>
                <div class="text-xs font-medium capitalize" style="color:#a5b4fc">{{ auth()->user()->role === 'dealer' ? 'seller' : auth()->user()->role }}</div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5">
            @if (auth()->user()->isOwner())
                @include('partials.nav-owner')
            @elseif (auth()->user()->isDealer())
                @include('partials.nav-dealer')
            @else
                @include('partials.nav-client')
            @endif
        </nav>

        {{-- User footer --}}
        <div class="p-4 border-t border-white/[0.07]">
            <div class="flex items-center gap-3 mb-2.5 px-1">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                     style="background: linear-gradient(135deg,#6366f1,#8b5cf6)">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-white truncate leading-tight">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <input type="hidden" name="_guard" value="{{ auth()->user()->role }}">
                <button class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-slate-500 hover:text-white hover:bg-white/10 transition-all duration-200">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    {{-- ── Main ───────────────────────────────────────────────────── --}}
    <main class="flex-1 flex flex-col min-w-0">

        {{-- Header --}}
        <header class="bg-white/90 backdrop-blur border-b border-slate-100 px-8 py-4 flex items-center justify-between sticky top-0 z-20"
                style="box-shadow:0 1px 0 rgba(15,23,42,0.05)">
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">{{ $heading ?? '' }}</h1>
            <div class="text-sm font-medium text-slate-400">{{ now()->format('d M Y') }}</div>
        </header>

        {{-- Content --}}
        <div class="p-8 space-y-6 page-content">
            @if (session('status'))
                <div class="flash-success">
                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="flash-error">
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
