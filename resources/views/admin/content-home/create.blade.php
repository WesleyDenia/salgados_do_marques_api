@extends('admin.layout')

@section('title', 'Novo Conteúdo - ContentHome')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Novo conteúdo</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Preencha os campos abaixo para criar um novo bloco para a home.
    </p>

    @include('admin.content-home.form', ['item' => $item, 'components' => $components])
  </div>
@endsection
