@extends('admin.layout')

@section('title', 'Editar Conteúdo - ContentHome')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Editar conteúdo #{{ $item->id }}</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Atualize as informações necessárias e salve para publicar as alterações.
    </p>

    @include('admin.content-home.form', ['item' => $item, 'components' => $components])
  </div>
@endsection
