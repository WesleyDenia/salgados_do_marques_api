@extends('admin.layout')

@section('title', 'Editar Componente da Home')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Editar componente da Home</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Ajuste o rótulo, a descrição e a disponibilidade deste componente no painel administrativo.
    </p>

    @include('admin.home-components.form', ['component' => $component])
  </div>
@endsection
