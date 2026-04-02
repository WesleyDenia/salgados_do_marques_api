<?php

namespace App\Repositories;

use App\Models\ContentHome;
use Illuminate\Database\Eloquent\Collection;

class ContentHomeRepository
{
    public function publicIndex(): Collection
    {
        return ContentHome::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }
}
