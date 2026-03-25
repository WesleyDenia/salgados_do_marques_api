<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartnerCampaignStoreRequest;
use App\Models\Coupon;
use App\Models\Partner;
use App\Models\PartnerCampaign;

class PartnerCampaignController extends Controller
{
    public function index()
    {
        $campaigns = PartnerCampaign::query()
            ->with(['partner', 'coupon'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.partner-campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('admin.partner-campaigns.create', [
            'campaign' => new PartnerCampaign([
                'active' => true,
            ]),
            'partners' => $this->partnerOptions(),
            'coupons' => $this->couponOptions(),
        ]);
    }

    public function store(PartnerCampaignStoreRequest $request)
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active');

        PartnerCampaign::create($data);

        return redirect()
            ->route('admin.partner-campaigns.index')
            ->with('status', 'Campanha criada com sucesso.');
    }

    public function edit(PartnerCampaign $partnerCampaign)
    {
        return view('admin.partner-campaigns.edit', [
            'campaign' => $partnerCampaign,
            'partners' => $this->partnerOptions(),
            'coupons' => $this->couponOptions(),
        ]);
    }

    public function update(PartnerCampaignStoreRequest $request, PartnerCampaign $partnerCampaign)
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active');

        $partnerCampaign->update($data);

        return redirect()
            ->route('admin.partner-campaigns.index')
            ->with('status', 'Campanha atualizada com sucesso.');
    }

    public function destroy(PartnerCampaign $partnerCampaign)
    {
        $partnerCampaign->delete();

        return redirect()
            ->route('admin.partner-campaigns.index')
            ->with('status', 'Campanha removida com sucesso.');
    }

    protected function partnerOptions()
    {
        return Partner::query()
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function couponOptions()
    {
        return Coupon::query()
            ->orderBy('title')
            ->pluck('title', 'id');
    }
}
