<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flavor;
use Illuminate\Http\Request;

class FlavorController extends Controller
{
    public function index()
    {
        $flavors = Flavor::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('admin.flavors.index', compact('flavors'));
    }

    public function create()
    {
        return view('admin.flavors.create', [
            'flavor' => new Flavor([
                'active' => true,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['active'] = $request->boolean('active');
        $data['display_order'] = isset($data['display_order']) ? (int) $data['display_order'] : 0;

        Flavor::create($data);

        return redirect()
            ->route('admin.flavors.index')
            ->with('status', 'Sabor criado com sucesso.');
    }

    public function edit(Flavor $flavor)
    {
        return view('admin.flavors.edit', compact('flavor'));
    }

    public function update(Request $request, Flavor $flavor)
    {
        $data = $this->validateData($request);
        $data['active'] = $request->boolean('active');
        $data['display_order'] = isset($data['display_order']) ? (int) $data['display_order'] : 0;

        $flavor->update($data);

        return redirect()
            ->route('admin.flavors.index')
            ->with('status', 'Sabor atualizado com sucesso.');
    }

    public function destroy(Flavor $flavor)
    {
        $flavor->delete();

        return redirect()
            ->route('admin.flavors.index')
            ->with('status', 'Sabor removido com sucesso.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);
    }
}
