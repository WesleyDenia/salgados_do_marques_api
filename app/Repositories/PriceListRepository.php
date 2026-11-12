<?php

namespace App\Repositories;

use App\Models\PriceEntry;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PriceListRepository extends BaseRepository
{
    public function __construct(PriceList $model)
    {
        parent::__construct($model);
    }

    public function normal(): PriceList
    {
        return PriceList::query()->firstOrCreate(
            ['is_default' => true],
            ['name' => 'Normal', 'priority' => 0, 'active' => true]
        );
    }

    public function resolveEntry(Product $product, ?ProductVariant $variant, array $tagIds, CarbonInterface $at): ?PriceEntry
    {
        return PriceEntry::query()
            ->with('priceList')
            ->when(
                $variant !== null,
                fn ($query) => $query->where('product_variant_id', $variant->id),
                fn ($query) => $query->where('product_id', $product->id)->whereNull('product_variant_id')
            )
            ->whereHas('priceList', function ($query) use ($tagIds, $at): void {
                $query->where('active', true)
                    ->where(fn ($dateQuery) => $dateQuery->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
                    ->where(fn ($dateQuery) => $dateQuery->whereNull('ends_at')->orWhere('ends_at', '>=', $at))
                    ->where(function ($tagQuery) use ($tagIds): void {
                        $tagQuery->whereDoesntHave('tags');

                        if ($tagIds !== []) {
                            $tagQuery->orWhereHas('tags', fn ($query) => $query
                                ->whereIn('order_tags.id', $tagIds)
                                ->where('order_tags.active', true));
                        }
                    });
            })
            ->join('price_lists', 'price_lists.id', '=', 'price_entries.price_list_id')
            ->orderByDesc('price_lists.priority')
            ->orderBy('price_lists.id')
            ->select('price_entries.*')
            ->first();
    }

    public function listForAdmin(): Collection
    {
        return PriceList::query()
            ->with('tags')
            ->withCount('entries')
            ->orderByDesc('is_default')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();
    }

    public function cloneSources(): Collection
    {
        return PriceList::query()
            ->with('entries')
            ->orderByDesc('is_default')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();
    }

    public function catalogTargets(): Collection
    {
        return Product::query()->with('variants')->orderBy('name')->get();
    }

    public function catalogTargetCount(): int
    {
        return Product::query()->count() + ProductVariant::query()->count();
    }

    public function activeOrderTags(): Collection
    {
        return \App\Models\OrderTag::query()->where('active', true)->orderBy('name')->get();
    }

    public function syncNormalPrices(Product $product): void
    {
        $normal = $this->normal();
        $now = now();

        DB::table('price_entries')->updateOrInsert(
            ['price_list_id' => $normal->id, 'product_id' => $product->id],
            ['product_variant_id' => null, 'amount' => $product->price, 'updated_at' => $now, 'created_at' => $now]
        );

        $product->variants()->get()->each(function (ProductVariant $variant) use ($normal, $now): void {
            DB::table('price_entries')->updateOrInsert(
                ['price_list_id' => $normal->id, 'product_variant_id' => $variant->id],
                ['product_id' => null, 'amount' => $variant->price, 'updated_at' => $now, 'created_at' => $now]
            );
        });
    }

    public function hasApplicableConflict(PriceList $priceList, array $entry, array $tagIds): bool
    {
        return PriceEntry::query()
            ->where(function ($query) use ($entry): void {
                if (! empty($entry['product_variant_id'])) {
                    $query->where('product_variant_id', $entry['product_variant_id']);

                    return;
                }

                $query->where('product_id', $entry['product_id'])->whereNull('product_variant_id');
            })
            ->whereHas('priceList', function ($query) use ($priceList, $tagIds): void {
                $query->where('id', '!=', $priceList->id)
                    ->where('active', true)
                    ->where('priority', $priceList->priority)
                    ->where(function ($tags) use ($tagIds): void {
                        $tags->whereDoesntHave('tags');

                        if ($tagIds !== []) {
                            $tags->orWhereHas('tags', fn ($query) => $query->whereIn('order_tags.id', $tagIds));
                        }
                    });

                if ($priceList->starts_at !== null) {
                    $query->where(fn ($dates) => $dates->whereNull('ends_at')->orWhere('ends_at', '>=', $priceList->starts_at));
                }

                if ($priceList->ends_at !== null) {
                    $query->where(fn ($dates) => $dates->whereNull('starts_at')->orWhere('starts_at', '<=', $priceList->ends_at));
                }
            })
            ->exists();
    }
}
