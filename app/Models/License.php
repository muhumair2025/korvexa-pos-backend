<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * License Model (Central — no tenant scoping)
 *
 * Represents a license key tied to a tenant with subscription plan and expiry.
 * License keys are the primary mechanism for tenant identification on device activation.
 */
class License extends Model
{
    protected $fillable = [
        'tenant_id',
        'license_key',
        'plan',
        'max_counters',
        'active_devices',
        'activated_at',
        'expires_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'max_counters' => 'integer',
            'active_devices' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('license_key', $key);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') return true;
        if ($this->expires_at && $this->expires_at->isPast()) return true;
        return false;
    }

    public function canAddDevice(): bool
    {
        // 0 = unlimited
        if ($this->max_counters === 0) return true;
        return $this->active_devices < $this->max_counters;
    }

    public function daysUntilExpiry(): ?int
    {
        if (!$this->expires_at) return null;
        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    /**
     * Generate a unique license key in format: APEX-XXXX-XXXX-XXXX
     */
    public static function generateKey(): string
    {
        do {
            $segments = [];
            for ($i = 0; $i < 3; $i++) {
                $segments[] = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            }
            $key = 'APEX-' . implode('-', $segments);
        } while (static::where('license_key', $key)->exists());

        return $key;
    }
}
