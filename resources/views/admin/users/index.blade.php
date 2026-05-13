@extends('admin.layout')

@section('title', 'Usuários')

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">Gerenciamento de usuários</h2>
          <p style="margin:8px 0 0; color:#6b7280; max-width:820px;">
            Gerencie os usuários internos do painel administrativo. Clientes do app não aparecem nesta listagem.
          </p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.users.create') }}">Novo usuário</a>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:18px;">
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Total</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['total'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Ativos</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['active'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Operacional</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['operacional'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Atendimento</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['atendimento'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Admins</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['admins'] }}</div>
      </div>
    </div>

    <div class="card">
      <form method="GET" class="filter-grid">
        <div class="form-group">
          <label for="search">Pesquisar</label>
          <input
            id="search"
            type="text"
            name="search"
            value="{{ $filters['search'] }}"
            placeholder="Nome, email, telefone ou NIF"
          >
        </div>

        <div class="form-group">
          <label for="role">Perfil</label>
          <select id="role" name="role">
            <option value="">Todos</option>
            <option value="operacional" @selected($filters['role'] === 'operacional')>Operacional</option>
            <option value="atendimento" @selected($filters['role'] === 'atendimento')>Atendimento</option>
            <option value="admin" @selected($filters['role'] === 'admin')>Admin</option>
          </select>
        </div>

        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="">Todos</option>
            <option value="active" @selected($filters['status'] === 'active')>Ativo</option>
            <option value="inactive" @selected($filters['status'] === 'inactive')>Inativo</option>
          </select>
        </div>

        <div class="form-group align-end">
          <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
      </form>

      <div class="responsive-table-wrap">
        <table class="responsive-table">
          <thead>
            <tr>
              <th>Usuário</th>
              <th>Contacto</th>
              <th>Perfil</th>
              <th>Último login</th>
              <th>Status</th>
              <th style="width:120px;">Ações</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($users as $user)
              <tr>
                <td>
                  <span class="stack-table-label">Usuário</span>
                  <strong>{{ $user->name }}</strong><br>
                  <span style="color:#6b7280;">#{{ $user->id }}</span>
                </td>
                <td>
                  <span class="stack-table-label">Contacto</span>
                  <div>{{ $user->email }}</div>
                  <div style="color:#6b7280;">{{ $user->phone ?: '—' }}</div>
                </td>
                <td>
                  <span class="stack-table-label">Perfil</span>
                  {{ ucfirst($user->role) }}
                </td>
                <td>
                  <span class="stack-table-label">Último login</span>
                  {{ $user->last_login?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td>
                  <span class="stack-table-label">Status</span>
                  @if ($user->active)
                    <span class="badge badge-success">Ativo</span>
                  @else
                    <span class="badge badge-muted">Inativo</span>
                  @endif
                </td>
                <td>
                  <span class="stack-table-label">Ações</span>
                  <a class="btn btn-secondary" href="{{ route('admin.users.edit', $user) }}">Editar</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" style="text-align:center; padding:32px 0; color:#6b7280;">
                  Nenhum usuário encontrado.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div style="margin-top:18px;">
        {{ $users->links() }}
      </div>
    </div>
  </div>
@endsection
