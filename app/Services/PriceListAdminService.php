<?php

namespace App\Services;

use App\Models\PriceList;
use App\Repositories\PriceListRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PriceListAdminService
{
    public function __construct(protected PriceListRepository $prices) {}

    public function list(): Collection
    {
        return $this->prices->listForAdmin();
    }

    public function catalogTargets(): Collection
    {
        return $this->prices->catalogTargets();
    }

    public function activeTags(): Collection
    {
        return $this->prices->activeOrderTags();
    }

    public function loadForEdit(PriceList $priceList): PriceList
    {
        return $priceList->load(['tags', 'entries']);
    }

    public function create(array $data): PriceList
    {
        return DB::transaction(function () use ($data): PriceList {
            $priceList = PriceList::create($this->payload($data));
            $this->sync($priceList, $data);

            return $priceList;
        });
    }

    public function update(PriceList $priceList, array $data): PriceList
    {
        return DB::transaction(function () use ($data, $priceList): PriceList {
            if ($priceList->is_default) {
                unset($data['priority'], $data['starts_at'], $data['ends_at'], $data['active'], $data['tag_ids']);
            }

            $priceList->update($this->payload($data, $priceList));
            $this->sync($priceList, $data);

            return $priceList;
        });
    }

    public function delete(PriceList $priceList): bool
    {
        if ($priceList->is_default) {
            return false;
        }

        $priceList->delete();

        return true;
    }

    protected function payload(array $data, ?PriceList $priceList = null): array
    {
        return [
            'name' => $data['name'],
            'priority' => $priceList?->is_default ? $priceList->priority : (int) ($data['priority'] ?? 0),
            'starts_at' => $priceList?->is_default ? $priceList->starts_at : ($data['starts_at'] ?? null),
            'ends_at' => $priceList?->is_default ? $priceList->ends_at : ($data['ends_at'] ?? null),
            'active' => $priceList?->is_default ? $priceList->active : (bool) ($data['active'] ?? false),
        ];
    }

    protected function sync(PriceList $priceList, array $data): void
    {
        $tagIds = collect($data['tag_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $entries = collect($data['entries'] ?? [])
            ->filter(fn (array $entry) => ($entry['amount'] ?? null) !== null && $entry['amount'] !== '')
            ->map(fn (array $entry) => [
                'product_id' => ! empty($entry['product_id']) ? (int) $entry['product_id'] : null,
                'product_variant_id' => ! empty($entry['product_variant_id']) ? (int) $entry['product_variant_id'] : null,
                'amount' => (float) $entry['amount'],
            ])->values()->all();

        if ($priceList->is_default && count($entries) !== $this->prices->catalogTargetCount()) {
            throw ValidationException::withMessages(['entries' => 'A lista Normal deve manter preços para todos os itens de catálogo.']);
        }

        $targets = [];
        foreach ($entries as $entry) {
            if (($entry['product_id'] === null) === ($entry['product_variant_id'] === null)) {
                throw ValidationException::withMessages(['entries' => 'Cada preço deve apontar para um produto ou para um pack.']);
            }

            $target = $entry['product_variant_id'] === null ? 'p-'.$entry['product_id'] : 'v-'.$entry['product_variant_id'];
            if (isset($targets[$target])) {
                throw ValidationException::withMessages(['entries' => 'Um item não pode ter dois preços na mesma lista.']);
            }
            $targets[$target] = true;

            if (! $priceList->is_default && $priceList->active && $this->prices->hasApplicableConflict($priceList, $entry, $tagIds)) {
                throw ValidationException::withMessages(['entries' => 'Existe uma campanha com a mesma prioridade aplicável a este item.']);
            }
        }

        $priceList->tags()->sync($priceList->is_default ? [] : $tagIds);
        $priceList->entries()->delete();
        $priceList->entries()->createMany($entries);
    }
}
