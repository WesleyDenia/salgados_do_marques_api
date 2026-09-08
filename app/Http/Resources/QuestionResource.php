<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code_type' => $this->code_type,
            'question' => $this->question,
            'secret_number' => $this->secret_number,
            'collection_label' => $this->collection_label,
            'eyebrow' => $this->eyebrow,
            'riddle' => preg_split('/\r\n|\r|\n/', trim((string) $this->question)) ?: [],
            'responses' => QuestionResponseResource::collection($this->whenLoaded('responses')),
        ];
    }
}
