<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use App\Services\AdminCouponService;

class CouponController extends Controller
{
    public function __construct(protected AdminCouponService $coupons) {}

    public function index()
    {
        return view('admin.coupons.index', [
            'coupons' => $this->coupons->list(),
        ]);
    }

    public function create()
    {
        return view('admin.coupons.create', [
            'coupon' => new Coupon([
                'active' => true,
                'recurrence' => 'none',
                'type' => 'money',
                'amount' => 0,
            ]),
            'categories' => $this->coupons->categoryOptions(),
        ]);
    }

    public function store(CouponRequest $request)
    {
        $this->coupons->create($request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Cupom criado com sucesso.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', [
            'coupon' => $coupon,
            'categories' => $this->coupons->categoryOptions(),
        ]);
    }

    public function update(CouponRequest $request, Coupon $coupon)
    {
        $this->coupons->update($coupon, $request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Cupom atualizado com sucesso.');
    }

    public function destroy(Coupon $coupon)
    {
        $this->coupons->delete($coupon);

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Cupom removido com sucesso.');
    }
}
