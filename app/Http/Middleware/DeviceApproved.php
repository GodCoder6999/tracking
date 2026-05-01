<?php

namespace App\Http\Middleware;

use App\Models\DeviceRegistration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $fingerprint = $request->header('X-Device-Fingerprint');

        if (! $fingerprint) {
            return response()->json(['error' => 'Device fingerprint missing.'], 403);
        }

        $device = DeviceRegistration::where('fingerprint', $fingerprint)->first();

        if (! $device || ! $device->isApproved()) {
            return response()->json(['error' => 'Device not approved.', 'status' => $device?->status ?? 'unregistered'], 403);
        }

        return $next($request);
    }
}
