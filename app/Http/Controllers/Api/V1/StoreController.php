<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIndexRequest;
use App\Http\Resources\StoreResource;
use App\Services\StoreService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoreController extends Controller
{
    public function __construct(protected StoreService $stores) {}

    public function index(StoreIndexRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $stores = $this->stores->listForApi([
            'city' => $validated['city'] ?? null,
            'type' => $validated['type'] ?? null,
            'lat' => array_key_exists('lat', $validated) ? (float) $validated['lat'] : null,
            'lng' => array_key_exists('lng', $validated) ? (float) $validated['lng'] : null,
            'accepts_orders' => array_key_exists('accepts_orders', $validated)
                ? filter_var($validated['accepts_orders'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                : null,
        ]);

        return StoreResource::collection($stores);
    }
}
