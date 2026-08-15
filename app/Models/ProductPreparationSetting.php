<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPreparationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'operational_preparation_slot_id',
        'product_id',
        'batch_size',
        'preparation_time_seconds',
    ];

    protected $casts = [
        'operational_preparation_slot_id' => 'integer',
        'product_id' => 'integer',
        'batch_size' => 'integer',
        'preparation_time_seconds' => 'integer',
    ];

    public function slot()
    {
        return $this->belongsTo(OperationalPreparationSlot::class, 'operational_preparation_slot_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
