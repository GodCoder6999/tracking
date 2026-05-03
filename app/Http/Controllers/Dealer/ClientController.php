<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->filled('q') ? trim($request->q) : null;

        $clients = User::where('role', User::ROLE_CLIENT)
            ->where('created_by', auth()->id())
            ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
            }))
            ->withCount('ordersAsClient')
            ->withSum('ordersAsClient as revenue', 'total_amount')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $allClients = User::where('role', User::ROLE_CLIENT)
            ->where('created_by', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('dealer.clients.index', compact('clients', 'allClients'));
    }

    public function create()
    {
        return view('dealer.clients.create');
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

        $data['password']   = Hash::make($data['password']);
        $data['role']       = User::ROLE_CLIENT;
        $data['created_by'] = auth()->id();

        User::create($data);

        return redirect()->route('dealer.clients.index')->with('status', 'Client created.');
    }

    public function edit(User $client)
    {
        $this->authorizeClient($client);
        return view('dealer.clients.edit', compact('client'));
    }

    public function update(Request $request, User $client)
    {
        $this->authorizeClient($client);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($client->id)],
            'phone'    => ['nullable', 'string', 'max:40'],
            'address'  => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $client->update($data);

        return redirect()->route('dealer.clients.index')->with('status', 'Client updated.');
    }

    public function destroy(User $client)
    {
        $this->authorizeClient($client);
        $client->delete();
        return back()->with('status', 'Client deleted.');
    }

    public function importForm()
    {
        return view('dealer.clients.import');
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

        $dealerId = auth()->id();
        $created  = [];
        $skipped  = [];

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
                'name'       => $name,
                'email'      => $email,
                'phone'      => $phone ?: null,
                'address'    => $addr  ?: null,
                'password'   => Hash::make($plainPass),
                'role'       => User::ROLE_CLIENT,
                'created_by' => $dealerId,
            ]);
            $created[] = ['name' => $name, 'email' => $email, 'password' => $plainPass];
        }

        return view('dealer.clients.import', compact('created', 'skipped'));
    }

    protected function authorizeClient(User $client): void
    {
        abort_unless($client->role === User::ROLE_CLIENT && $client->created_by === auth()->id(), 403);
    }
}
