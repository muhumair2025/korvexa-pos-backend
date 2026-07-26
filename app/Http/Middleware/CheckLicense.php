<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckLicense Middleware
 *
 * Validates that the authenticated user's tenant has an active, non-expired license.
 * Applied to all tenant-scoped API routes after authentication.
 */
class CheckLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        // Find the active license for this tenant
        $license = \App\Models\License::where('tenant_id', $user->tenant_id)
            ->whereIn('status', ['active'])
            ->first();

        if (!$license) {
            return response()->json([
                'success' => false,
                'message' => 'No active license found. Please activate a valid license key.',
                'code' => 'LICENSE_MISSING',
            ], 403);
        }

        // Check if license has expired
        if ($license->expires_at && $license->expires_at->isPast()) {
            // Mark as expired in DB
            $license->update(['status' => 'expired']);

            return response()->json([
                'success' => false,
                'message' => 'Your subscription has expired. Please renew to continue using cloud features.',
                'code' => 'LICENSE_EXPIRED',
                'expires_at' => $license->expires_at->toISOString(),
            ], 403);
        }

        // Inject license info into request for downstream use
        $request->merge([
            '_license_id' => $license->id,
            '_license_plan' => $license->plan,
        ]);

        return $next($request);
    }
}
