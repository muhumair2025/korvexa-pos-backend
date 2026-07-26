<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Shift extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'uuid', 'tenant_id', 'shift_no', 'cashier_name',
        'shift_schedule', 'terminal_id', 'opening_float',
        'cash_sales', 'card_sales', 'khata_repayments',
        'pay_ins', 'pay_outs', 'expected_cash', 'actual_cash',
        'difference', 'status', 'opened_at', 'closed_at',
        'notes', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_float' => 'decimal:2',
            'cash_sales' => 'decimal:2',
            'card_sales' => 'decimal:2',
            'khata_repayments' => 'decimal:2',
            'pay_ins' => 'decimal:2',
            'pay_outs' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class, 'shift_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'CLOSED');
    }
}
