<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\UrbanCampaignCouponClaim;
use App\Models\UrbanCampaignCouponConfig;
use App\Repositories\UrbanCampaignRepository;
use App\Services\Erp\Vendus\VendusCouponSyncService;
use Illuminate\Validation\ValidationException;
use Throwable;

class UrbanCampaignService
{
    public function __construct(
        protected UrbanCampaignRepository $repository,
        protected VendusCouponSyncService $vendus,
    ) {}

    public function getChallengePayload(string $code): array
    {
        $qrCode = $this->repository->findActiveQrCodeByCode($code);

        if (! $qrCode) {
            throw ValidationException::withMessages([
                'code' => 'QR Code inválido ou indisponível.',
            ]);
        }

        $question = $this->repository->activeChallengeForCodeType($qrCode->type);

        if (! $question) {
            throw ValidationException::withMessages([
                'code' => 'Esta pista ainda não tem pergunta configurada.',
            ]);
        }

        return [
            'qr_code' => $qrCode,
            'question' => $question,
        ];
    }

    public function answerChallenge(string $code, int $questionId, int $responseId): array
    {
        $payload = $this->resolveAnsweredChallenge($code, $questionId, $responseId);
        $reward = $this->resolveReward($payload['question'], $payload['response'], $payload['coupon_config']);

        return array_merge($payload, $reward);
    }

    public function claimCoupon(string $code, int $questionId, int $responseId, string $phone): UrbanCampaignCouponClaim
    {
        $payload = $this->resolveAnsweredChallenge($code, $questionId, $responseId);
        $qrCode = $payload['qr_code'];
        $question = $payload['question'];
        $response = $payload['response'];
        $config = $payload['coupon_config'];
        $couponType = mb_strtolower(trim($qrCode->type));
        $claim = $this->repository->findClaimByPhoneAndType($phone, $couponType);

        if ($claim?->hasSyncedCode()) {
            return $claim;
        }

        if (! $config) {
            throw ValidationException::withMessages([
                'phone' => 'Este cupom ainda não está configurado para resgate.',
            ]);
        }

        if (! $claim) {
            $claim = $this->repository->createClaim([
                'phone' => $phone,
                'coupon_type' => $couponType,
                'coupon_config_id' => $config->id,
                'qr_code_id' => $qrCode->id,
                'question_id' => $question->id,
                'response_id' => $response->id,
                'is_correct' => $response->is_correct,
                'discount_type' => $config->discount_type,
                'amount' => $config->amount,
                'status' => UrbanCampaignCouponClaim::STATUS_PENDING_ERP,
            ]);
        } else {
            $claim = $this->repository->refreshClaimConfiguration($claim, [
                'coupon_config_id' => $config->id,
                'qr_code_id' => $qrCode->id,
                'question_id' => $question->id,
                'response_id' => $response->id,
                'is_correct' => $response->is_correct,
                'discount_type' => $config->discount_type,
                'amount' => $config->amount,
            ]);
        }

        $claim = $this->repository->markClaimSyncing($claim);

        try {
            $erpResponse = $this->vendus->createFromPayload($this->toVendusPayload($claim, $config), [
                'entity_type' => 'urban_campaign_coupon_claim',
                'entity_id' => $claim->id,
                'coupon_type' => $claim->coupon_type,
            ]);

            if (! $erpResponse || empty($erpResponse['external_code'])) {
                throw ValidationException::withMessages([
                    'phone' => 'Não foi possível gerar o cupom no Vendus. Tente novamente.',
                ]);
            }

            return $this->repository->markClaimSynced($claim, $erpResponse);
        } catch (ValidationException $exception) {
            $this->repository->markClaimFailed($claim, $exception->getMessage());

            throw $exception;
        } catch (Throwable $exception) {
            $this->repository->markClaimFailed($claim, $exception->getMessage());

            throw ValidationException::withMessages([
                'phone' => 'Não foi possível gerar o cupom no Vendus. Tente novamente.',
            ]);
        }
    }

    protected function resolveAnsweredChallenge(string $code, int $questionId, int $responseId): array
    {
        $qrCode = $this->repository->findActiveQrCodeByCode($code);

        if (! $qrCode) {
            throw ValidationException::withMessages([
                'code' => 'QR Code inválido ou indisponível.',
            ]);
        }

        $question = $this->repository->findActiveQuestionForCodeType($questionId, $qrCode->type);

        if (! $question) {
            throw ValidationException::withMessages([
                'question_id' => 'A pergunta não pertence a este QR Code.',
            ]);
        }

        $response = $this->repository->findResponseForQuestion($responseId, $question->id);

        if (! $response) {
            throw ValidationException::withMessages([
                'response_id' => 'A resposta não pertence a esta pergunta.',
            ]);
        }

        return [
            'qr_code' => $qrCode,
            'question' => $question,
            'response' => $response,
            'coupon_config' => $this->repository->activeCouponConfigForType($qrCode->type),
            'is_correct' => $response->is_correct,
        ];
    }

    protected function resolveReward(
        Question $question,
        QuestionResponse $response,
        ?UrbanCampaignCouponConfig $config
    ): array {
        $fallbackPercent = $response->is_correct
            ? $question->reward_when_correct
            : $question->reward_when_wrong;

        return [
            'reward_percent' => $fallbackPercent,
            'reward_type' => $config?->discount_type ?? UrbanCampaignCouponConfig::TYPE_PERCENT,
            'reward_amount' => (float) ($config?->amount ?? $fallbackPercent),
        ];
    }

    protected function toVendusPayload(UrbanCampaignCouponClaim $claim, UrbanCampaignCouponConfig $config): array
    {
        return [
            'amount' => number_format((float) $config->amount, 2, '.', ''),
            'type' => $config->discount_type,
            'date_expire' => optional($config->ends_at)->toDateString(),
            'obs' => $config->description ?: sprintf(
                'Cupom Campanha Urbana %s para %s',
                $claim->coupon_type,
                $claim->phone
            ),
        ];
    }
}
