<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerSearchRequest;
use App\Services\CustomerDirectoryService;

class CustomerSearchController extends Controller
{
    public function __construct(protected CustomerDirectoryService $customers) {}

    public function __invoke(CustomerSearchRequest $request)
    {
        $term = $request->validated('query');

        return response()->json([
            'data' => $this->customers->search($term),
        ]);
    }
}
