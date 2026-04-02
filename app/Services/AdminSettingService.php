<?php

namespace App\Services;

use App\Models\Setting;
use App\Repositories\SettingRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminSettingService
{
    public function __construct(protected SettingRepository $repository) {}

    public function list(): LengthAwarePaginator
    {
        return Setting::query()
            ->orderBy('key')
            ->paginate(20);
    }

    public function create(array $data): Setting
    {
        $payload = $this->normalizePayload($data, true);
        $setting = Setting::create($payload);
        $this->forgetCache($setting->key);

        return $setting;
    }

    public function update(Setting $setting, array $data): bool
    {
        if (!$setting->editable) {
            return false;
        }

        $oldKey = $setting->key;
        $payload = $this->normalizePayload($data, false);
        $setting->fill($payload)->save();

        $this->forgetCache($oldKey);
        $this->forgetCache($setting->key);

        return true;
    }

    protected function normalizePayload(array $data, bool $defaultEditable): array
    {
        $value = $data['value'] ?? null;

        if ($value !== null) {
            $this->validateValueByType($value, $data['type'], false);
            $value = $this->castValue($value, $data['type']);
        }

        return [
            'key' => $data['key'],
            'type' => $data['type'],
            'value' => $value,
            'editable' => array_key_exists('editable', $data) ? (bool) $data['editable'] : $defaultEditable,
        ];
    }

    protected function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => $this->sanitizeJson($value),
            default => $value,
        };
    }

    protected function validateValueByType(mixed $value, string $type, bool $required): void
    {
        $requiredRule = $required ? ['required'] : ['nullable'];

        $typedRules = match ($type) {
            'integer' => ['integer'],
            'boolean' => ['boolean'],
            'json' => [function ($attribute, $candidate, $fail) {
                if (is_array($candidate)) {
                    return;
                }

                if (!is_string($candidate)) {
                    $fail('JSON inválido. Verifique o formato.');

                    return;
                }

                json_decode($candidate, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail('JSON inválido. Verifique o formato.');
                }
            }],
            default => ['string'],
        };

        Validator::make(
            ['value' => $value],
            ['value' => array_merge($requiredRule, $typedRules)]
        )->validate();
    }

    protected function sanitizeJson(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        throw ValidationException::withMessages([
            'value' => 'JSON inválido. Verifique o formato.',
        ]);
    }

    protected function forgetCache(string $key): void
    {
        Cache::forget("setting_{$key}");
    }
}
