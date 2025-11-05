<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'body'             => $this->body,
            'code'             => $this->code,
            'image'            => $this->image,
            'discount_percent' => $this->discount_percent,
            'starts_at'        => optional($this->starts_at)->toIso8601String(),
            'ends_at'          => optional($this->ends_at)->toIso8601String(),
            'active'           => $this->active,
        ];
    }
}
