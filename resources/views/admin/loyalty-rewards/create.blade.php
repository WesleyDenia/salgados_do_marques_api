@extends('admin.layout')

@section('title', 'Nova Recompensa')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Nova recompensa</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Defina o nome, os pontos necessários e as informações da recompensa.
    </p>

    @include('admin.loyalty-rewards.form', ['reward' => $reward])
  </div>
@endsection
