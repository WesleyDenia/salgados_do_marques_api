<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UrbanCampaignCouponConfigRequest;
use App\Http\Requests\Admin\UrbanCampaignQrCodeRequest;
use App\Http\Requests\Admin\UrbanCampaignQuestionRequest;
use App\Http\Requests\Admin\UrbanCampaignResponseRequest;
use App\Models\QrCode;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\UrbanCampaignCouponConfig;
use App\Services\UrbanCampaignAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UrbanCampaignController extends Controller
{
    public function __construct(
        protected UrbanCampaignAdminService $service,
    ) {}

    public function index(): View
    {
        return $this->view();
    }

    public function editQrCode(QrCode $qrCode): View
    {
        return $this->view(['editQrCode' => $qrCode, 'activeTab' => 'qr-codes']);
    }

    public function storeQrCode(UrbanCampaignQrCodeRequest $request): RedirectResponse
    {
        $this->service->createQrCode($request->validated());

        return $this->redirectToTab('qr-codes', 'QR Code criado com sucesso.');
    }

    public function updateQrCode(UrbanCampaignQrCodeRequest $request, QrCode $qrCode): RedirectResponse
    {
        $this->service->updateQrCode($qrCode, $request->validated());

        return $this->redirectToTab('qr-codes', 'QR Code atualizado com sucesso.');
    }

    public function destroyQrCode(QrCode $qrCode): RedirectResponse
    {
        $this->service->deleteQrCode($qrCode);

        return $this->redirectToTab('qr-codes', 'QR Code removido com sucesso.');
    }

    public function editQuestion(Question $question): View
    {
        return $this->view(['editQuestion' => $question, 'activeTab' => 'questions']);
    }

    public function storeQuestion(UrbanCampaignQuestionRequest $request): RedirectResponse
    {
        $this->service->createQuestion($request->validated());

        return $this->redirectToTab('questions', 'Pergunta criada com sucesso.');
    }

    public function updateQuestion(UrbanCampaignQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->service->updateQuestion($question, $request->validated());

        return $this->redirectToTab('questions', 'Pergunta atualizada com sucesso.');
    }

    public function destroyQuestion(Question $question): RedirectResponse
    {
        $this->service->deleteQuestion($question);

        return $this->redirectToTab('questions', 'Pergunta removida com sucesso.');
    }

    public function editResponse(QuestionResponse $response): View
    {
        return $this->view(['editResponse' => $response, 'activeTab' => 'responses']);
    }

    public function storeResponse(UrbanCampaignResponseRequest $request): RedirectResponse
    {
        $this->service->createResponse($request->validated());

        return $this->redirectToTab('responses', 'Resposta criada com sucesso.');
    }

    public function updateResponse(UrbanCampaignResponseRequest $request, QuestionResponse $response): RedirectResponse
    {
        $this->service->updateResponse($response, $request->validated());

        return $this->redirectToTab('responses', 'Resposta atualizada com sucesso.');
    }

    public function destroyResponse(QuestionResponse $response): RedirectResponse
    {
        $this->service->deleteResponse($response);

        return $this->redirectToTab('responses', 'Resposta removida com sucesso.');
    }

    public function editCouponConfig(UrbanCampaignCouponConfig $couponConfig): View
    {
        return $this->view(['editCouponConfig' => $couponConfig, 'activeTab' => 'coupon-configs']);
    }

    public function storeCouponConfig(UrbanCampaignCouponConfigRequest $request): RedirectResponse
    {
        $this->service->createCouponConfig($request->validated());

        return $this->redirectToTab('coupon-configs', 'Configuração de cupom criada com sucesso.');
    }

    public function updateCouponConfig(
        UrbanCampaignCouponConfigRequest $request,
        UrbanCampaignCouponConfig $couponConfig
    ): RedirectResponse {
        $this->service->updateCouponConfig($couponConfig, $request->validated());

        return $this->redirectToTab('coupon-configs', 'Configuração de cupom atualizada com sucesso.');
    }

    public function destroyCouponConfig(UrbanCampaignCouponConfig $couponConfig): RedirectResponse
    {
        $this->service->deleteCouponConfig($couponConfig);

        return $this->redirectToTab('coupon-configs', 'Configuração de cupom removida com sucesso.');
    }

    protected function view(array $data = []): View
    {
        return view('admin.urban-campaign.index', array_merge(
            $this->service->dashboardData(),
            [
                'editQrCode' => new QrCode(['active' => true]),
                'editQuestion' => new Question([
                    'active' => true,
                    'reward_when_correct' => 10,
                    'reward_when_wrong' => 5,
                    'display_order' => 0,
                ]),
                'editResponse' => new QuestionResponse([
                    'display_order' => 0,
                ]),
                'editCouponConfig' => new UrbanCampaignCouponConfig([
                    'active' => true,
                    'discount_type' => UrbanCampaignCouponConfig::TYPE_PERCENT,
                    'amount' => 10,
                ]),
                'activeTab' => request('tab', 'qr-codes'),
            ],
            $data
        ));
    }

    protected function redirectToTab(string $tab, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.urban-campaign.index', ['tab' => $tab])
            ->with('status', $message);
    }
}
