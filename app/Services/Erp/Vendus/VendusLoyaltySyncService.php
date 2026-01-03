<?php

namespace App\Services\Erp\Vendus;

use App\Models\User;
use App\Models\LoyaltyAccount;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VendusLoyaltySyncService
{
    public function __construct(
        protected VendusHttpClient $client,
        protected SettingService $settings
    ) {}

    /**
     * Sincroniza as faturas do Vendus e atualiza pontos de fidelidade.
     */
    public function sync(string $external_user_id, int $since = 10): array  
    {
        $pointsPerEuro = $this->settings->get('LOYALTY_POINTS_PER_EURO', 10);

        $params = [             
            'since' => now()->subDays($since)->toDateString(), 
            'until' => now()->toDateString(), 
            'status' => 'all', 
            'aggregate' => 'no', 
        ];        

        $response = $this->client->client()->get(
            '/clients/'.$external_user_id.'/balance',
            http_build_query($params)
        );

        // Tratamento de "sem dados" ou 404 como sucesso silencioso para evitar ruído
        if ($response->status() === 404) {
            return ['status' => 'success', 'processed' => [], 'message' => 'Sem dados'];
        }

        // Alguns retornos 200/400 trazem código A001 "No data"
        $rawBody = $response->body();
        $jsonBody = json_decode($rawBody, true);
        $hasNoDataCode = is_array($jsonBody)
            && isset($jsonBody['errors'])
            && collect($jsonBody['errors'])->pluck('code')->contains('A001');
        if ($hasNoDataCode) {
            return ['status' => 'success', 'processed' => [], 'message' => 'Sem dados'];
        }

        if ($response->failed()) {
            Log::warning('Erro ao sincronizar documentos do Vendus', [
                'status' => $response->status(),
                'body' => $rawBody,
            ]);
            return ['status' => 'error', 'message' => 'Falha na comunicação com o Vendus'];
        }

        $documents = $response->json() ?? [];
        $processed = [];
        
        foreach ($documents as $doc) {
            if ($doc['type'] != 'FT') continue;

            // Evita processar documentos sem valor ou cujo valor seja menos que 1 euro
            if (empty($doc['amount_gross']) || $doc['amount_gross'] < 1) continue;

            // Buscar usuário local vinculado ao client_id do Vendus
            $user = User::where('external_id', $external_user_id)->first();

            if (!$user) continue;

            // Evita duplicação: checar se já foi processado
            $alreadyProcessed = DB::table('loyalty_logs')
                ->where('external_id', $doc['id'])
                ->exists();
            if ($alreadyProcessed) continue;

            // Calcular pontos
            $points = floor($doc['amount_gross'] * $pointsPerEuro);

            // Buscar ou criar conta de fidelidade
            $account = LoyaltyAccount::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->firstOrCreate(['user_id' => $user->id], ['points' => 0]);

            // Atualizar saldo de pontos
            $account->increment('points', $points);
              
            // Registrar transação de pontos
            DB::table('loyalty_transactions')->insert([
                'user_id'    => $user->id,
                'type'       => 'earn',
                'points'     => $points,
                'reason'     => 'Fatura #'.$doc['number'],
                'meta'       => json_encode(['invoice_id' => $doc['id'], 'amount_gross' => $doc['amount_gross']]),
                'created_at' => now(),
                'updated_at' => now(),
            ]); 

            // Registrar log de transação de pontos
            DB::table('loyalty_logs')->insert([
                'user_id'      => $user->id,
                'external_id'  => $doc['id'],
                'source'       => 'vendus',
                'points'       => $points,
                'created_at'   => now(),
            ]);

            $processed[] = [
                'invoice_id' => $doc['id'],
                'client'     => $user->name,
                'points'     => $points,
            ];

        }

        return [
            'status' => 'success',
            'processed' => $processed,
        ];
    }
}
