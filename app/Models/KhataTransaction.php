<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KhataTransaction extends Model
{
    use BelongsToTenant;

    protected $table = 'khata_transactions';

    protected $fillable = [
        'uuid', 'tenant_id', 'customer_id', 'customer_name',
        'type', 'amount', 'previous_balance', 'new_balance',
        'payment_method', 'notes', 'items_json', 'receipt_json',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'previous_balance' => 'decimal:2',
            'new_balance' => 'decimal:2',
            'items_json' => 'array',
            'receipt_json' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->uuid) $model->uuid = (string) Str::uuid();
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeDebts($query)
    {
        return $query->where('type', 'DEBT_ADD');
    }

    public function scopeRepayments($query)
    {
        return $query->where('type', 'REPAYMENT');
    }
}
