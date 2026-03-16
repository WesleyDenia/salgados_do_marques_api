<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model) { parent::__construct($model); }

    public function publicList()
    {
        return $this->paginate(['category', 'variants', 'allowedFlavors'], function($q){
            $q->where('active', true);
            if ($cid = request('category_id')) { $q->where('category_id', $cid); }
            if ($s = request('search')) {
                $q->where(function($qq) use ($s){
                    $qq->where('name','like',"%$s%")->orWhere('description','like',"%$s%");
                });
            }
        });
    }

    public function findActiveForOrder(array $productIds): Collection
    {
        if ($productIds === []) {
            return new Collection();
        }

        return Product::query()
            ->with('allowedFlavors')
            ->whereIn('id', $productIds)
            ->where('active', true)
            ->get()
            ->keyBy('id');
    }

    public function findActiveVariantsForOrder(array $variantIds): Collection
    {
        if ($variantIds === []) {
            return new Collection();
        }

        return ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->where('active', true)
            ->get()
            ->keyBy('id');
    }
}
