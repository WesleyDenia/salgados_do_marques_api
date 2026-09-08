<?php

namespace App\Repositories;

use App\Models\QrCode;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\UrbanCampaignCouponClaim;
use App\Models\UrbanCampaignCouponConfig;

class UrbanCampaignRepository
{
    public function findActiveQrCodeByCode(string $code): ?QrCode
    {
        return QrCode::query()
            ->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])
            ->where('active', true)
            ->first();
    }

    public function activeChallengeForCodeType(string $codeType): ?Question
    {
        return Question::query()
            ->with(['responses' => fn ($query) => $query
                ->orderBy('display_order')
                ->orderBy('id')])
            ->whereRaw('LOWER(code_type) = ?', [mb_strtolower(trim($codeType))])
            ->where('active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->first();
    }

    public function findActiveQuestionForCodeType(int $questionId, string $codeType): ?Question
    {
        return Question::query()
            ->whereKey($questionId)
            ->whereRaw('LOWER(code_type) = ?', [mb_strtolower(trim($codeType))])
            ->where('active', true)
            ->first();
    }

    public function findResponseForQuestion(int $responseId, int $questionId): ?QuestionResponse
    {
        return QuestionResponse::query()
            ->whereKey($responseId)
            ->where('question_id', $questionId)
            ->first();
    }

    public function activeCouponConfigForType(string $couponType): ?UrbanCampaignCouponConfig
    {
        return UrbanCampaignCouponConfig::query()
            ->whereRaw('LOWER(coupon_type) = ?', [mb_strtolower(trim($couponType))])
            ->where('active', true)
            ->first();
    }

    public function findClaimByPhoneAndType(string $phone, string $couponType): ?UrbanCampaignCouponClaim
    {
        return UrbanCampaignCouponClaim::query()
            ->with(['config', 'qrCode', 'question', 'response'])
            ->where('phone', $phone)
            ->where('coupon_type', mb_strtolower(trim($couponType)))
            ->first();
    }

    public function createClaim(array $data): UrbanCampaignCouponClaim
    {
        return UrbanCampaignCouponClaim::query()
            ->create($data)
            ->load(['config', 'qrCode', 'question', 'response']);
    }

    public function markClaimSyncing(UrbanCampaignCouponClaim $claim): UrbanCampaignCouponClaim
    {
        $claim->forceFill([
            'status' => UrbanCampaignCouponClaim::STATUS_SYNCING_ERP,
            'erp_sync_error' => null,
            'erp_sync_attempts' => $claim->erp_sync_attempts + 1,
        ])->save();

        return $claim->refresh()->load(['config', 'qrCode', 'question', 'response']);
    }

    public function refreshClaimConfiguration(UrbanCampaignCouponClaim $claim, array $data): UrbanCampaignCouponClaim
    {
        $claim->forceFill(array_merge($data, [
            'status' => UrbanCampaignCouponClaim::STATUS_PENDING_ERP,
            'external_id' => null,
            'code' => null,
            'erp_sync_error' => null,
            'erp_synced_at' => null,
        ]))->save();

        return $claim->refresh()->load(['config', 'qrCode', 'question', 'response']);
    }

    public function markClaimSynced(UrbanCampaignCouponClaim $claim, array $response): UrbanCampaignCouponClaim
    {
        $claim->forceFill([
            'external_id' => $response['external_id'] ?? $claim->external_id,
            'code' => $response['external_code'] ?? $claim->code,
            'status' => UrbanCampaignCouponClaim::STATUS_SYNCED,
            'erp_sync_error' => null,
            'erp_synced_at' => now(),
        ])->save();

        return $claim->refresh()->load(['config', 'qrCode', 'question', 'response']);
    }

    public function markClaimFailed(UrbanCampaignCouponClaim $claim, string $message): UrbanCampaignCouponClaim
    {
        $claim->forceFill([
            'status' => UrbanCampaignCouponClaim::STATUS_FAILED_ERP,
            'erp_sync_error' => mb_strimwidth($message, 0, 1000, '...'),
        ])->save();

        return $claim->refresh()->load(['config', 'qrCode', 'question', 'response']);
    }
}
