<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Customer extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'uuid', 'tenant_id', 'name', 'phone', 'email',
        'address', 'city', 'loyalty_points', 'tier',
        'total_spent', 'credit_balance', 'notes', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'loyalty_points' => 'integer',
            'total_spent' => 'decimal:2',
            'credit_balance' => 'decimal:2',
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

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function khataTransactions()
    {
        return $this->hasMany(KhataTransaction::class, 'customer_id');
    }
}
