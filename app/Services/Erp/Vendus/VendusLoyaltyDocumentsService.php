<?php

namespace App\Services\Erp\Vendus;

use App\Models\User;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VendusLoyaltyDocumentsService
{
    public function __construct(
        protected VendusHttpClient $client,
        protected SettingService $settings
    ) {}

    /**
     * Sincroniza os pontos de fidelidade a partir das faturas do Vendus (/documents)
     */
    public function sync(): array
    {
        Log::info('[VendusDocumentsSync] Iniciando sincronização via /documents');

        // ⚙️ Chamada sem filtros (busca notas do dia)
        $response = $this->client->client()->get('/documents?type=FT');

        if ($response->failed()) {
            Log::error('[VendusDocumentsSync] Falha na requisição', ['body' => $response->body()]);
            return [
                'status' => 'error',
                'message' => 'Falha na comunicação com o Vendus',
            ];
        }

        // A API às vezes devolve dentro de "documents", outras vezes direto no array
        $documents = $response->json('documents') ?? $response->json() ?? [];

        Log::info('[VendusDocumentsSync] Documentos retornados', ['Documentos: ' => $documents]);

        if (empty($documents)) {
            Log::info('[VendusDocumentsSync] Nenhum documento retornado.');
            return [
                'status' => 'success',
                'message' => 'Nenhum documento novo encontrado.',
                'processed' => [],
            ];
        }

        $pointsPerEuro = $this->settings->get('LOYALTY_POINTS_PER_EURO', 10);
        $processed = [];

        foreach ($documents as $doc) {
            try {
                // 🧾 Validações básicas
                if (empty($doc['client_id']) || empty($doc['total'])) continue;

                $user = User::where('vendus_client_id', $doc['client_id'])->first();
                if (!$user) continue;

                // Evita duplicações
                $alreadyProcessed = DB::table('loyalty_logs')
                    ->where('external_id', $doc['id'])
                    ->exists();

                if ($alreadyProcessed) continue;

                $points = floor($doc['total'] * $pointsPerEuro);

                // 🔸 Registra log da transação
                DB::table('loyalty_logs')->insert([
                    'user_id'     => $user->id,
                    'external_id' => $doc['id'],
                    'source'      => 'vendus',
                    'points'      => $points,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // 🔸 Atualiza o saldo do usuário
                $user->increment('loyalty_points', $points);

                $processed[] = [
                    'invoice_id' => $doc['id'],
                    'client'     => $user->name,
                    'points'     => $points,
                ];

                Log::info("[VendusDocumentsSync] +{$points} pontos para {$user->name} ({$doc['id']})");
            } catch (\Throwable $e) {
                Log::error('[VendusDocumentsSync] Erro ao processar documento', [
                    'doc_id' => $doc['id'] ?? 'unknown',
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        $count = count($processed);
        Log::info("[VendusDocumentsSync] Finalizado ({$count} faturas processadas)");

        return [
            'status' => 'success',
            'processed' => $processed,
        ];
    }
}
