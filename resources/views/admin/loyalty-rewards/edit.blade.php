@extends('admin.layout')

@section('title', 'Editar Recompensa')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Editar recompensa #{{ $reward->id }}</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Atualize as informações e salve para aplicar as alterações.
    </p>

    @include('admin.loyalty-rewards.form', ['reward' => $reward])
  </div>
@endsection
