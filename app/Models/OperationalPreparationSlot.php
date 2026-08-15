<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalPreparationSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active',
        'display_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function productSettings(): HasMany
    {
        return $this->hasMany(ProductPreparationSetting::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(OrderPreparationAllocation::class);
    }
}
