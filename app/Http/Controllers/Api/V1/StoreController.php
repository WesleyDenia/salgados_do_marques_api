<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
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
        $city = $request->input('city');
        $type = $request->input('type');

        $query = Store::query()
            ->where('is_active', true);

        if ($city) {
            $query->where('city', 'like', '%' . $city . '%');
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($request->filled('accepts_orders')) {
            $acceptsOrders = $request->boolean('accepts_orders', null);
            if ($acceptsOrders !== null) {
                $query->where('accepts_orders', $acceptsOrders);
            }
        }

        if ($lat !== null && $lng !== null) {
            $query->select(['stores.*'])->selectRaw(
                '2 * 6371 * ASIN(SQRT(POWER(SIN(RADIANS(? - latitude) / 2), 2) + COS(RADIANS(latitude)) * COS(RADIANS(?)) * POWER(SIN(RADIANS(? - longitude) / 2), 2))) AS distance_km',
                [$lat, $lat, $lng]
            );

            $query->orderBy('distance_km');
        } else {
            $query->orderBy('name');
        }

        $stores = $query->get();

        return StoreResource::collection($stores);
    }
}
