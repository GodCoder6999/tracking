<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-brand-50 flex items-center justify-center p-6">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-brand-600 text-white flex items-center justify-center text-2xl font-bold">T</div>
            <h1 class="mt-4 text-3xl font-bold text-slate-900">{{ config('app.name', 'Tracking') }}</h1>
            <p class="mt-2 text-slate-600">Order tracking &amp; analytics</p>
        </div>

        <div class="card space-y-3">
            <a href="{{ route('login.dealer') }}" class="btn-primary w-full">Dealer Login</a>
            <a href="{{ route('login.client') }}" class="btn-secondary w-full">Client Login</a>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">Internal use only.</p>
    </div>
</body>
</html>
