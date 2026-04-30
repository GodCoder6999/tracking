<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showDealer() { return view('auth.dealer-login'); }
    public function showClient() { return view('auth.client-login'); }
    public function showOwner()  { return view('auth.owner-login'); }

    public function dealer(Request $request): RedirectResponse
    {
        return $this->authenticate($request, User::ROLE_DEALER, 'dealer', 'dealer.dashboard');
    }

    public function client(Request $request): RedirectResponse
    {
        return $this->authenticate($request, User::ROLE_CLIENT, 'client', 'client.dashboard');
    }

    public function owner(Request $request): RedirectResponse
    {
        return $this->authenticate($request, User::ROLE_OWNER, 'owner', 'owner.dashboard');
    }

    protected function authenticate(Request $request, string $role, string $guard, string $redirect): RedirectResponse
    {
        $creds = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $creds['role']      = $role;
        $creds['is_active'] = true;

        if (! Auth::guard($guard)->attempt($creds, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials for this login.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route($redirect);
    }

    public function logout(Request $request): RedirectResponse
    {
        $guard = $request->input('_guard', 'web');
        $role  = Auth::guard($guard)->user()?->role;

        Auth::guard($guard)->logout();
        $request->session()->regenerate();

        return match ($role) {
            User::ROLE_OWNER  => redirect()->route('login.owner'),
            User::ROLE_CLIENT => redirect()->route('login.client'),
            default           => redirect()->route('login.dealer'),
        };
    }
}
