@extends('admin.layout')

@section('title', 'Editar Loja - Painel')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Editar loja #{{ $store->id }}</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Atualize os dados conforme necessário e salve para refletir no aplicativo.
    </p>

    @include('admin.stores.form', ['store' => $store])
  </div>
@endsection
