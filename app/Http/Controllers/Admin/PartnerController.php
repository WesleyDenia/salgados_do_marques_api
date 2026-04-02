<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartnerStoreRequest;
use App\Models\Partner;
use App\Services\PartnerService;

class PartnerController extends Controller
{
    public function __construct(protected PartnerService $partners) {}

    public function index()
    {
        return view('admin.partners.index', [
            'partners' => $this->partners->listAdmin(),
        ]);
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
        $this->partners->createAdmin($request->validated(), $request->file('image'));

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
        $this->partners->updateAdmin($partner, $request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.partners.index')
            ->with('status', 'Parceiro atualizado com sucesso.');
    }

    public function destroy(Partner $partner)
    {
        $this->partners->deleteAdmin($partner);

        return redirect()
            ->route('admin.partners.index')
            ->with('status', 'Parceiro removido com sucesso.');
    }
}
