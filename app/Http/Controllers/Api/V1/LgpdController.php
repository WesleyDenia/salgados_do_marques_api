<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class LgpdController extends Controller
{
    public function terms(): JsonResponse
    {
        $setting = Setting::where('key', 'LGPD_TERMS')->first();

        if (!$setting) {
            return response()->json(['message' => 'Termo LGPD não configurado'], 404);
        }

        $rawContent = (string) $setting->value;
        $hash = hash('sha256', $rawContent);
        $version = optional($setting->updated_at)->toISOString()
            ?? optional($setting->created_at)->toISOString();

        return response()->json([
            'key' => 'LGPD_TERMS',
            'content' => $this->normalizeContent($rawContent),
            'hash' => $hash,
            'version' => $version,
            'updated_at' => optional($setting->updated_at)->toISOString(),
        ]);
    }

    protected function normalizeContent(string $content): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);

        if (!str_contains($normalized, "\n")) {
            $normalized = preg_replace('/ {2,}(?=\S)/u', "\n", $normalized);
        }

        $normalized = preg_replace('/\n{3,}/', "\n\n", $normalized);

        return trim($normalized);
    }
}
