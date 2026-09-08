<?php

namespace App\Repositories;

use App\Models\QrCode;
use App\Models\Question;
use App\Models\QuestionResponse;

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
}
