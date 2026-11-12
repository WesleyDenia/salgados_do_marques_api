<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'priority', 'starts_at', 'ends_at', 'active'];

    protected $casts = [
        'priority' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(PriceEntry::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(OrderTag::class, 'price_list_order_tag')->withTimestamps();
    }
}
