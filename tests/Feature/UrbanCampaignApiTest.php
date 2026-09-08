<?php

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\UrbanCampaignCouponClaim;
use App\Models\UrbanCampaignCouponConfig;
use Carbon\Carbon;
use Database\Seeders\UrbanCampaignSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UrbanCampaignApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_load_challenge_for_active_qr_code_without_correct_answer(): void
    {
        $challenge = $this->createChallenge();

        $response = $this->getJson('/api/v1/public/urban-campaign/qr-codes/urbana-001');

        $response->assertOk()
            ->assertJsonPath('data.type', 'quiz-praca')
            ->assertJsonPath('data.code', 'URBANA-001')
            ->assertJsonPath('data.question.id', $challenge['question']->id)
            ->assertJsonPath('data.question.secret_number', 1)
            ->assertJsonPath('data.question.collection_label', 'Kibe')
            ->assertJsonPath('data.question.eyebrow', 'Uma pista de forma comprida')
            ->assertJsonPath('data.question.riddle.0', 'Por fora sou dourado.')
            ->assertJsonPath('data.question.responses.0.response', 'Resposta certa')
            ->assertJsonPath('data.question.responses.1.response', 'Resposta errada')
            ->assertJsonMissing(['is_correct' => true])
            ->assertJsonMissing(['is_correct' => false]);
    }

    public function test_answer_endpoint_returns_correct_reward_after_submission(): void
    {
        $challenge = $this->createChallenge();

        $response = $this->postJson('/api/v1/public/urban-campaign/answers', [
            'code' => 'urbana-001',
            'question_id' => $challenge['question']->id,
            'response_id' => $challenge['correct']->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.type', 'quiz-praca')
            ->assertJsonPath('data.question_id', $challenge['question']->id)
            ->assertJsonPath('data.response_id', $challenge['correct']->id)
            ->assertJsonPath('data.is_correct', true)
            ->assertJsonPath('data.reward_percent', 10)
            ->assertJsonPath('data.collection_label', 'Kibe');
    }

    public function test_answer_endpoint_returns_wrong_reward_for_incorrect_submission(): void
    {
        $challenge = $this->createChallenge();

        $response = $this->postJson('/api/v1/public/urban-campaign/answers', [
            'code' => 'URBANA-001',
            'question_id' => $challenge['question']->id,
            'response_id' => $challenge['wrong']->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_correct', false)
            ->assertJsonPath('data.reward_percent', 5);
    }

    public function test_inactive_or_unknown_qr_code_returns_validation_error(): void
    {
        QrCode::create([
            'type' => 'quiz-praca',
            'code' => 'URBANA-001',
            'active' => false,
        ]);

        $response = $this->getJson('/api/v1/public/urban-campaign/qr-codes/URBANA-001');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_answer_must_belong_to_qr_code_question(): void
    {
        $challenge = $this->createChallenge();
        $otherQuestion = Question::create([
            'code_type' => 'outro-tipo',
            'question' => 'Outra pergunta',
            'secret_number' => 2,
            'collection_label' => 'Outro',
            'reward_when_correct' => 15,
            'reward_when_wrong' => 10,
            'active' => true,
            'display_order' => 1,
        ]);

        $otherResponse = QuestionResponse::create([
            'question_id' => $otherQuestion->id,
            'response' => 'Outra resposta',
            'is_correct' => true,
            'display_order' => 1,
        ]);

        $response = $this->postJson('/api/v1/public/urban-campaign/answers', [
            'code' => 'URBANA-001',
            'question_id' => $challenge['question']->id,
            'response_id' => $otherResponse->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['response_id']);
    }

    public function test_seeded_campaign_data_can_load_current_qr_codes(): void
    {
        $this->seed(UrbanCampaignSeeder::class);

        $response = $this->getJson('/api/v1/public/urban-campaign/qr-codes/qrxpto');

        $response->assertOk()
            ->assertJsonPath('data.type', 'kibe')
            ->assertJsonPath('data.question.secret_number', 1)
            ->assertJsonPath('data.question.collection_label', 'Kibe')
            ->assertJsonPath('data.question.responses.0.response', 'Kibe')
            ->assertJsonMissing(['is_correct' => true]);
    }

    public function test_public_user_can_claim_vendus_coupon_once_per_phone_and_type(): void
    {
        $this->travelTo(Carbon::parse('2026-09-08 12:00:00'));

        config([
            'services.vendus.base_url' => 'https://vendus.test/ws/v1.1',
            'services.vendus.token' => 'test-token',
        ]);

        $challenge = $this->createChallenge();
        UrbanCampaignCouponConfig::create([
            'coupon_type' => 'quiz-praca',
            'title' => 'Campanha Urbana - Kibe',
            'description' => 'Cupom gerado pela campanha urbana.',
            'duration_days' => 7,
            'discount_type' => 'percent',
            'amount' => 10,
            'active' => true,
        ]);

        Http::fake([
            'vendus.test/ws/v1.1/discountcards/*' => Http::response([
                'id' => 123,
                'code' => 'VD-URBANA-10',
                'status' => 'available',
                'type' => 'percent',
                'amount' => 10,
            ], 200),
        ]);

        $first = $this->postJson('/api/v1/public/urban-campaign/claims', [
            'code' => 'urbana-001',
            'question_id' => $challenge['question']->id,
            'response_id' => $challenge['correct']->id,
            'phone' => '912 345 678',
        ]);

        $second = $this->postJson('/api/v1/public/urban-campaign/claims', [
            'code' => 'URBANA-001',
            'question_id' => $challenge['question']->id,
            'response_id' => $challenge['wrong']->id,
            'phone' => '+351 912 345 678',
        ]);

        $first->assertOk()
            ->assertJsonPath('data.phone', '351912345678')
            ->assertJsonPath('data.coupon_type', 'quiz-praca')
            ->assertJsonPath('data.code', 'VD-URBANA-10')
            ->assertJsonPath('data.status', 'synced')
            ->assertJsonPath('data.discount_type', 'percent')
            ->assertJsonPath('data.amount', 10);

        $second->assertOk()
            ->assertJsonPath('data.code', 'VD-URBANA-10')
            ->assertJsonPath('data.status', 'synced');

        $this->assertDatabaseCount('urban_campaign_coupon_claims', 1);
        $this->assertDatabaseHas('urban_campaign_coupon_claims', [
            'phone' => '351912345678',
            'coupon_type' => 'quiz-praca',
            'code' => 'VD-URBANA-10',
            'status' => 'synced',
        ]);

        $claim = UrbanCampaignCouponClaim::query()->firstOrFail();
        $this->assertSame('2026-09-08 12:00:00', $claim->generated_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-15 12:00:00', $claim->expires_at->format('Y-m-d H:i:s'));

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['date_expire'] === '2026-09-15');
        $this->travelBack();
    }

    public function test_claim_requires_active_coupon_config(): void
    {
        $challenge = $this->createChallenge();

        $response = $this->postJson('/api/v1/public/urban-campaign/claims', [
            'code' => 'URBANA-001',
            'question_id' => $challenge['question']->id,
            'response_id' => $challenge['correct']->id,
            'phone' => '912345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    protected function createChallenge(): array
    {
        QrCode::create([
            'type' => 'quiz-praca',
            'code' => 'URBANA-001',
            'active' => true,
        ]);

        $question = Question::create([
            'code_type' => 'quiz-praca',
            'question' => "Por fora sou dourado.\nQuem sou eu?",
            'secret_number' => 1,
            'collection_label' => 'Kibe',
            'eyebrow' => 'Uma pista de forma comprida',
            'reward_when_correct' => 10,
            'reward_when_wrong' => 5,
            'active' => true,
            'display_order' => 1,
        ]);

        $wrong = QuestionResponse::create([
            'question_id' => $question->id,
            'response' => 'Resposta errada',
            'is_correct' => false,
            'display_order' => 2,
        ]);

        $correct = QuestionResponse::create([
            'question_id' => $question->id,
            'response' => 'Resposta certa',
            'is_correct' => true,
            'display_order' => 1,
        ]);

        return [
            'question' => $question,
            'correct' => $correct,
            'wrong' => $wrong,
        ];
    }
}
