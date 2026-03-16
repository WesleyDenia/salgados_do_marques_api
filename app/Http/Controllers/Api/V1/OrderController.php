<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SettingService;
use App\Services\StoreService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        protected SettingService $settings,
        protected StoreService $stores,
    ) {}

    public function settings()
    {
        return response()->json([
            'data' => $this->resolveOrderSettings(),
        ]);
    }

    public function index(Request $request)
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items', 'store'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        $order->load(['items', 'store']);

        return new OrderResource($order);
    }

    public function store(OrderStoreRequest $request)
    {
        $data = $request->validated();
        $orderSettings = $this->resolveOrderSettings();
        $scheduled = $this->parseScheduledAt($data['scheduled_at'], $orderSettings['timezone']);
        $store = $this->stores->findById((int) $data['store_id']);

        if (!$store) {
            throw ValidationException::withMessages([
                'store_id' => 'A loja selecionada não existe.',
            ]);
        }

        $this->stores->validateScheduledPickup($store, $scheduled, $orderSettings);

        $items = collect($data['items']);
        $productIds = $items->pluck('product_id')->unique()->values();
        $variantIds = $items->pluck('variant_id')->filter()->unique()->values();
        $products = Product::query()
            ->with('allowedFlavors')
            ->whereIn('id', $productIds)
            ->where('active', true)
            ->get()
            ->keyBy('id');
        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->where('active', true)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Um ou mais produtos não estão disponíveis.',
            ]);
        }

        if ($variants->count() !== $variantIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Uma ou mais opções de pack não estão disponíveis.',
            ]);
        }

        $order = DB::transaction(function () use ($request, $data, $items, $products, $variants, $scheduled) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'store_id' => $data['store_id'],
                'status' => 'placed',
                'scheduled_at' => $scheduled->copy()->timezone('UTC'),
                'total' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $total = 0;

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $variant = $item['variant_id'] ? $variants->get($item['variant_id']) : null;
                $quantity = (int) $item['quantity'];

                if ($variant && $variant->product_id !== $product->id) {
                    throw ValidationException::withMessages([
                        'items' => 'A opção selecionada não pertence ao produto escolhido.',
                    ]);
                }

                $flavors = isset($item['flavors']) && is_array($item['flavors']) ? $item['flavors'] : [];
                $allowedFlavorIds = $product->allowedFlavors->pluck('id');

                if (!$variant && !empty($flavors)) {
                    throw ValidationException::withMessages([
                        'items' => 'Os sabores só podem ser informados para packs.',
                    ]);
                }

                if ($variant) {
                    if (count($flavors) < 1) {
                        throw ValidationException::withMessages([
                            'items' => 'Selecione pelo menos 1 sabor para o pack escolhido.',
                        ]);
                    }

                    $maxFlavors = (int) $variant->max_flavors;
                    if ($maxFlavors < 1) {
                        throw ValidationException::withMessages([
                            'items' => 'O pack selecionado não aceita sabores no momento.',
                        ]);
                    }

                    if (count($flavors) > $maxFlavors) {
                        throw ValidationException::withMessages([
                            'items' => 'Você selecionou mais sabores do que o permitido para este pack.',
                        ]);
                    }

                    $hasInvalidFlavor = collect($flavors)->contains(
                        fn ($flavorId) => !$allowedFlavorIds->contains((int) $flavorId)
                    );

                    if ($hasInvalidFlavor) {
                        throw ValidationException::withMessages([
                            'items' => 'Um ou mais sabores informados não são permitidos para este artigo.',
                        ]);
                    }
                }

                $price = $variant ? (float) $variant->price : (float) $product->price;
                $lineTotal = $price * $quantity;

                $order->items()->create([
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'name_snapshot' => $variant ? $variant->name : $product->name,
                    'price_snapshot' => $price,
                    'quantity' => $quantity,
                    'options' => !empty($flavors) ? ['flavors' => $flavors] : null,
                    'total' => $lineTotal,
                ]);

                $total += $lineTotal;
            }

            $order->update(['total' => $total]);

            return $order;
        });

        $order->load(['items', 'store']);

        return new OrderResource($order);
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        if (!in_array($order->status, ['placed', 'accepted'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Este pedido não pode ser cancelado.',
            ]);
        }

        $orderSettings = $this->resolveOrderSettings();
        $timezone = $orderSettings['timezone'];
        $cancelMinutes = max(0, (int) $orderSettings['cancel_minutes']);
        $now = Carbon::now($timezone);
        $scheduled = Carbon::parse($order->scheduled_at, 'UTC')->timezone($timezone);
        $deadline = $scheduled->copy()->subMinutes($cancelMinutes);

        if ($now->greaterThan($deadline)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'O prazo para cancelamento deste pedido expirou.',
            ]);
        }

        $order->update([
            'status' => 'canceled',
            'cancelled_at' => Carbon::now('UTC'),
        ]);

        $order->load(['items', 'store']);

        return new OrderResource($order);
    }

    protected function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    protected function resolveOrderSettings(): array
    {
        return [
            'start_time' => $this->settings->get(
                'ORDER_START_TIME',
                $this->settings->get('order_start_time', '12:00')
            ),
            'end_time' => $this->settings->get(
                'ORDER_END_TIME',
                $this->settings->get('order_end_time', '20:00')
            ),
            'minimum_minutes' => (int) $this->settings->get(
                'ORDER_MINIMUM_MINUTES',
                $this->settings->get('order_minimum_minutes', 30)
            ),
            'cancel_minutes' => (int) $this->settings->get(
                'ORDER_CANCEL_MINUTES',
                $this->settings->get('order_cancel_minutes', 60)
            ),
            'timezone' => $this->settings->get(
                'ORDER_TIMEZONE',
                $this->settings->get('order_timezone', 'Europe/Lisbon')
            ),
            'scheduling_window_days' => max(1, (int) $this->settings->get(
                'ORDER_SCHEDULING_WINDOW_DAYS',
                14
            )),
        ];
    }

    protected function parseScheduledAt(string $value, string $timezone): Carbon
    {
        return Carbon::parse($value, $timezone);
    }

}
