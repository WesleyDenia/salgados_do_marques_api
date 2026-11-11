<?php

namespace App\Repositories;

use App\Models\Category;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $model) { parent::__construct($model); }

    public function paginate(
        array $with = [],
        ?callable $filter = null,
        int $defaultPerPage = 15,
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return parent::paginate($with, function ($query) use ($filter) {
            if ($filter) {
                $filter($query);
            }

            $query->orderBy('display_order')
                  ->orderBy('name');
        }, $defaultPerPage);
    }
}
