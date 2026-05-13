@extends('admin.layout')

@section('title', 'Alterar Senha')

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">Alterar minha senha</h2>
          <p style="margin:8px 0 0; color:#6b7280;">
            Atualize a senha do usuário logado para continuar com acesso ao painel administrativo.
          </p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Voltar</a>
      </div>
    </div>

    <div class="card">
      <form method="POST" action="{{ route('admin.users.password.update') }}" class="form-grid">
        @csrf
        @method('PUT')

        <div class="form-group">
          <label for="current_password">Senha atual</label>
          <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
          @error('current_password')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="password">Nova senha</label>
          <input id="password" type="password" name="password" required autocomplete="new-password">
          @error('password')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="password_confirmation">Confirmar nova senha</label>
          <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <div class="form-actions" style="margin-top:0;">
          <button type="submit" class="btn btn-primary">Atualizar senha</button>
        </div>
      </form>
    </div>
  </div>
@endsection
