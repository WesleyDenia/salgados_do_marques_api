<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UrbanCampaignAnswerRequest;
use App\Http\Requests\UrbanCampaignQrCodeShowRequest;
use App\Http\Resources\UrbanCampaignAnswerResource;
use App\Http\Resources\UrbanCampaignQrCodeResource;
use App\Services\UrbanCampaignService;

class UrbanCampaignController extends Controller
{
    public function __construct(
        protected UrbanCampaignService $service,
    ) {}

    public function show(UrbanCampaignQrCodeShowRequest $request): UrbanCampaignQrCodeResource
    {
        return new UrbanCampaignQrCodeResource(
            $this->service->getChallengePayload($request->validated('code'))
        );
    }

    public function answer(UrbanCampaignAnswerRequest $request): UrbanCampaignAnswerResource
    {
        $validated = $request->validated();

        return new UrbanCampaignAnswerResource(
            $this->service->answerChallenge(
                $validated['code'],
                $validated['question_id'],
                $validated['response_id'],
            )
        );
    }
}
