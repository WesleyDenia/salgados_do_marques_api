<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartnerPublicResource;
use App\Http\Resources\PartnerResource;
use App\Services\PartnerService;

class PartnerController extends Controller
{
    public function __construct(
        protected PartnerService $service,
    ) {}

    public function index()
    {
        return PartnerResource::collection($this->service->listActive());
    }

    public function publicIndex()
    {
        return PartnerPublicResource::collection($this->service->listActive());
    }

    public function show(int $partner)
    {
        return new PartnerResource($this->service->showActive($partner));
    }

    public function publicShow(int $partner)
    {
        return new PartnerPublicResource($this->service->showActive($partner));
    }
}
