@php $heading = 'Owner Control'; $subheading = 'Restricted access.'; @endphp
<x-layouts.guest :heading="$heading" :subheading="$subheading">
    <form method="POST" action="{{ url()->current() }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" required autofocus value="{{ old('email') }}" class="input">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input type="password" name="password" required class="input">
        </div>

        @if ($errors->any())
            <p class="text-sm text-red-600">{{ $errors->first() }}</p>
        @endif

        <button class="btn-primary w-full">Enter</button>
    </form>
</x-layouts.guest>
