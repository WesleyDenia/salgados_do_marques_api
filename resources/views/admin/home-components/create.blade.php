@extends('admin.layout')

@section('title', 'Novo Componente da Home')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Novo componente da Home</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Registe um componente já suportado pelo app para que ele fique disponível no Content Home.
    </p>

    @include('admin.home-components.form', ['component' => $component])
  </div>
@endsection
