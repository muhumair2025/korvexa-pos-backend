<?php

namespace App\Services;

use App\Models\License;
use App\Models\Tenant;
use Illuminate\Support\Str;

class LicenseService
{
    /**
     * Create a new tenant and generate an active license key.
     */
    public function createTenantWithLicense(array $tenantData, string $plan = 'starter', int $maxCounters = 0, int $durationDays = 365): array
    {
        $tenant = Tenant::create([
            'business_name' => $tenantData['business_name'],
            'owner_name'    => $tenantData['owner_name'],
            'owner_email'   => $tenantData['owner_email'],
            'owner_phone'   => $tenantData['owner_phone'] ?? null,
            'status'        => 'active',
            'notes'         => $tenantData['notes'] ?? null,
        ]);

        $licenseKey = License::generateKey();

        $license = License::create([
            'tenant_id'      => $tenant->id,
            'license_key'    => $licenseKey,
            'plan'           => $plan,
            'max_counters'   => $maxCounters, // 0 = unlimited
            'active_devices' => 0,
            'activated_at'   => null, // Will be set on first activation
            'expires_at'     => now()->addDays($durationDays),
            'status'         => 'active',
        ]);

        return [
            'tenant' => $tenant,
            'license' => $license,
        ];
    }

    /**
     * Validate and activate a license key on a client device.
     */
    public function activateKey(string $licenseKey, string $deviceId): array
    {
        $cleanKey = strtoupper(trim($licenseKey));
        $license = License::with('tenant')->where('license_key', $cleanKey)->first();

        if (!$license) {
            return [
                'success' => false,
                'message' => 'Invalid license key. Please verify your license key.',
                'code'    => 'INVALID_KEY',
            ];
        }

        if ($license->status === 'revoked') {
            return [
                'success' => false,
                'message' => 'This license key has been revoked. Please contact support.',
                'code'    => 'KEY_REVOKED',
            ];
        }

        if ($license->isExpired()) {
            $license->update(['status' => 'expired']);
            return [
                'success' => false,
                'message' => 'This license key has expired. Please renew your subscription.',
                'code'    => 'KEY_EXPIRED',
                'expires_at' => $license->expires_at ? $license->expires_at->toISOString() : null,
            ];
        }

        if (!$license->tenant || !$license->tenant->isActive()) {
            return [
                'success' => false,
                'message' => 'Associated business account is inactive or suspended.',
                'code'    => 'TENANT_INACTIVE',
            ];
        }

        // Set activated_at on first device activation
        if (!$license->activated_at) {
            $license->activated_at = now();
        }

        // Increment device count if under limit
        if ($license->canAddDevice()) {
            $license->active_devices += 1;
            $license->save();
        }

        // Check if tenant has staff/cashier users registered
        $hasUsers = $license->tenant->users()->where('role', '!=', 'admin')->exists();

        // Get or create primary admin user for this tenant to issue Sanctum API token
        $user = $license->tenant->users()->first();
        if (!$user) {
            $user = \App\Models\User::create([
                'tenant_id' => $license->tenant->id,
                'username'  => 'admin',
                'password'  => \Illuminate\Support\Facades\Hash::make('admin123'),
                'full_name' => $license->tenant->owner_name ?: 'System Admin',
                'role'      => 'admin',
            ]);
        }

        $token = $user->createToken($deviceId)->plainTextToken;

        return [
            'success' => true,
            'message' => 'License key validated and activated successfully.',
            'token'   => $token,
            'tenant' => [
                'id'            => $license->tenant->id,
                'business_name' => $license->tenant->business_name,
                'owner_name'    => $license->tenant->owner_name,
                'owner_email'   => $license->tenant->owner_email,
            ],
            'license' => [
                'key'            => $license->license_key,
                'plan'           => $license->plan,
                'status'         => $license->status,
                'expires_at'     => $license->expires_at ? $license->expires_at->toISOString() : null,
                'days_remaining' => $license->daysUntilExpiry(),
                'max_counters'   => $license->max_counters,
                'active_devices' => $license->active_devices,
            ],
            'has_users' => $hasUsers,
        ];
    }

    /**
     * Validate an existing active license session.
     */
    public function validateKey(string $licenseKey): array
    {
        $cleanKey = strtoupper(trim($licenseKey));
        $license = License::with('tenant')->where('license_key', $cleanKey)->first();

        if (!$license) {
            return [
                'valid'   => false,
                'message' => 'License key not found.',
                'code'    => 'INVALID_KEY',
            ];
        }

        if ($license->isExpired()) {
            $license->update(['status' => 'expired']);
            return [
                'valid'      => false,
                'message'    => 'Subscription expired.',
                'code'       => 'KEY_EXPIRED',
                'expires_at' => $license->expires_at ? $license->expires_at->toISOString() : null,
            ];
        }

        if ($license->status !== 'active') {
            return [
                'valid'   => false,
                'message' => "License is {$license->status}.",
                'code'    => 'KEY_' . strtoupper($license->status),
            ];
        }

        return [
            'valid' => true,
            'license' => [
                'key'            => $license->license_key,
                'plan'           => $license->plan,
                'status'         => $license->status,
                'expires_at'     => $license->expires_at ? $license->expires_at->toISOString() : null,
                'days_remaining' => $license->daysUntilExpiry(),
            ],
            'tenant' => [
                'id'            => $license->tenant->id,
                'business_name' => $license->tenant->business_name,
            ],
        ];
    }
}
