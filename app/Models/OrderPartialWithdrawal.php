<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPartialWithdrawal extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'parent_order_id',
        'parent_order_item_id',
        'generated_order_id',
        'created_by',
        'requested_units',
        'flavor_ids',
        'scheduled_at',
        'status',
        'notes',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'requested_units' => 'integer',
        'flavor_ids' => 'array',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function parentOrder()
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    public function parentOrderItem()
    {
        return $this->belongsTo(OrderItem::class, 'parent_order_item_id');
    }

    public function generatedOrder()
    {
        return $this->belongsTo(Order::class, 'generated_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
