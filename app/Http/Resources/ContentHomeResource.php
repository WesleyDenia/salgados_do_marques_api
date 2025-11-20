<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentHomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'text_body' => $this->text_body,
            'image_url' => $this->image_url,
            'type' => $this->type,
            'layout' => $this->layout,
            'component_name' => $this->component_name,
            'component_props' => $this->component_props,
            'cta_label' => $this->cta_label,
            'cta_url' => $this->cta_url,
            'cta_image_only' => (bool) $this->cta_image_only,
            'background_color' => $this->background_color,
            'display_order' => $this->display_order,
            'is_active' => $this->is_active,
            'publish_at' => $this->publish_at,
        ];
    }
}
