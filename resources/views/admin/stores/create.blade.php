@extends('admin.layout')

@section('title', 'Nova Loja - Painel')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Cadastrar nova loja</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Informe os dados do ponto de venda para disponibilizá-lo no aplicativo.
    </p>

    @include('admin.stores.form', ['store' => $store])
  </div>
@endsection
