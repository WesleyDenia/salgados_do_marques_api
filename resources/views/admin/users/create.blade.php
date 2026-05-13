@extends('admin.layout')

@section('title', 'Novo Usuário')

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">Novo usuário operacional</h2>
          <p style="margin:8px 0 0; color:#6b7280;">
            Cadastre perfis internos do painel, sem misturar clientes na operação administrativa.
          </p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Voltar</a>
      </div>
    </div>

    <div class="card">
      @include('admin.users.form')
    </div>
  </div>
@endsection
