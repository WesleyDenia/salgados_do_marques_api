@extends('admin.layout')

@section('title', 'Editar Usuário')

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">Editar {{ $user->name }}</h2>
          <p style="margin:8px 0 0; color:#6b7280;">
            Ajuste permissões, acesso e dados cadastrais do usuário.
          </p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Voltar</a>
      </div>
    </div>

    <div class="card">
      @include('admin.users.form')
    </div>

    @if (auth()->id() !== $user->id)
      <div class="card">
        <h3 style="margin:0 0 8px; font-size:1.1rem;">Excluir usuário</h3>
        <p style="margin:0 0 18px; color:#6b7280;">
          Remova o acesso deste usuário ao painel. Esta ação apaga o registo do usuário.
        </p>

        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Excluir este usuário?');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn" style="background:#b91c1c; color:#fff;">Excluir usuário</button>
        </form>
      </div>
    @endif
  </div>
@endsection
