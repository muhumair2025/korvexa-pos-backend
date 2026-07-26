<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

/**
 * User Model (Tenant-Scoped)
 *
 * POS terminal users: Administrators, Managers, Cashiers, Inventory Clerks.
 * Scoped to tenant_id for full data isolation between clients.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'username',
        'password',
        'full_name',
        'role',
        'permissions',
        'shift_schedule',
        'max_cash_limit',
        'avatar',
        'synced_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'permissions' => 'array',
            'max_cash_limit' => 'decimal:2',
            'synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ── Boot ─────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function (User $user) {
            if (!$user->uuid) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────

    public function orders()
    {
        return $this->hasMany(Order::class, 'cashier_name', 'full_name');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return in_array($this->role, ['Administrator', 'Admin', 'Manager']);
    }

    public function isCashier(): bool
    {
        return $this->role === 'Cashier';
    }

    /**
     * Get effective permissions based on role.
     * Admins/Managers always get full permissions.
     */
    public function getEffectivePermissions(): array
    {
        if ($this->isAdmin()) {
            return [
                'register', 'inventory', 'orders', 'customers',
                'khata', 'suppliers', 'cash_drawer', 'overview',
                'staff', 'settings',
            ];
        }

        $perms = $this->permissions ?? [];

        if (empty($perms)) {
            if ($this->role === 'Cashier') {
                return ['register', 'orders', 'customers', 'khata', 'cash_drawer'];
            }
            if ($this->role === 'Inventory Clerk') {
                return ['inventory', 'suppliers'];
            }
        }

        return $perms;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getEffectivePermissions());
    }
}
