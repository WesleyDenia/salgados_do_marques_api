<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentHomeResource;
use App\Models\ContentHome;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContentHomeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $items = ContentHome::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return ContentHomeResource::collection($items);
    }
}
