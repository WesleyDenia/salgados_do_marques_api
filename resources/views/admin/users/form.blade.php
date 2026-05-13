@php
  $isEdit = isset($user);
  $selectedRole = old('role', $user->role ?? \App\Models\User::ROLE_OPERACIONAL);
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}" class="form-grid">
  @csrf
  @if ($isEdit)
    @method('PUT')
  @endif

  <div class="form-group">
    <label for="name">Nome</label>
    <input id="name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required>
    @error('name')
      <span class="alert alert-error">{{ $message }}</span>
    @enderror
  </div>

  <div class="form-group">
    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
    @error('email')
      <span class="alert alert-error">{{ $message }}</span>
    @enderror
  </div>

  <div class="form-group">
    <label for="role">Perfil</label>
    <select id="role" name="role" required>
      @foreach ($roles as $role)
        <option value="{{ $role }}" @selected($selectedRole === $role)>{{ ucfirst($role) }}</option>
      @endforeach
    </select>
    @error('role')
      <span class="alert alert-error">{{ $message }}</span>
    @enderror
  </div>

  <div class="form-group">
    <label for="phone">Telefone</label>
    <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" maxlength="30">
    @error('phone')
      <span class="alert alert-error">{{ $message }}</span>
    @enderror
  </div>

  <div class="form-group">
    <label for="password">{{ $isEdit ? 'Nova senha' : 'Senha' }}</label>
    <input id="password" type="password" name="password" {{ $isEdit ? '' : 'required' }}>
    @error('password')
      <span class="alert alert-error">{{ $message }}</span>
    @enderror
  </div>

  <div class="form-group">
    <label for="password_confirmation">{{ $isEdit ? 'Confirmar nova senha' : 'Confirmar senha' }}</label>
    <input id="password_confirmation" type="password" name="password_confirmation" {{ $isEdit ? '' : 'required' }}>
  </div>

  <div class="form-group">
    <label for="nif">NIF</label>
    <input id="nif" type="text" name="nif" value="{{ old('nif', $user->nif ?? '') }}" maxlength="20">
    @error('nif')
      <span class="alert alert-error">{{ $message }}</span>
    @enderror
  </div>

  <div class="form-group">
    <label for="birth_date">Nascimento</label>
    <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', isset($user) ? $user->birth_date?->format('Y-m-d') : '') }}">
    @error('birth_date')
      <span class="alert alert-error">{{ $message }}</span>
    @enderror
  </div>

  <div class="form-group">
    <label for="street">Morada</label>
    <input id="street" type="text" name="street" value="{{ old('street', $user->street ?? '') }}" maxlength="255">
    @error('street')
      <span class="alert alert-error">{{ $message }}</span>
    @enderror
  </div>

  <div class="form-group">
    <label for="city">Cidade</label>
    <input id="city" type="text" name="city" value="{{ old('city', $user->city ?? '') }}" maxlength="100">
    @error('city')
      <span class="alert alert-error">{{ $message }}</span>
    @enderror
  </div>

  <div class="form-group">
    <label for="postal_code">Código postal</label>
    <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code ?? '') }}" maxlength="20">
    @error('postal_code')
      <span class="alert alert-error">{{ $message }}</span>
    @enderror
  </div>

  <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
    <input type="checkbox" name="active" value="1" @checked(old('active', $user->active ?? true))>
    Usuário ativo
  </label>

  @if ($isEdit)
    <p style="margin:0; color:#6b7280;">
      Deixe os campos de senha em branco para manter a senha atual.
    </p>
  @endif

  <div class="form-actions" style="margin-top:0;">
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Salvar alterações' : 'Criar usuário' }}</button>
    <a href="{{ $isEdit ? route('admin.users.show', $user) : route('admin.users.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
