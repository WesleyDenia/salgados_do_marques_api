<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_tag_order')
            ->withTimestamps();
    }

    public function priceLists(): BelongsToMany
    {
        return $this->belongsToMany(PriceList::class, 'price_list_order_tag')
            ->withTimestamps();
    }
}
