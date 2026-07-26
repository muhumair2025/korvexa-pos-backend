<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SyncLog Model (Central — queried by admin, but has tenant_id)
 *
 * Tracks every sync push/pull event per tenant and device.
 * Used for monitoring and debugging sync issues.
 */
class SyncLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'device_id',
        'direction',
        'tables_synced',
        'records_pushed',
        'records_pulled',
        'conflicts_resolved',
        'status',
        'error_message',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'tables_synced' => 'array',
            'records_pushed' => 'integer',
            'records_pulled' => 'integer',
            'conflicts_resolved' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
