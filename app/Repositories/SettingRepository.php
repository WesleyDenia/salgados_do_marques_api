<?php

namespace App\Repositories;

use App\Models\Setting;

class SettingRepository
{
    public function all()
    {
        return Setting::orderBy('key')->get();
    }

    public function findByKey(string $key): ?Setting
    {
        return Setting::where('key', $key)->first();
    }

    public function updateOrCreate(string $key, array $data): Setting
    {
        return Setting::updateOrCreate(['key' => $key], $data);
    }
}
