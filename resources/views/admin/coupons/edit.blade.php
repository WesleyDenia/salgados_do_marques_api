@extends('admin.layout')

@section('title', 'Editar Cupom')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Editar cupom #{{ $coupon->id }}</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Atualize as informações desejadas e salve para aplicar as alterações.
    </p>

    @include('admin.coupons.form', ['coupon' => $coupon, 'categories' => $categories])
  </div>
@endsection
