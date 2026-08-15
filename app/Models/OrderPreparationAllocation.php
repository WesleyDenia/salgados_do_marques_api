<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPreparationAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'operational_preparation_slot_id',
        'scheduled_slot',
        'scheduled_for_date',
        'batch_index',
        'batch_units',
        'preparation_time_seconds',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'order_item_id' => 'integer',
        'product_id' => 'integer',
        'operational_preparation_slot_id' => 'integer',
        'scheduled_for_date' => 'date:Y-m-d',
        'batch_index' => 'integer',
        'batch_units' => 'integer',
        'preparation_time_seconds' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function preparationSlot()
    {
        return $this->belongsTo(OperationalPreparationSlot::class, 'operational_preparation_slot_id');
    }
}
