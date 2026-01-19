<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'unit_count',
        'max_flavors',
        'price',
        'active',
        'display_order',
    ];

    protected $casts = [
        'unit_count' => 'integer',
        'max_flavors' => 'integer',
        'price' => 'decimal:2',
        'active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
