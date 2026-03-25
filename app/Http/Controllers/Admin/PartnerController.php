<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartnerStoreRequest;
use App\Models\Partner;
use Illuminate\Support\Facades\Storage;

class PartnerController extends Controller
{
    public function index()
    {
        $partners = Partner::query()
            ->orderBy('name')
            ->paginate(15);

        return view('admin.partners.index', compact('partners'));
    }

    public function create()
    {
        return view('admin.partners.create', [
            'partner' => new Partner([
                'active' => true,
            ]),
        ]);
    }

    public function store(PartnerStoreRequest $request)
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->storeImage($request);
        }

        $data['active'] = $request->boolean('active');

        Partner::create($data);

        return redirect()
            ->route('admin.partners.index')
            ->with('status', 'Parceiro criado com sucesso.');
    }

    public function edit(Partner $partner)
    {
        return view('admin.partners.edit', compact('partner'));
    }

    public function update(PartnerStoreRequest $request, Partner $partner)
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->filled('remove_image')) {
            $this->deleteImage($partner->image_url);
            $data['image_url'] = null;
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($partner->image_url);
            $data['image_url'] = $this->storeImage($request);
        }

        $data['active'] = $request->boolean('active');

        $partner->update($data);

        return redirect()
            ->route('admin.partners.index')
            ->with('status', 'Parceiro atualizado com sucesso.');
    }

    public function destroy(Partner $partner)
    {
        $this->deleteImage($partner->image_url);
        $partner->delete();

        return redirect()
            ->route('admin.partners.index')
            ->with('status', 'Parceiro removido com sucesso.');
    }

    protected function storeImage(PartnerStoreRequest $request): string
    {
        $path = $request->file('image')->store('partners', 'public');

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
