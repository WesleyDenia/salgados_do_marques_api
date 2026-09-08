<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UrbanCampaignAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $qrCode = $this->resource['qr_code'];
        $question = $this->resource['question'];
        $response = $this->resource['response'];

        return [
            'qr_code_id' => $qrCode->id,
            'type' => $qrCode->type,
            'question_id' => $question->id,
            'response_id' => $response->id,
            'is_correct' => (bool) $this->resource['is_correct'],
            'reward_percent' => (int) $this->resource['reward_percent'],
            'collection_label' => $question->collection_label,
        ];
    }
}
