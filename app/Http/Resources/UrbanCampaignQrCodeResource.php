<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UrbanCampaignQrCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $qrCode = $this->resource['qr_code'];
        $question = $this->resource['question'];

        return [
            'id' => $qrCode->id,
            'type' => $qrCode->type,
            'code' => $qrCode->code,
            'question' => new QuestionResource($question),
        ];
    }
}
