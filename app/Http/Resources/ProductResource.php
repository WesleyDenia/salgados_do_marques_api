<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => $this->price,
            'image_url'   => $this->image_url,
            'active'      => $this->active,
            'category'    => [
                'id'    => $this->category->id ?? null,
                'name'  => $this->category->name ?? null,
                'order' => $this->category->display_order ?? null,
            ],
        ];
    }
}
