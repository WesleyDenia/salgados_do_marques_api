<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeComponent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeComponentController extends Controller
{
    public function index()
    {
        $components = HomeComponent::query()
            ->orderByDesc('is_active')
            ->orderBy('label')
            ->paginate(20);

        return view('admin.home-components.index', compact('components'));
    }

    public function create()
    {
        $component = new HomeComponent([
            'is_active' => true,
        ]);

        return view('admin.home-components.create', compact('component'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active', true);

        HomeComponent::create($data);

        return redirect()
            ->route('admin.home-components.index')
            ->with('status', 'Componente de Home criado com sucesso.');
    }

    public function edit(HomeComponent $homeComponent)
    {
        $component = $homeComponent;

        return view('admin.home-components.edit', compact('component'));
    }

    public function update(Request $request, HomeComponent $homeComponent)
    {
        $data = $this->validateData($request, $homeComponent->id);
        $data['is_active'] = $request->boolean('is_active');

        $homeComponent->update($data);

        return redirect()
            ->route('admin.home-components.index')
            ->with('status', 'Componente de Home atualizado com sucesso.');
    }

    public function destroy(HomeComponent $homeComponent)
    {
        $inUse = $homeComponent->key
            && \App\Models\ContentHome::query()
                ->where('component_name', $homeComponent->key)
                ->exists();

        if ($inUse) {
            return redirect()
                ->route('admin.home-components.index')
                ->with('error', 'Este componente está em uso no Content Home e não pode ser removido.');
        }

        $homeComponent->delete();

        return redirect()
            ->route('admin.home-components.index')
            ->with('status', 'Componente de Home removido com sucesso.');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z][A-Za-z0-9]*$/',
                Rule::unique('home_components', 'key')->ignore($id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
