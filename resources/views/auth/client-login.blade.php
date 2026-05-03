@php $heading = 'Client Login'; $subheading = 'See your order status.'; @endphp
<x-layouts.guest :heading="$heading" :subheading="$subheading">
    <form method="POST" action="{{ route('login.client') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" required autofocus value="{{ old('email') }}" class="input">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input type="password" name="password" required class="input">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded"> Remember me
        </label>

        @if ($errors->any())
            <p class="text-sm text-red-600">{{ $errors->first() }}</p>
        @endif

        <button class="btn-primary w-full">Sign In</button>

    </form>
</x-layouts.guest>
