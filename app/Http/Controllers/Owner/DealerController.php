<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DealerController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->filled('q') ? trim($request->q) : null;

        $dealers = User::where('role', User::ROLE_DEALER)
            ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
            }))
            ->withCount(['clients', 'ordersAsDealer'])
            ->withSum('ordersAsDealer as revenue', 'total_amount')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $allDealers = User::where('role', User::ROLE_DEALER)->orderBy('name')->get(['id', 'name']);

        return view('owner.dealers.index', compact('dealers', 'allDealers'));
    }

    public function create()
    {
        return view('owner.dealers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:40'],
            'address'  => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['role']     = User::ROLE_DEALER;

        User::create($data);

        return redirect()->route('owner.dealers.index')->with('status', 'Dealer created.');
    }

    public function edit(User $dealer)
    {
        abort_unless($dealer->role === User::ROLE_DEALER, 404);
        return view('owner.dealers.edit', ['dealer' => $dealer]);
    }

    public function update(Request $request, User $dealer)
    {
        abort_unless($dealer->role === User::ROLE_DEALER, 404);

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($dealer->id)],
            'phone'     => ['nullable', 'string', 'max:40'],
            'address'   => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'password'  => ['nullable', 'string', 'min:6'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $dealer->update($data);

        return redirect()->route('owner.dealers.index')->with('status', 'Dealer updated.');
    }

    public function destroy(User $dealer)
    {
        abort_unless($dealer->role === User::ROLE_DEALER, 404);
        $dealer->delete();
        return back()->with('status', 'Dealer deleted.');
    }

    public function importForm()
    {
        return view('owner.dealers.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:5120']]);
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'json'])) {
            return back()->withErrors(['file' => 'File must be CSV or JSON.']);
        }

        try {
            $rows = UserImportService::parse($request->file('file'));
        } catch (\Exception $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $created = [];
        $skipped = [];

        foreach ($rows as $i => $row) {
            $name  = $row['name']     ?? '';
            $email = $row['email']    ?? '';
            $phone = $row['phone']    ?? null;
            $addr  = $row['address']  ?? null;
            $pass  = $row['password'] ?? '';

            if (! $name || ! $email) {
                $skipped[] = ['row' => $i + 2, 'reason' => 'Missing name or email', 'data' => $email ?: $name];
                continue;
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped[] = ['row' => $i + 2, 'reason' => 'Invalid email', 'data' => $email];
                continue;
            }
            if (User::where('email', $email)->exists()) {
                $skipped[] = ['row' => $i + 2, 'reason' => 'Email already exists', 'data' => $email];
                continue;
            }

            $plainPass = $pass ?: Str::random(10);
            User::create([
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone ?: null,
                'address'  => $addr  ?: null,
                'password' => Hash::make($plainPass),
                'role'     => User::ROLE_DEALER,
            ]);
            $created[] = ['name' => $name, 'email' => $email, 'password' => $plainPass];
        }

        return view('owner.dealers.import', compact('created', 'skipped'));
    }

    public function show(User $dealer)
    {
        abort_unless($dealer->role === User::ROLE_DEALER, 404);

        $dealer->loadCount(['clients', 'ordersAsDealer']);
        $orders = $dealer->ordersAsDealer()->with('client')->latest()->limit(30)->get();

        return view('owner.dealers.show', compact('dealer', 'orders'));
    }
}
