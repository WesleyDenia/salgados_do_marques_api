<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalPreparationSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'active',
        'display_order',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function productSettings(): HasMany
    {
        return $this->hasMany(ProductPreparationSetting::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(OrderPreparationAllocation::class);
    }
}
