<?php

namespace App\Services;

use App\Repositories\PartnerRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Partner;

class PartnerService
{
    public function __construct(
        protected PartnerRepository $partners,
    ) {}

    public function listActive(): Collection
    {
        return $this->partners->activeForApp();
    }

    public function showActive(int $id): Partner
    {
        return $this->partners->activeById($id);
    }
}
