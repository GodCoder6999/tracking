<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewDeviceRegistered;
use App\Models\DeviceRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'fingerprint' => ['required', 'string'],
            'device_name' => ['required', 'string'],
            'platform'    => ['nullable', 'string'],
        ]);

        $device = DeviceRegistration::where('fingerprint', $request->fingerprint)->first();

        if (! $device) {
            $user = User::where('email', $request->email)
                ->where('role', User::ROLE_DEALER)
                ->where('is_active', true)
                ->first();

            DeviceRegistration::create([
                'dealer_id'   => $user?->id,
                'fingerprint' => $request->fingerprint,
                'device_name' => $request->device_name,
                'platform'    => $request->platform,
                'status'      => DeviceRegistration::STATUS_PENDING,
            ]);

            $ownerEmail = env('OWNER_NOTIFY_EMAIL') ?: env('OWNER_EMAIL');
            if ($ownerEmail) {
                Mail::to($ownerEmail)->queue(new NewDeviceRegistered(
                    $request->device_name,
                    $request->platform ?? 'Unknown',
                    $user?->name ?? $request->email,
                ));
            }

            return response()->json([
                'status'  => 'pending',
                'message' => 'Device registration request sent. Waiting for admin approval.',
            ], 202);
        }

        if ($device->isPending()) {
            return response()->json([
                'status'  => 'pending',
                'message' => 'Device approval pending. Please wait for admin.',
            ], 202);
        }

        if ($device->isRejected()) {
            return response()->json([
                'status'  => 'rejected',
                'message' => 'This device has been rejected. Contact admin.',
            ], 403);
        }

        $user = User::where('email', $request->email)
            ->where('role', User::ROLE_DEALER)
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials.'], 401);
        }

        // update device dealer association if missing
        if (! $device->dealer_id) {
            $device->update(['dealer_id' => $user->id]);
        }

        $plainToken = Str::random(60);
        $user->update(['api_token' => hash('sha256', $plainToken)]);

        return response()->json([
            'status' => 'approved',
            'token'  => $plainToken,
            'dealer' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
        ]);
    }

    public function checkDevice(Request $request): JsonResponse
    {
        $request->validate(['fingerprint' => ['required', 'string']]);

        $device = DeviceRegistration::where('fingerprint', $request->fingerprint)->first();

        if (! $device) {
            return response()->json(['status' => 'unregistered']);
        }

        return response()->json([
            'status'      => $device->status,
            'device_name' => $device->device_name,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->update(['api_token' => null]);

        return response()->json(['message' => 'Logged out.']);
    }
}
