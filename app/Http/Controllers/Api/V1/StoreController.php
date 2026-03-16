<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreResource;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    public function __construct(protected StoreService $stores) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $validator = Validator::make($request->all(), [
            'city' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:principal,revenda'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'accepts_orders' => ['nullable', 'boolean'],
        ]);

        $validator->validate();

        $lat = $request->float('lat');
        $lng = $request->float('lng');
        $stores = $this->stores->listForApi([
            'city' => $request->input('city'),
            'type' => $request->input('type'),
            'lat' => $lat,
            'lng' => $lng,
            'accepts_orders' => $request->filled('accepts_orders')
                ? $request->boolean('accepts_orders')
                : null,
        ]);

        return StoreResource::collection($stores);
    }
}
