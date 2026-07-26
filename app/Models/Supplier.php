<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'uuid', 'tenant_id', 'name', 'contact_person',
        'phone', 'email', 'address', 'category',
        'lead_time_days', 'notes', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'lead_time_days' => 'integer',
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

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
