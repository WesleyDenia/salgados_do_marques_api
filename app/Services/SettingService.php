<?php

namespace App\Services;

use App\Repositories\SettingRepository;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public function __construct(protected SettingRepository $repository) {}

    public function get(string $key, $default = null)
    {
        return Cache::rememberForever("setting_{$key}", function () use ($key, $default) {
            return optional($this->repository->findByKey($key))->value ?? $default;
        });
    }

    public function set(string $key, $value)
    {
        Cache::forget("setting_{$key}");

        return $this->repository->updateOrCreate($key, [
            'value' => $value,
        ]);
    }

    public function all()
    {
        return $this->repository->all();
    }
}
