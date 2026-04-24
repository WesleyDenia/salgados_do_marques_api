<?php

namespace App\Services\Erp\Vendus;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Throwable;

class VendusLogSanitizer
{
    public static function response(
        ?Response $response,
        string $endpoint,
        array $context = [],
        ?Throwable $exception = null
    ): array {
        $summary = [
            'endpoint' => $endpoint,
            'status' => $response?->status(),
        ];

        foreach (['entity_type', 'entity_id', 'external_id', 'external_code', 'error_code'] as $key) {
            if (array_key_exists($key, $context) && $context[$key] !== null && $context[$key] !== '') {
                $summary[$key] = $context[$key];
            }
        }

        $message = self::messageFromResponse($response) ?: $exception?->getMessage();

        if ($message) {
            $summary['message'] = self::sanitizeMessage($message);
        }

        return $summary;
    }

    public static function sanitizeMessage(string $message, int $limit = 300): string
    {
        $message = preg_replace('/[\w.\-+]+@[\w.\-]+\.\w+/', '[email]', $message) ?? $message;
        $message = preg_replace('/\b(?:\+?\d[\d\s().-]{6,}\d)\b/', '[number]', $message) ?? $message;
        $message = preg_replace('/Bearer\s+[A-Za-z0-9._\-]+/i', 'Bearer [token]', $message) ?? $message;

        return Str::limit(trim($message), $limit, '...');
    }

    protected static function messageFromResponse(?Response $response): ?string
    {
        if (!$response) {
            return null;
        }

        $json = $response->json();

        if (is_array($json)) {
            $error = $json['errors'][0] ?? $json['error'] ?? null;

            if (is_array($error)) {
                return trim(($error['code'] ?? '') . ' ' . ($error['message'] ?? ''));
            }

            if (is_string($error)) {
                return $error;
            }

            if (isset($json['message']) && is_string($json['message'])) {
                return $json['message'];
            }
        }

        return 'HTTP ' . $response->status();
    }
}
