<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomeComponentRequest;
use App\Models\HomeComponent;
use App\Services\HomeComponentAdminService;

class HomeComponentController extends Controller
{
    public function __construct(protected HomeComponentAdminService $components) {}

    public function index()
    {
        return view('admin.home-components.index', [
            'components' => $this->components->list(),
        ]);
    }

    public function create()
    {
        return view('admin.home-components.create', [
            'component' => new HomeComponent([
                'is_active' => true,
            ]),
        ]);
    }

    public function store(HomeComponentRequest $request)
    {
        $this->components->create($request->validated());

        return redirect()
            ->route('admin.home-components.index')
            ->with('status', 'Componente de Home criado com sucesso.');
    }

    public function edit(HomeComponent $homeComponent)
    {
        return view('admin.home-components.edit', [
            'component' => $homeComponent,
        ]);
    }

    public function update(HomeComponentRequest $request, HomeComponent $homeComponent)
    {
        $this->components->update($homeComponent, $request->validated());

        return redirect()
            ->route('admin.home-components.index')
            ->with('status', 'Componente de Home atualizado com sucesso.');
    }

    public function destroy(HomeComponent $homeComponent)
    {
        if (!$this->components->delete($homeComponent)) {
            return redirect()
                ->route('admin.home-components.index')
                ->with('error', 'Este componente está em uso no Content Home e não pode ser removido.');
        }

        return redirect()
            ->route('admin.home-components.index')
            ->with('status', 'Componente de Home removido com sucesso.');
    }
}
