<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FlavorRequest;
use App\Models\Flavor;
use App\Services\AdminFlavorService;

class FlavorController extends Controller
{
    public function __construct(protected AdminFlavorService $flavors) {}

    public function index()
    {
        return view('admin.flavors.index', [
            'flavors' => $this->flavors->list(),
        ]);
    }

    public function create()
    {
        return view('admin.flavors.create', [
            'flavor' => new Flavor([
                'active' => true,
            ]),
        ]);
    }

    public function store(FlavorRequest $request)
    {
        $this->flavors->create($request->validated());

        return redirect()
            ->route('admin.flavors.index')
            ->with('status', 'Sabor criado com sucesso.');
    }

    public function edit(Flavor $flavor)
    {
        return view('admin.flavors.edit', compact('flavor'));
    }

    public function update(FlavorRequest $request, Flavor $flavor)
    {
        $this->flavors->update($flavor, $request->validated());

        return redirect()
            ->route('admin.flavors.index')
            ->with('status', 'Sabor atualizado com sucesso.');
    }

    public function destroy(Flavor $flavor)
    {
        $this->flavors->delete($flavor);

        return redirect()
            ->route('admin.flavors.index')
            ->with('status', 'Sabor removido com sucesso.');
    }
}
