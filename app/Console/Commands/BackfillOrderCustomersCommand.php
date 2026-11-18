<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use App\Services\CustomerDirectoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackfillOrderCustomersCommand extends Command
{
    protected $signature = 'orders:backfill-customers {--commit : Persiste as alterações após a validação}';

    protected $description = 'Associa encomendas históricas aos clientes, deduplicando por telemóvel normalizado.';

    public function handle(CustomerDirectoryService $customers): int
    {
        $lock = Cache::lock('orders:backfill-customers', 0);

        if (! $lock->get()) {
            $this->error('Já existe uma execução de retropreenchimento de clientes em curso.');

            return self::FAILURE;
        }

        try {
            $summary = $this->buildSummary($customers);
            $this->renderSummary($summary, $this->option('commit') ? 'pré-validação para commit' : 'simulação');

            if ($summary['conflicting_users'] > 0) {
                $this->error('Foram encontrados clientes existentes com o mesmo telemóvel normalizado. Nenhuma alteração foi realizada.');

                return self::FAILURE;
            }

            if (! $this->option('commit')) {
                $this->info('Simulação concluída. Execute novamente com --commit para persistir as alterações.');

                return self::SUCCESS;
            }

            $committed = DB::transaction(function () use ($customers): array {
                $this->normalizeExistingCustomers($customers);

                return $this->backfillOrders($customers);
            }, 1);

            Log::info('[BackfillOrderCustomers] Operação concluída', $committed);
            $this->renderSummary($committed, 'commit concluído');
            $this->info('Retropreenchimento concluído com sucesso.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('A operação falhou e foi revertida integralmente: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, int> */
    private function buildSummary(CustomerDirectoryService $customers): array
    {
        $summary = $this->emptySummary();
        $knownPhones = [];

        foreach (User::query()->where('role', User::ROLE_CLIENTE)->cursor() as $user) {
            $phone = $customers->normalizePhone($user->phone);

            if ($phone === null) {
                continue;
            }

            if (array_key_exists($phone, $knownPhones)) {
                $summary['conflicting_users']++;

                continue;
            }

            $knownPhones[$phone] = $user->id;

            if ($user->phone_normalized !== $phone) {
                $summary['customers_to_normalize']++;
            }
        }

        foreach (Order::query()->with('user:id,name,phone,role')->orderBy('id')->cursor() as $order) {
            $summary['orders_scanned']++;
            $name = $this->customerNameFor($order);
            $phone = $customers->normalizePhone($this->customerPhoneFor($order));

            if ($name === null || $phone === null) {
                $summary['orders_skipped']++;

                continue;
            }

            if (! array_key_exists($phone, $knownPhones)) {
                $knownPhones[$phone] = null;
                $summary['customers_to_create']++;
            }

            if (
                $knownPhones[$phone] === null
                || $order->user_id !== $knownPhones[$phone]
                || $order->customer_contact !== $phone
            ) {
                $summary['orders_to_update']++;
            }
        }

        return $summary;
    }

    private function normalizeExistingCustomers(CustomerDirectoryService $customers): void
    {
        foreach (User::query()->where('role', User::ROLE_CLIENTE)->orderBy('id')->cursor() as $user) {
            $phone = $customers->normalizePhone($user->phone);

            if ($phone !== null && $user->phone_normalized !== $phone) {
                $user->forceFill(['phone_normalized' => $phone])->save();
            }
        }
    }

    /** @return array<string, int> */
    private function backfillOrders(CustomerDirectoryService $customers): array
    {
        $summary = $this->emptySummary();

        foreach (Order::query()->with('user:id,name,phone,role')->orderBy('id')->cursor() as $order) {
            $summary['orders_scanned']++;
            $name = $this->customerNameFor($order);
            $phone = $customers->normalizePhone($this->customerPhoneFor($order));

            if ($name === null || $phone === null) {
                $summary['orders_skipped']++;

                continue;
            }

            $existing = $customers->findByNormalizedPhone($phone);
            $customer = $customers->findOrCreateOperationalCustomer($name, $phone, 'backfill_pending');

            if (! $existing) {
                $summary['customers_to_create']++;
            }

            if ($order->user_id !== $customer->id || $order->customer_contact !== $phone) {
                $order->forceFill([
                    'user_id' => $customer->id,
                    'customer_contact' => $phone,
                ])->save();
                $summary['orders_to_update']++;
            }
        }

        return $summary;
    }

    private function customerNameFor(Order $order): ?string
    {
        $name = trim((string) ($order->customer_name ?: $order->user?->name));

        return $name !== '' ? $name : null;
    }

    private function customerPhoneFor(Order $order): ?string
    {
        $phone = trim((string) ($order->customer_contact ?: $order->user?->phone));

        return $phone !== '' ? $phone : null;
    }

    /** @return array<string, int> */
    private function emptySummary(): array
    {
        return [
            'orders_scanned' => 0,
            'orders_to_update' => 0,
            'orders_skipped' => 0,
            'customers_to_create' => 0,
            'customers_to_normalize' => 0,
            'conflicting_users' => 0,
        ];
    }

    /** @param array<string, int> $summary */
    private function renderSummary(array $summary, string $label): void
    {
        $this->table(['Operação', 'Total'], [
            ['Estado', $label],
            ['Encomendas lidas', $summary['orders_scanned']],
            ['Encomendas a associar/normalizar', $summary['orders_to_update']],
            ['Encomendas sem nome ou telemóvel válido', $summary['orders_skipped']],
            ['Clientes a criar', $summary['customers_to_create']],
            ['Clientes existentes a normalizar', $summary['customers_to_normalize']],
            ['Conflitos de clientes existentes', $summary['conflicting_users']],
        ]);
    }
}
