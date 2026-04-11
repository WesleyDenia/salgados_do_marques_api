<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleReviewsFeatureTest extends TestCase
{
    public function test_google_reviews_endpoint_filters_by_rating_and_limits_results(): void
    {
        Config::set('services.google_places.api_key', 'test-key');
        Config::set('services.google_places.place_id', 'places/test-place');
        Config::set('services.google_places.minimum_rating', 4);
        Config::set('services.google_places.limit', 4);
        Config::set('cache.default', 'array');
        Cache::flush();

        Http::fake([
            'https://places.googleapis.com/v1/places/places/test-place*' => Http::response([
                'googleMapsUri' => 'https://maps.google.com/?cid=place',
                'reviews' => [
                    $this->reviewPayload('Ana', 5, 'Excelente atendimento', '3 semanas atrás', 'https://maps.google.com/review-1'),
                    $this->reviewPayload('Bruno', 4, 'Muito bom', '1 mês atrás', 'https://maps.google.com/review-2'),
                    $this->reviewPayload('Carla', 3, 'Razoável', '2 meses atrás', 'https://maps.google.com/review-3'),
                    $this->reviewPayload('Diogo', 5, 'Produto impecável', '2 semanas atrás', 'https://maps.google.com/review-4'),
                    $this->reviewPayload('Eva', 4, 'Voltarei a comprar', '5 dias atrás', 'https://maps.google.com/review-5'),
                    $this->reviewPayload('Fábio', 5, 'Quinta review elegível extra', '1 semana atrás', 'https://maps.google.com/review-6'),
                ],
            ]),
        ]);

        $response = $this->getJson('/api/v1/google-reviews');

        $response->assertOk()
            ->assertJsonPath('meta.source', 'Google Maps')
            ->assertJsonPath('meta.ordered_by', 'relevance')
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.author_name', 'Ana')
            ->assertJsonPath('data.1.author_name', 'Bruno')
            ->assertJsonPath('data.2.author_name', 'Diogo')
            ->assertJsonPath('data.3.author_name', 'Eva');

        Http::assertSentCount(1);
    }

    public function test_google_reviews_endpoint_returns_cached_payload(): void
    {
        Config::set('services.google_places.api_key', 'test-key');
        Config::set('services.google_places.place_id', 'places/test-place');
        Config::set('cache.default', 'array');
        Cache::flush();

        Http::fake([
            'https://places.googleapis.com/v1/places/places/test-place*' => Http::response([
                'reviews' => [
                    $this->reviewPayload('Ana', 5, 'Excelente atendimento', '3 semanas atrás'),
                ],
            ]),
        ]);

        $this->getJson('/api/v1/google-reviews')->assertOk();
        $this->getJson('/api/v1/google-reviews')->assertOk()->assertJsonCount(1, 'data');

        Http::assertSentCount(1);
    }

    private function reviewPayload(
        string $author,
        int $rating,
        string $text,
        string $relativeTime,
        ?string $googleMapsUri = null
    ): array {
        return [
            'rating' => $rating,
            'publishTime' => '2026-04-01T10:00:00Z',
            'relativePublishTimeDescription' => $relativeTime,
            'text' => ['text' => $text],
            'googleMapsUri' => $googleMapsUri,
            'authorAttribution' => [
                'displayName' => $author,
                'uri' => 'https://maps.google.com/profile/' . urlencode($author),
                'photoUri' => 'https://example.com/photo.jpg',
            ],
        ];
    }
}
