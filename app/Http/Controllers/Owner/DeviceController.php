<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\DeviceRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $pending  = DeviceRegistration::with('dealer')
            ->where('status', DeviceRegistration::STATUS_PENDING)
            ->latest()
            ->get();

        $approved = DeviceRegistration::with(['dealer', 'approvedBy'])
            ->where('status', DeviceRegistration::STATUS_APPROVED)
            ->latest()
            ->limit(50)
            ->get();

        $rejected = DeviceRegistration::with('dealer')
            ->where('status', DeviceRegistration::STATUS_REJECTED)
            ->latest()
            ->limit(50)
            ->get();

        return view('owner.devices.index', compact('pending', 'approved', 'rejected'));
    }

    public function approve(DeviceRegistration $device): RedirectResponse
    {
        $device->update([
            'status'      => DeviceRegistration::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', "Device \"{$device->device_name}\" approved.");
    }

    public function reject(DeviceRegistration $device): RedirectResponse
    {
        $device->update([
            'status'      => DeviceRegistration::STATUS_REJECTED,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return back()->with('status', "Device \"{$device->device_name}\" rejected.");
    }

    public function revoke(DeviceRegistration $device): RedirectResponse
    {
        $device->update([
            'status'      => DeviceRegistration::STATUS_REJECTED,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return back()->with('status', "Device \"{$device->device_name}\" revoked.");
    }
}
