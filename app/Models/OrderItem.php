<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'parent_order_item_id',
        'product_id',
        'variant_id',
        'name_snapshot',
        'price_snapshot',
        'quantity',
        'options',
        'total',
    ];

    protected $casts = [
        'price_snapshot' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
        'parent_order_item_id' => 'integer',
        'options' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function parentOrderItem()
    {
        return $this->belongsTo(self::class, 'parent_order_item_id');
    }

    public function derivedItems()
    {
        return $this->hasMany(self::class, 'parent_order_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function partialWithdrawals()
    {
        return $this->hasMany(OrderPartialWithdrawal::class, 'parent_order_item_id')
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id');
    }

    public function preparationAllocations()
    {
        return $this->hasMany(OrderPreparationAllocation::class);
    }
}
