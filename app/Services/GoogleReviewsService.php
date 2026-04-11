<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleReviewsService
{
    public function listPublic(): array
    {
        $config = config('services.google_places', []);
        $apiKey = (string) ($config['api_key'] ?? '');
        $placeId = (string) ($config['place_id'] ?? '');
        $cacheHours = max(1, (int) ($config['cache_hours'] ?? 6));

        if ($apiKey === '' || $placeId === '') {
            Log::warning('[GoogleReviewsService] Credenciais do Google Places não configuradas');

            return [];
        }

        return Cache::remember(
            sprintf('google_places_reviews:%s', $placeId),
            now()->addHours($cacheHours),
            fn (): array => $this->fetchAndTransform($config, $apiKey, $placeId)
        );
    }

    protected function fetchAndTransform(array $config, string $apiKey, string $placeId): array
    {
        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => implode(',', [
                    'displayName',
                    'googleMapsUri',
                    'reviews.rating',
                    'reviews.publishTime',
                    'reviews.relativePublishTimeDescription',
                    'reviews.text',
                    'reviews.originalText',
                    'reviews.googleMapsUri',
                    'reviews.authorAttribution.displayName',
                    'reviews.authorAttribution.uri',
                    'reviews.authorAttribution.photoUri',
                ]),
            ])
                ->timeout(10)
                ->acceptJson()
                ->get(rtrim((string) ($config['base_url'] ?? 'https://places.googleapis.com/v1'), '/') . "/places/{$placeId}", [
                    'languageCode' => (string) ($config['language_code'] ?? 'pt-PT'),
                    'regionCode' => (string) ($config['region_code'] ?? 'PT'),
                ]);

            if (!$response->successful()) {
                Log::warning('[GoogleReviewsService] Falha ao consultar Google Places', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $reviews = collect($response->json('reviews', []));
            $minimumRating = max(1, min(5, (int) ($config['minimum_rating'] ?? 4)));
            $limit = max(1, (int) ($config['limit'] ?? 4));
            $placeGoogleMapsUri = data_get($response->json(), 'googleMapsUri');

            return $reviews
                ->filter(fn (array $review): bool => (int) data_get($review, 'rating', 0) >= $minimumRating)
                ->map(fn (array $review): array => [
                    'author_name' => (string) data_get($review, 'authorAttribution.displayName', 'Cliente Google'),
                    'author_url' => data_get($review, 'authorAttribution.uri'),
                    'author_photo_url' => data_get($review, 'authorAttribution.photoUri'),
                    'rating' => (int) data_get($review, 'rating', 0),
                    'text' => trim((string) (data_get($review, 'text.text') ?? data_get($review, 'originalText.text') ?? '')),
                    'published_at' => data_get($review, 'publishTime'),
                    'relative_time_description' => (string) data_get($review, 'relativePublishTimeDescription', ''),
                    'google_maps_url' => data_get($review, 'googleMapsUri') ?? $placeGoogleMapsUri,
                ])
                ->filter(fn (array $review): bool => $review['text'] !== '')
                ->take($limit)
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            Log::error('[GoogleReviewsService] Erro inesperado ao consultar Google Places', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
