<?php

namespace App\Services;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LgpdService
{
    public function __construct(protected SettingService $settings) {}

    public function terms(): array
    {
        $setting = $this->settings->findRecord('LGPD_TERMS');

        if (!$setting) {
            throw new NotFoundHttpException('Termo LGPD não configurado');
        }

        $rawContent = (string) $setting->value;

        return [
            'key' => 'LGPD_TERMS',
            'content' => $this->normalizeContent($rawContent),
            'hash' => hash('sha256', $rawContent),
            'version' => optional($setting->updated_at)->toISOString()
                ?? optional($setting->created_at)->toISOString(),
            'updated_at' => optional($setting->updated_at)->toISOString(),
        ];
    }

    protected function normalizeContent(string $content): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);
        $normalized = preg_replace('/ {2,}(?=\S)/u', "\n", $normalized);

        $normalized = preg_replace('/\n{3,}/', "\n\n", $normalized);

        return trim($normalized);
    }
}
