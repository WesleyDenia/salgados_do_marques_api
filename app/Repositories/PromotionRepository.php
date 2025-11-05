<?php

namespace App\Repositories;

use App\Models\Promotion;

class PromotionRepository extends BaseRepository
{
    public function __construct(Promotion $model) { parent::__construct($model); }

    public function publicList()
    {
        $now = now();
        return $this->paginate([], function($q) use ($now){
            $q->where('active', true)
              ->where(function($q) use ($now){
                  $q->whereNull('starts_at')->orWhere('starts_at','<=',$now);
              })
              ->where(function($q) use ($now){
                  $q->whereNull('ends_at')->orWhere('ends_at','>=',$now);
              });
        });
    }
}
