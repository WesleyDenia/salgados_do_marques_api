<?php

namespace App\Services;

use App\Repositories\UrbanCampaignRepository;
use Illuminate\Validation\ValidationException;

class UrbanCampaignService
{
    public function __construct(
        protected UrbanCampaignRepository $repository,
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
            'is_correct' => $response->is_correct,
            'reward_percent' => $response->is_correct
                ? $question->reward_when_correct
                : $question->reward_when_wrong,
        ];
    }
}
