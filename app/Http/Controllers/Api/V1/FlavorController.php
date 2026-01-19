<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlavorResource;
use App\Models\Flavor;

class FlavorController extends Controller
{
    public function index()
    {
        $flavors = Flavor::query()
            ->where('active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return FlavorResource::collection($flavors);
    }
}
