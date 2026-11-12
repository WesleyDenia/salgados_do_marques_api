<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class PriceEntry extends Model
{
    use HasFactory;

    protected $fillable = ['price_list_id', 'product_id', 'product_variant_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(function (self $entry): void {
            if (($entry->product_id === null) === ($entry->product_variant_id === null)) {
                throw ValidationException::withMessages([
                    'entries' => 'Cada preço deve apontar para um produto ou para um pack.',
                ]);
            }
        });
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
