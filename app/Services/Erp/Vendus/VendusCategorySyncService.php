<?php

namespace App\Services\Erp\Vendus;

use App\Models\Category;
use Illuminate\Support\Facades\Log;

class VendusCategorySyncService
{
    public function __construct(protected VendusHttpClient $http) {}

    /**
     * Busca as categorias do Vendus e sincroniza com o banco local
     */
    public function sync(): void
    {
        Log::info('🔁 [Vendus] Iniciando sincronização de categorias...');

        $response = $this->http->client()->get('/products/categories/');
        if (!$response->successful()) {
            Log::error('❌ [Vendus] Falha ao obter categorias', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return;
        }

        $categories = $response->json();
        if (!is_array($categories)) {
            Log::error('⚠️ [Vendus] Resposta inválida ao listar categorias', [
                'body' => $response->body(),
            ]);
            return;
        }

        foreach ($categories as $cat) {
            $this->syncCategory($cat);
        }

        Log::info('✅ [Vendus] Sincronização de categorias concluída', [
            'total' => count($categories),
        ]);
    }

    /**
     * Sincroniza uma única categoria
     */
    protected function syncCategory(array $cat): void
    {
        $externalId = (string)($cat['id'] ?? null);
        if (!$externalId) return;

        $name = $cat['title'] ?? 'Sem nome';
        $active = ($cat['status'] ?? 'on') === 'on';

        $category = Category::updateOrCreate(
            ['external_id' => $externalId],
            [
                'name'        => $name,
                'description' => $cat['description'] ?? null,
                'active'      => $active,
            ]
        );

        Log::info('📦 [Vendus] Categoria sincronizada', [
            'external_id' => $externalId,
            'name'        => $name,
            'active'      => $active,
        ]);
    }
}
