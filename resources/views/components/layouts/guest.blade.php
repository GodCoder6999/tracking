<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $heading ?? config('app.name') }} &middot; {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, #312e81 100%);
            min-height: 100vh;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(99,102,241,0.18) 0%, transparent 70%);
            pointer-events: none;
        }
    </style>
</head>
<body class="flex items-center justify-center p-6">
    <div class="max-w-md w-full relative animate-fade-up">

        {{-- Logo + heading --}}
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-block">
                <div class="mx-auto w-14 h-14 rounded-2xl flex items-center justify-center text-2xl font-black text-white"
                     style="background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);box-shadow:0 8px 32px rgba(99,102,241,0.55)">
                    T
                </div>
            </a>
            <h1 class="mt-4 text-2xl font-bold text-white tracking-tight">{{ $heading ?? config('app.name') }}</h1>
            @isset($subheading)
                <p class="text-sm mt-1" style="color:#a5b4fc">{{ $subheading }}</p>
            @endisset
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl p-8" style="box-shadow:0 24px 64px rgba(0,0,0,0.35)">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
