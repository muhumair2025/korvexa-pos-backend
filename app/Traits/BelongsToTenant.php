<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * BelongsToTenant Trait
 *
 * Automatically scopes all queries to the authenticated user's tenant_id.
 * Applied via a global scope so every SELECT, UPDATE, DELETE is tenant-isolated.
 * Also auto-fills tenant_id on model creation.
 */
trait BelongsToTenant
{
    /**
     * Boot the trait: register the global scope and creating event.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Auto-scope all queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = static::resolveCurrentTenantId();
            if ($tenantId) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        // Auto-fill tenant_id when creating a new record
        static::creating(function (Model $model) {
            if (!$model->tenant_id) {
                $tenantId = static::resolveCurrentTenantId();
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    private static bool $isResolving = false;

    /**
     * Resolve the current tenant ID from the authenticated user or request context.
     */
    protected static function resolveCurrentTenantId(): ?int
    {
        // Priority 1: Tenant ID merged on request context by TenantScope middleware
        if (request() && request()->has('_tenant_id')) {
            return (int) request()->input('_tenant_id');
        }

        // Prevent infinite recursion if auth('sanctum')->user() queries a model that uses BelongsToTenant
        if (static::$isResolving) {
            return null;
        }

        static::$isResolving = true;

        try {
            // Priority 2: Check Sanctum API guard user directly
            if (auth('sanctum')->check()) {
                $user = auth('sanctum')->user();
                if ($user && !empty($user->tenant_id)) {
                    return (int) $user->tenant_id;
                }
            }

            // Priority 3: Check current request user
            if (request() && request()->user() && !empty(request()->user()->tenant_id)) {
                return (int) request()->user()->tenant_id;
            }

            // Priority 4: Check default auth guard
            if (auth()->check() && auth()->user() && !empty(auth()->user()->tenant_id)) {
                return (int) auth()->user()->tenant_id;
            }
        } catch (\Throwable $e) {
            // ignore
        } finally {
            static::$isResolving = false;
        }

        return null;
    }

    /**
     * Query without tenant scoping (for admin/superadmin operations).
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }

    /**
     * Relationship: this model belongs to a Tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
