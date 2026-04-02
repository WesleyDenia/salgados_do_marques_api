<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartnerCampaignStoreRequest;
use App\Models\PartnerCampaign;
use App\Services\AdminCouponService;
use App\Services\PartnerCampaignService;
use App\Services\PartnerService;

class PartnerCampaignController extends Controller
{
    public function __construct(
        protected PartnerCampaignService $campaigns,
        protected PartnerService $partners,
        protected AdminCouponService $coupons,
    ) {}

    public function index()
    {
        return view('admin.partner-campaigns.index', [
            'campaigns' => $this->campaigns->listAdmin(),
        ]);
    }

    public function create()
    {
        return view('admin.partner-campaigns.create', [
            'campaign' => new PartnerCampaign([
                'active' => true,
            ]),
            'partners' => $this->partners->options(),
            'coupons' => $this->coupons->options(),
        ]);
    }

    public function store(PartnerCampaignStoreRequest $request)
    {
        $this->campaigns->createAdmin($request->validated());

        return redirect()
            ->route('admin.partner-campaigns.index')
            ->with('status', 'Campanha criada com sucesso.');
    }

    public function edit(PartnerCampaign $partnerCampaign)
    {
        return view('admin.partner-campaigns.edit', [
            'campaign' => $partnerCampaign,
            'partners' => $this->partners->options(),
            'coupons' => $this->coupons->options(),
        ]);
    }

    public function update(PartnerCampaignStoreRequest $request, PartnerCampaign $partnerCampaign)
    {
        $this->campaigns->updateAdmin($partnerCampaign, $request->validated());

        return redirect()
            ->route('admin.partner-campaigns.index')
            ->with('status', 'Campanha atualizada com sucesso.');
    }

    public function destroy(PartnerCampaign $partnerCampaign)
    {
        $this->campaigns->deleteAdmin($partnerCampaign);

        return redirect()
            ->route('admin.partner-campaigns.index')
            ->with('status', 'Campanha removida com sucesso.');
    }
}
