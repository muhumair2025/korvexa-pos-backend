<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantScope Middleware
 *
 * Extracts the tenant_id from the authenticated user's token
 * and injects it into the request for use by the BelongsToTenant trait.
 * Rejects requests without a valid tenant context.
 */
class TenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context could not be resolved. Please re-authenticate.',
            ], 403);
        }

        // Support token issued to Tenant model directly or User model
        $tenantId = ($user instanceof \App\Models\Tenant) ? $user->id : ($user->tenant_id ?? null);

        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context could not be resolved. Please re-authenticate.',
            ], 403);
        }

        // Verify the tenant exists and is active
        $tenant = \App\Models\Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found. Your account may have been removed.',
            ], 404);
        }

        if ($tenant->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact support.',
            ], 403);
        }

        // Inject tenant_id into request for BelongsToTenant trait resolution
        $request->merge(['_tenant_id' => $tenantId]);

        return $next($request);
    }
}
