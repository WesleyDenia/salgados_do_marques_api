<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model) { $this->model = $model; }

    public function query() { return $this->model->newQuery(); }

    public function paginate(array $with = [], ?callable $filter = null): LengthAwarePaginator
    {
        $q = $this->query()->with($with);
        if ($filter) { $filter($q); }
        return $q->paginate(request('per_page', 15));
    }

    public function find($id, array $with = []) { return $this->query()->with($with)->findOrFail($id); }
    public function create(array $data) { return $this->model->create($data); }
    public function update(Model $instance, array $data) { $instance->fill($data)->save(); return $instance; }
    public function delete(Model $instance) { return $instance->delete(); }
}
