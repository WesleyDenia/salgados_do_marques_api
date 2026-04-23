<?php

namespace Tests\Unit;

use App\Services\Erp\Vendus\VendusCustomerSyncService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VendusCustomerSyncServiceTest extends TestCase
{
    public function test_find_by_fiscal_id_treats_vendus_no_data_as_not_found(): void
    {
        config([
            'services.vendus.base_url' => 'https://vendus.test/ws/v1.1',
            'services.vendus.token' => 'test-token',
        ]);

        Http::fake([
            'vendus.test/ws/v1.1/clients/*' => Http::response([
                'errors' => [
                    [
                        'code' => 'A001',
                        'message' => 'No data',
                    ],
                ],
            ], 404),
        ]);

        $this->assertNull(app(VendusCustomerSyncService::class)->findByFiscalId('315454810'));
    }
}
