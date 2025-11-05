<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LoyaltyRewardController extends Controller
{
    public function index()
    {
        $rewards = LoyaltyReward::query()
            ->orderBy('threshold')
            ->paginate(15);

        return view('admin.loyalty-rewards.index', compact('rewards'));
    }

    public function create()
    {
        return view('admin.loyalty-rewards.create', [
            'reward' => new LoyaltyReward([
                'active' => true,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->storeImage($request);
        }

        $data['active'] = $request->boolean('active');

        LoyaltyReward::create($data);

        return redirect()
            ->route('admin.loyalty-rewards.index')
            ->with('status', 'Recompensa criada com sucesso.');
    }

    public function edit(LoyaltyReward $loyaltyReward)
    {
        return view('admin.loyalty-rewards.edit', [
            'reward' => $loyaltyReward,
        ]);
    }

    public function update(Request $request, LoyaltyReward $loyaltyReward)
    {
        $data = $this->validateData($request, $loyaltyReward->id);
        unset($data['image']);

        if ($request->filled('remove_image')) {
            $this->deleteImage($loyaltyReward->image_url);
            $data['image_url'] = null;
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($loyaltyReward->image_url);
            $data['image_url'] = $this->storeImage($request);
        }

        $data['active'] = $request->boolean('active');

        $loyaltyReward->update($data);

        return redirect()
            ->route('admin.loyalty-rewards.index')
            ->with('status', 'Recompensa atualizada com sucesso.');
    }

    public function destroy(LoyaltyReward $loyaltyReward)
    {
        $this->deleteImage($loyaltyReward->image_url);
        $loyaltyReward->delete();

        return redirect()
            ->route('admin.loyalty-rewards.index')
            ->with('status', 'Recompensa removida com sucesso.');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'value' => ['required', 'numeric', 'min:0'],
            'threshold' => [
                'required',
                'integer',
                'min:0',
                Rule::unique('loyalty_rewards', 'threshold')->ignore($id),
            ],
            'active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    protected function storeImage(Request $request): string
    {
        $path = $request->file('image')->store('loyalty-rewards', 'public');

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
}
