<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GoogleReviewsService;
use Illuminate\Http\JsonResponse;

class GoogleReviewsController extends Controller
{
    public function __construct(protected GoogleReviewsService $reviews) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->reviews->listPublic(),
            'meta' => [
                'source' => 'Google Maps',
                'ordered_by' => 'relevance',
                'minimum_rating' => (int) config('services.google_places.minimum_rating', 4),
            ],
        ]);
    }
}
