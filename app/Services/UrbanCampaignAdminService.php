<?php

namespace App\Services;

use App\Models\QrCode;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Repositories\UrbanCampaignAdminRepository;
use Illuminate\Support\Facades\DB;

class UrbanCampaignAdminService
{
    public function __construct(
        protected UrbanCampaignAdminRepository $repository,
    ) {}

    public function dashboardData(): array
    {
        return [
            'qrCodes' => $this->repository->qrCodes(),
            'questions' => $this->repository->questions(),
            'responses' => $this->repository->responses(),
            'questionOptions' => $this->repository->questionOptions(),
            'codeTypeOptions' => $this->repository->codeTypeOptions(),
        ];
    }

    public function createQrCode(array $data): QrCode
    {
        return $this->repository->createQrCode($this->normalizeQrCode($data));
    }

    public function updateQrCode(QrCode $qrCode, array $data): QrCode
    {
        return $this->repository->updateQrCode($qrCode, $this->normalizeQrCode($data));
    }

    public function deleteQrCode(QrCode $qrCode): void
    {
        $this->repository->deleteQrCode($qrCode);
    }

    public function createQuestion(array $data): Question
    {
        return $this->repository->createQuestion($this->normalizeQuestion($data));
    }

    public function updateQuestion(Question $question, array $data): Question
    {
        return $this->repository->updateQuestion($question, $this->normalizeQuestion($data));
    }

    public function deleteQuestion(Question $question): void
    {
        $this->repository->deleteQuestion($question);
    }

    public function createResponse(array $data): QuestionResponse
    {
        return DB::transaction(function () use ($data): QuestionResponse {
            $payload = $this->normalizeResponse($data);

            if ($payload['is_correct']) {
                $this->repository->clearCorrectResponsesForQuestion($payload['question_id']);
            }

            return $this->repository->createResponse($payload);
        });
    }

    public function updateResponse(QuestionResponse $response, array $data): QuestionResponse
    {
        return DB::transaction(function () use ($response, $data): QuestionResponse {
            $payload = $this->normalizeResponse($data);

            if ($payload['is_correct']) {
                $this->repository->clearCorrectResponsesForQuestion($payload['question_id'], $response->id);
            }

            return $this->repository->updateResponse($response, $payload);
        });
    }

    public function deleteResponse(QuestionResponse $response): void
    {
        $this->repository->deleteResponse($response);
    }

    protected function normalizeQrCode(array $data): array
    {
        $data['code'] = mb_strtoupper(trim((string) $data['code']));
        $data['type'] = mb_strtolower(trim((string) $data['type']));
        $data['active'] = (bool) ($data['active'] ?? false);

        return $data;
    }

    protected function normalizeQuestion(array $data): array
    {
        $data['code_type'] = mb_strtolower(trim((string) $data['code_type']));
        $data['secret_number'] = (int) $data['secret_number'];
        $data['reward_when_correct'] = (int) $data['reward_when_correct'];
        $data['reward_when_wrong'] = (int) $data['reward_when_wrong'];
        $data['display_order'] = isset($data['display_order']) ? (int) $data['display_order'] : 0;
        $data['active'] = (bool) ($data['active'] ?? false);

        return $data;
    }

    protected function normalizeResponse(array $data): array
    {
        $data['question_id'] = (int) $data['question_id'];
        $data['display_order'] = isset($data['display_order']) ? (int) $data['display_order'] : 0;
        $data['is_correct'] = (bool) ($data['is_correct'] ?? false);

        return $data;
    }
}
