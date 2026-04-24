<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserCouponResource extends JsonResource
{
    public function toArray($request): array
    {
        $partner = $this->partnerCampaign?->partner;
        $latestTask = $this->relationLoaded('latestErpTask') ? $this->latestErpTask : null;
        $originType = $this->type === 'partner'
            ? 'partner'
            : ($this->type === 'loyalty' ? 'loyalty' : 'regular');

        return [
            'id'          => $this->id,
            'external_code' => $this->external_code,
            'external_id'   => $this->external_id,
            'type'         => $this->type,
            'loyalty_reward_id' => $this->loyalty_reward_id,
            'partner_campaign_id' => $this->partner_campaign_id,
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'expires_at'  => optional($this->expires_at)->toIso8601String(),
            'active'      => $this->active,
            'status'      => $this->status,
            'erp_status'  => $latestTask?->status,
            'erp_error'   => $latestTask?->last_error ?: $this->erp_sync_error,
            'created_at'  => optional($this->created_at)->toIso8601String(),
            'origin'      => [
                'type' => $originType,
                'label' => $originType === 'partner'
                    ? 'Cupom Parceiro'
                    : ($originType === 'loyalty' ? 'Recompensa Fidelidade' : 'Cupom'),
                'partner' => $partner ? [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'slug' => $partner->slug,
                ] : null,
                'partner_campaign' => $this->partnerCampaign ? [
                    'id' => $this->partnerCampaign->id,
                    'public_name' => $this->partnerCampaign->public_name,
                ] : null,
            ],
            'erp_task' => $latestTask ? [
                'id' => $latestTask->id,
                'operation' => $latestTask->operation,
                'status' => $latestTask->status,
                'last_error' => $latestTask->last_error,
            ] : null,
            'user'        => [
                'id'    => $this->user->id ?? null,
                'name'  => $this->user->name ?? null,
                'email' => $this->user->email ?? null,
            ],
            'coupon'      => new CouponResource($this->whenLoaded('coupon')),
        ];
    }
}
