<?php

namespace App\Repositories;

use App\Models\QrCode;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\UrbanCampaignCouponClaim;
use App\Models\UrbanCampaignCouponConfig;
use Illuminate\Database\Eloquent\Collection;

class UrbanCampaignAdminRepository
{
    public function qrCodes(): Collection
    {
        return QrCode::query()
            ->orderBy('type')
            ->orderBy('code')
            ->get();
    }

    public function questions(): Collection
    {
        return Question::query()
            ->withCount('responses')
            ->orderBy('display_order')
            ->orderBy('secret_number')
            ->orderBy('id')
            ->get();
    }

    public function responses(): Collection
    {
        return QuestionResponse::query()
            ->with('question')
            ->orderBy('question_id')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public function couponConfigs(): Collection
    {
        return UrbanCampaignCouponConfig::query()
            ->orderBy('coupon_type')
            ->get();
    }

    public function couponClaims(): Collection
    {
        return UrbanCampaignCouponClaim::query()
            ->with(['config', 'qrCode', 'question'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function questionOptions(): Collection
    {
        return Question::query()
            ->orderBy('display_order')
            ->orderBy('secret_number')
            ->orderBy('id')
            ->get(['id', 'code_type', 'secret_number', 'collection_label']);
    }

    public function codeTypeOptions(): Collection
    {
        return QrCode::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->get();
    }

    public function createQrCode(array $data): QrCode
    {
        return QrCode::query()->create($data);
    }

    public function updateQrCode(QrCode $qrCode, array $data): QrCode
    {
        $qrCode->update($data);

        return $qrCode->fresh();
    }

    public function deleteQrCode(QrCode $qrCode): void
    {
        $qrCode->delete();
    }

    public function createQuestion(array $data): Question
    {
        return Question::query()->create($data);
    }

    public function updateQuestion(Question $question, array $data): Question
    {
        $question->update($data);

        return $question->fresh();
    }

    public function deleteQuestion(Question $question): void
    {
        $question->delete();
    }

    public function createResponse(array $data): QuestionResponse
    {
        return QuestionResponse::query()->create($data);
    }

    public function updateResponse(QuestionResponse $response, array $data): QuestionResponse
    {
        $response->update($data);

        return $response->fresh();
    }

    public function deleteResponse(QuestionResponse $response): void
    {
        $response->delete();
    }

    public function createCouponConfig(array $data): UrbanCampaignCouponConfig
    {
        return UrbanCampaignCouponConfig::query()->create($data);
    }

    public function updateCouponConfig(UrbanCampaignCouponConfig $config, array $data): UrbanCampaignCouponConfig
    {
        $config->update($data);

        return $config->fresh();
    }

    public function deleteCouponConfig(UrbanCampaignCouponConfig $config): void
    {
        $config->delete();
    }

    public function clearCorrectResponsesForQuestion(int $questionId, ?int $exceptResponseId = null): void
    {
        QuestionResponse::query()
            ->where('question_id', $questionId)
            ->when($exceptResponseId !== null, fn ($query) => $query->whereKeyNot($exceptResponseId))
            ->update(['is_correct' => false]);
    }
}
