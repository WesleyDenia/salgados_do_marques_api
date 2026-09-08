<?php

namespace Tests\Feature\Admin;

use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UrbanCampaignAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_campaign_page_has_one_sidebar_entry_and_internal_tabs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.urban-campaign.index'));

        $response->assertOk()
            ->assertSee('Campanha Urbana')
            ->assertSee('QR Codes')
            ->assertSee('Perguntas')
            ->assertSee('Respostas');

        $this->assertSame(1, substr_count($response->getContent(), '>Campanha Urbana</a>'));
    }

    public function test_admin_can_create_qr_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.urban-campaign.qr-codes.store'), [
            'code' => ' qrxpto ',
            'type' => ' Kibe ',
            'active' => '1',
        ]);

        $response->assertRedirect(route('admin.urban-campaign.index', ['tab' => 'qr-codes']));
        $this->assertDatabaseHas('qr_codes', [
            'code' => 'QRXPTO',
            'type' => 'kibe',
            'active' => true,
        ]);
    }

    public function test_admin_can_create_question(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.urban-campaign.questions.store'), [
            'code_type' => ' Kibe ',
            'question' => "Por fora sou dourado.\nQuem sou eu?",
            'secret_number' => 1,
            'collection_label' => 'Kibe',
            'eyebrow' => 'Uma pista de forma comprida',
            'reward_when_correct' => 10,
            'reward_when_wrong' => 5,
            'display_order' => 2,
            'active' => '1',
        ]);

        $response->assertRedirect(route('admin.urban-campaign.index', ['tab' => 'questions']));
        $this->assertDatabaseHas('questions', [
            'code_type' => 'kibe',
            'secret_number' => 1,
            'collection_label' => 'Kibe',
            'reward_when_correct' => 10,
            'reward_when_wrong' => 5,
            'display_order' => 2,
            'active' => true,
        ]);
    }

    public function test_admin_marks_only_one_response_as_correct_for_question(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $question = Question::create([
            'code_type' => 'kibe',
            'question' => 'Quem sou eu?',
            'secret_number' => 1,
            'collection_label' => 'Kibe',
            'reward_when_correct' => 10,
            'reward_when_wrong' => 5,
            'active' => true,
            'display_order' => 1,
        ]);
        $first = QuestionResponse::create([
            'question_id' => $question->id,
            'response' => 'Kibe',
            'is_correct' => true,
            'display_order' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.urban-campaign.responses.store'), [
            'question_id' => $question->id,
            'response' => 'Coxinha',
            'is_correct' => '1',
            'display_order' => 2,
        ]);

        $response->assertRedirect(route('admin.urban-campaign.index', ['tab' => 'responses']));
        $this->assertFalse($first->fresh()->is_correct);

        $newCorrect = QuestionResponse::query()->where('response', 'Coxinha')->firstOrFail();
        $this->assertTrue($newCorrect->is_correct);
    }

    public function test_admin_can_update_response_and_switch_correct_answer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $question = Question::create([
            'code_type' => 'kibe',
            'question' => 'Quem sou eu?',
            'secret_number' => 1,
            'collection_label' => 'Kibe',
            'reward_when_correct' => 10,
            'reward_when_wrong' => 5,
            'active' => true,
            'display_order' => 1,
        ]);
        $first = QuestionResponse::create([
            'question_id' => $question->id,
            'response' => 'Kibe',
            'is_correct' => true,
            'display_order' => 1,
        ]);
        $second = QuestionResponse::create([
            'question_id' => $question->id,
            'response' => 'Coxinha',
            'is_correct' => false,
            'display_order' => 2,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.urban-campaign.responses.update', $second), [
            'question_id' => $question->id,
            'response' => 'Coxinha',
            'is_correct' => '1',
            'display_order' => 2,
        ]);

        $response->assertRedirect(route('admin.urban-campaign.index', ['tab' => 'responses']));
        $this->assertFalse($first->fresh()->is_correct);
        $this->assertTrue($second->fresh()->is_correct);
    }
}
