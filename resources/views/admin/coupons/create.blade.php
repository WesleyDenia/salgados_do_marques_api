@extends('admin.layout')

@section('title', 'Novo Cupom')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Novo cupom</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Preencha as informações abaixo para criar um novo cupom de desconto.
    </p>

    @include('admin.coupons.form', ['coupon' => $coupon, 'categories' => $categories])
  </div>
@endsection
