<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model) { parent::__construct($model); }

    public function publicList()
    {
        return $this->paginate(['category'], function($q){
            $q->where('active', true);
            if ($cid = request('category_id')) { $q->where('category_id', $cid); }
            if ($s = request('search')) {
                $q->where(function($qq) use ($s){
                    $qq->where('name','like',"%$s%")->orWhere('description','like',"%$s%");
                });
            }
        });
    }
}
