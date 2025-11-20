<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentHome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentHomeController extends Controller
{
    public function index()
    {
        $items = ContentHome::query()
            ->orderBy('display_order')
            ->orderByDesc('publish_at')
            ->paginate(12);

        return view('admin.content-home.index', compact('items'));
    }

    public function create()
    {
        $item = new ContentHome([
            'display_order' => 0,
            'type' => 'text',
            'layout' => 'default',
            'is_active' => true,
        ]);

        return view('admin.content-home.create', [
            'item' => $item,
            'components' => $this->availableComponents(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->prepareComponentData($this->validateData($request));

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->storeImage($request);
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['cta_image_only'] = $request->boolean('cta_image_only');

        DB::transaction(function () use ($data) {
            $desiredOrder = $data['display_order'];

            $existing = ContentHome::where('display_order', $desiredOrder)->first();
            if ($existing) {
                $maxOrder = ContentHome::max('display_order') ?? 0;
                $existing->update(['display_order' => $maxOrder + 1]);
            }

            ContentHome::create($data);
        });

        return redirect()
            ->route('admin.content-home.index')
            ->with('status', 'Conteúdo criado com sucesso.');
    }

    public function edit(ContentHome $contentHome)
    {
        return view('admin.content-home.edit', [
            'item' => $contentHome,
            'components' => $this->availableComponents(),
        ]);
    }

    public function update(Request $request, ContentHome $contentHome)
    {
        $data = $this->prepareComponentData($this->validateData($request, $contentHome->id));

        $data['is_active'] = $request->boolean('is_active');
        $data['cta_image_only'] = $request->boolean('cta_image_only');

        if ($request->filled('remove_image')) {
            $this->deleteImage($contentHome->image_url);
            $data['image_url'] = null;
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($contentHome->image_url);
            $data['image_url'] = $this->storeImage($request);
        }

        DB::transaction(function () use ($contentHome, $data) {
            $currentOrder = $contentHome->display_order;
            $desiredOrder = $data['display_order'];

            if ($currentOrder !== $desiredOrder) {
                $clashing = ContentHome::where('display_order', $desiredOrder)
                    ->where('id', '!=', $contentHome->id)
                    ->first();

                if ($clashing) {
                    $clashing->update(['display_order' => $currentOrder]);
                }
            }

            $contentHome->update($data);
        });

        return redirect()
            ->route('admin.content-home.index')
            ->with('status', 'Conteúdo atualizado com sucesso.');
    }

    public function destroy(ContentHome $contentHome)
    {
        $this->deleteImage($contentHome->image_url);
        $contentHome->delete();

        return redirect()
            ->route('admin.content-home.index')
            ->with('status', 'Conteúdo removido com sucesso.');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        $componentKeys = array_keys($this->availableComponents());

        return $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'text_body' => ['nullable', 'string'],
            'display_order' => ['required', 'integer', 'min:0'],
            'type' => ['required', Rule::in(['text', 'image', 'only_image', 'component'])],
            'layout' => ['required', 'string', 'max:50'],
            'component_name' => ['nullable', 'string', 'max:100', Rule::in($componentKeys)],
            'component_props' => ['nullable', 'json'],
            'cta_label' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'cta_image_only' => ['nullable', 'boolean'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'publish_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:3072'],
        ]);
    }

    protected function storeImage(Request $request): string
    {
        $path = $request->file('image')->store('content-home', 'public');

        return Storage::url($path);
    }

    protected function deleteImage(?string $url): void
    {
        if (!$url) {
            return;
        }

        $disk = Storage::disk('public');
        $path = str_replace('/storage/', '', $url);

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    protected function prepareComponentData(array $data): array
    {
        if (($data['type'] ?? null) === 'component') {
            if (empty($data['component_name'])) {
                throw ValidationException::withMessages([
                    'component_name' => 'Selecione um componente válido.',
                ]);
            }

            $props = $data['component_props'] ?? null;
            if ($props === null || $props === '') {
                $data['component_props'] = null;
            } else {
                $decoded = json_decode((string) $props, true);
                $data['component_props'] = is_array($decoded) ? $decoded : null;
            }
        } else {
            $data['component_name'] = null;
            $data['component_props'] = null;
        }

        return $data;
    }

    protected function availableComponents(): array
    {
        return [
            'WelcomeBonusButton' => 'Botão Bônus de boas-vindas',
            'CouponsCarousel' => 'Carrossel de cupons',
        ];
    }
}
