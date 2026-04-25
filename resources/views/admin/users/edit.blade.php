@extends('admin.layout')

@section('title', 'Editar Usuário')

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">Editar {{ $user->name }}</h2>
          <p style="margin:8px 0 0; color:#6b7280;">
            Ajuste dados cadastrais usados no app e na sincronização Vendus.
          </p>
        </div>
        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">Voltar</a>
      </div>
    </div>

    <div class="card">
      <form method="POST" action="{{ route('admin.users.update', $user) }}" class="form-grid">
        @csrf
        @method('PUT')

        <div class="form-group">
          <label for="name">Nome</label>
          <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
          @error('name')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
          @error('email')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="nif">NIF</label>
          <input id="nif" type="text" name="nif" value="{{ old('nif', $user->nif) }}" maxlength="20">
          @error('nif')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="phone">Telefone</label>
          <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30">
          @error('phone')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="birth_date">Nascimento</label>
          <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}">
          @error('birth_date')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="role">Perfil</label>
          <select id="role" name="role" required>
            <option value="cliente" @selected(old('role', $user->role) === 'cliente')>Cliente</option>
            <option value="revendedor" @selected(old('role', $user->role) === 'revendedor')>Revendedor</option>
            <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
          </select>
          @error('role')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="street">Morada</label>
          <input id="street" type="text" name="street" value="{{ old('street', $user->street) }}" maxlength="255">
          @error('street')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="city">Cidade</label>
          <input id="city" type="text" name="city" value="{{ old('city', $user->city) }}" maxlength="100">
          @error('city')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="postal_code">Código postal</label>
          <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" maxlength="20">
          @error('postal_code')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
          <input type="checkbox" name="active" value="1" @checked(old('active', $user->active))>
          Usuário ativo
        </label>

        <div class="form-actions" style="margin-top:0;">
          <button type="submit" class="btn btn-primary">Salvar alterações</button>
          <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
@endsection
