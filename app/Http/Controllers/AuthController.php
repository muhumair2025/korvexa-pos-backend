<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * POST /api/auth/register
     * Register Master Admin for a Tenant (First-time setup after license activation).
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'full_name'   => 'required|string|max:255',
            'username'    => 'required|string|max:100',
            'password'    => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $licenseKey = strtoupper(trim($request->input('license_key')));
        $license = License::with('tenant')->where('license_key', $licenseKey)->first();

        if (!$license || !$license->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive license key.',
            ], 400);
        }

        if ($license->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'License has expired.',
            ], 403);
        }

        $tenantId = $license->tenant_id;

        // Check if tenant already has users registered
        $existingUserCount = User::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->count();

        if ($existingUserCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'An administrator account already exists for this business. Please log in using existing employee credentials.',
                'code'    => 'ADMIN_EXISTS',
            ], 400);
        }

        $cleanUsername = strtolower(trim($request->input('username')));

        // Create Master Admin User
        $user = User::withoutTenantScope()->create([
            'uuid'           => (string) Str::uuid(),
            'tenant_id'      => $tenantId,
            'username'       => $cleanUsername,
            'password'       => Hash::make($request->input('password')),
            'full_name'      => trim($request->input('full_name')),
            'role'           => 'Administrator',
            'permissions'    => [
                'register', 'inventory', 'orders', 'customers',
                'khata', 'suppliers', 'cash_drawer', 'overview',
                'staff', 'settings',
            ],
            'shift_schedule' => 'Flexible / Full Day',
            'max_cash_limit' => 1000.00,
            'synced_at'      => now(),
        ]);

        // Create Sanctum API Token
        $token = $user->createToken('pos_device_token', ['*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Master Administrator registered successfully.',
            'token'   => $token,
            'user'    => [
                'id'             => $user->id,
                'uuid'           => $user->uuid,
                'username'       => $user->username,
                'full_name'      => $user->full_name,
                'role'           => $user->role,
                'permissions'    => $user->getEffectivePermissions(),
                'shift_schedule' => $user->shift_schedule,
                'max_cash_limit' => (float) $user->max_cash_limit,
            ],
            'tenant'  => [
                'id'            => $license->tenant->id,
                'business_name' => $license->tenant->business_name,
            ],
        ], 201);
    }

    /**
     * POST /api/auth/login
     * Authenticate user for a specific tenant via License Key + Credentials.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'username'    => 'required|string',
            'password'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'License key, username, and password are required.',
            ], 422);
        }

        $licenseKey = strtoupper(trim($request->input('license_key')));
        $license = License::with('tenant')->where('license_key', $licenseKey)->first();

        if (!$license || !$license->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive license key.',
            ], 400);
        }

        if ($license->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription expired. Please renew your license.',
                'code'    => 'LICENSE_EXPIRED',
            ], 403);
        }

        $cleanUsername = strtolower(trim($request->input('username')));

        // Find user specifically under this tenant's ID (Strict Multi-Tenant Scope)
        $user = User::withoutTenantScope()
            ->where('tenant_id', $license->tenant_id)
            ->where('username', $cleanUsername)
            ->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password for this business store.',
            ], 401);
        }

        // Generate new API Token
        $token = $user->createToken('pos_device_token', ['*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => [
                'id'             => $user->id,
                'uuid'           => $user->uuid,
                'username'       => $user->username,
                'full_name'      => $user->full_name,
                'role'           => $user->role,
                'permissions'    => $user->getEffectivePermissions(),
                'shift_schedule' => $user->shift_schedule,
                'max_cash_limit' => (float) $user->max_cash_limit,
                'avatar'         => $user->avatar,
            ],
            'tenant'  => [
                'id'            => $license->tenant->id,
                'business_name' => $license->tenant->business_name,
                'owner_name'    => $license->tenant->owner_name,
            ],
            'license' => [
                'key'            => $license->license_key,
                'plan'           => $license->plan,
                'expires_at'     => $license->expires_at ? $license->expires_at->toISOString() : null,
                'days_remaining' => $license->daysUntilExpiry(),
            ],
        ], 200);
    }

    /**
     * GET /api/auth/me
     * Return authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = Tenant::find($user->tenant_id);

        return response()->json([
            'success' => true,
            'user'    => [
                'id'             => $user->id,
                'uuid'           => $user->uuid,
                'username'       => $user->username,
                'full_name'      => $user->full_name,
                'role'           => $user->role,
                'permissions'    => $user->getEffectivePermissions(),
                'shift_schedule' => $user->shift_schedule,
                'max_cash_limit' => (float) $user->max_cash_limit,
                'avatar'         => $user->avatar,
            ],
            'tenant'  => [
                'id'            => $tenant->id,
                'business_name' => $tenant->business_name,
                'owner_name'    => $tenant->owner_name,
                'owner_email'   => $tenant->owner_email,
            ],
        ], 200);
    }

    /**
     * POST /api/auth/logout
     * Revoke active device API token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }
}
