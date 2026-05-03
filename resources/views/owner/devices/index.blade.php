<x-layouts.app heading="Desktop App Devices">

    {{-- Pending --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <div>
                <h2 class="card-title">Pending Approvals</h2>
                <p class="card-subtitle">New devices waiting for your approval</p>
            </div>
            @if($pending->count() > 0)
                <span class="px-3 py-1 text-xs font-bold bg-amber-100 text-amber-700 rounded-full">{{ $pending->count() }} pending</span>
            @endif
        </div>

        @if($pending->isEmpty())
            <div class="p-8 text-center text-slate-400 text-sm">No pending device requests.</div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>Platform</th>
                            <th>Dealer</th>
                            <th>Requested</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $device)
                            <tr>
                                <td class="font-semibold text-slate-800">{{ $device->device_name }}</td>
                                <td class="text-slate-500">{{ $device->platform ?? '—' }}</td>
                                <td>{{ $device->dealer?->name ?? '—' }}</td>
                                <td class="text-slate-500 text-xs">{{ $device->created_at->diffForHumans() }}</td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('owner.devices.approve', $device) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('owner.devices.reject', $device) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Approved --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Approved Devices</h2>
            <p class="card-subtitle">These devices can access the app</p>
        </div>
        @if($approved->isEmpty())
            <div class="p-8 text-center text-slate-400 text-sm">No approved devices yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>Platform</th>
                            <th>Dealer</th>
                            <th>Approved</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($approved as $device)
                            <tr>
                                <td class="font-semibold text-slate-800">{{ $device->device_name }}</td>
                                <td class="text-slate-500">{{ $device->platform ?? '—' }}</td>
                                <td>{{ $device->dealer?->name ?? '—' }}</td>
                                <td class="text-slate-500 text-xs">{{ $device->approved_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('owner.devices.revoke', $device) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-danger">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Rejected --}}
    @if($rejected->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Rejected Devices</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Device Name</th>
                        <th>Platform</th>
                        <th>Dealer</th>
                        <th>Rejected</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rejected as $device)
                        <tr>
                            <td class="font-semibold text-slate-800">{{ $device->device_name }}</td>
                            <td class="text-slate-500">{{ $device->platform ?? '—' }}</td>
                            <td>{{ $device->dealer?->name ?? '—' }}</td>
                            <td class="text-slate-500 text-xs">{{ $device->updated_at->diffForHumans() }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('owner.devices.approve', $device) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-success">Re-approve</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</x-layouts.app>
