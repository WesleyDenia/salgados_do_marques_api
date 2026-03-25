<?php

namespace App\Repositories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Collection;

class PartnerRepository extends BaseRepository
{
    public function __construct(Partner $model)
    {
        parent::__construct($model);
    }

    public function activeForApp(): Collection
    {
        return $this->query()
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    public function activeById(int $id): Partner
    {
        return $this->query()
            ->where('active', true)
            ->findOrFail($id);
    }
}
