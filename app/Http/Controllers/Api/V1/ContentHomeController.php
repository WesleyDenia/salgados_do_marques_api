<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentHomeResource;
use App\Services\ContentHomeService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContentHomeController extends Controller
{
    public function __construct(protected ContentHomeService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return ContentHomeResource::collection($this->service->listPublic());
    }
}
