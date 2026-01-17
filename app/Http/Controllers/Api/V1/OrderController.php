<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(protected SettingService $settings) {}

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

        $this->validateScheduledAt($scheduled, $orderSettings);

        $items = collect($data['items']);
        $productIds = $items->pluck('product_id')->unique()->values();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('active', true)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Um ou mais produtos não estão disponíveis.',
            ]);
        }

        $order = DB::transaction(function () use ($request, $data, $items, $products, $scheduled) {
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
                $quantity = (int) $item['quantity'];
                $price = (float) $product->price;
                $lineTotal = $price * $quantity;

                $order->items()->create([
                    'product_id' => $product->id,
                    'name_snapshot' => $product->name,
                    'price_snapshot' => $price,
                    'quantity' => $quantity,
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
            'start_time' => $this->settings->get('order_start_time', '12:00'),
            'end_time' => $this->settings->get('order_end_time', '20:00'),
            'minimum_minutes' => (int) $this->settings->get('order_minimum_minutes', 30),
            'cancel_minutes' => (int) $this->settings->get('order_cancel_minutes', 60),
            'timezone' => $this->settings->get('order_timezone', 'Europe/Lisbon'),
        ];
    }

    protected function parseScheduledAt(string $value, string $timezone): Carbon
    {
        return Carbon::parse($value, $timezone);
    }

    protected function validateScheduledAt(Carbon $scheduled, array $settings): void
    {
        $timezone = $settings['timezone'];
        $now = Carbon::now($timezone);
        $minimumMinutes = max(0, (int) $settings['minimum_minutes']);
        $minimumAllowed = $now->copy()->addMinutes($minimumMinutes);

        if ($scheduled->lessThan($minimumAllowed)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'O horário escolhido precisa respeitar o tempo mínimo de preparação.',
            ]);
        }

        $startTime = $settings['start_time'];
        $endTime = $settings['end_time'];

        if ($startTime && $endTime) {
            $date = $scheduled->format('Y-m-d');
            $start = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $startTime, $timezone);
            $end = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $endTime, $timezone);

            if ($scheduled->lessThan($start) || $scheduled->greaterThan($end)) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'O horário precisa estar dentro do período de atendimento.',
                ]);
            }
        }
    }
}
