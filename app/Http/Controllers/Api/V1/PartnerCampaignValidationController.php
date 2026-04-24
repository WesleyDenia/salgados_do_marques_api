<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerCampaignValidateRequest;
use App\Http\Resources\UserCouponResource;
use App\Services\PartnerCampaignService;

class PartnerCampaignValidationController extends Controller
{
    public function __construct(
        protected PartnerCampaignService $service,
    ) {}

    public function store(PartnerCampaignValidateRequest $request)
    {
        $userCoupon = $this->service->validateCode(
            $request->user(),
            $request->validated('code')
        );

        return (new UserCouponResource($userCoupon))
            ->response()
            ->setStatusCode(200);
    }
}
