<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id','name','description','price','image_url','active'
    ];

    protected $casts = [
        'price'=>'decimal:2',
        'active'=>'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function flavors(): BelongsToMany
    {
        return $this->belongsToMany(Flavor::class)
            ->withTimestamps();
    }

    public function allowedFlavors(): BelongsToMany
    {
        return $this->flavors()
            ->where('flavors.active', true)
            ->orderBy('flavors.display_order')
            ->orderBy('flavors.name');
    }
}
