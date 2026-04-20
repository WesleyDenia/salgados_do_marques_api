@extends('admin.layout')

@section('title', 'Usuários')

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">Gerenciamento de usuários</h2>
          <p style="margin:8px 0 0; color:#6b7280; max-width:820px;">
            Consulte os clientes cadastrados, acompanhe saldo de Coinxinhas, visualize cupons atribuídos
            e entre na ficha individual para operações manuais.
          </p>
        </div>
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
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Com loyalty</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['with_loyalty'] }}</div>
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
            <option value="cliente" @selected($filters['role'] === 'cliente')>Cliente</option>
            <option value="revendedor" @selected($filters['role'] === 'revendedor')>Revendedor</option>
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

      <table>
        <thead>
          <tr>
            <th>Usuário</th>
            <th>Contacto</th>
            <th>Perfil</th>
            <th>Coinxinhas</th>
            <th>Cupons</th>
            <th>Encomendas</th>
            <th>Último login</th>
            <th>Status</th>
            <th style="width:120px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $user)
            <tr>
              <td>
                <strong>{{ $user->name }}</strong><br>
                <span style="color:#6b7280;">#{{ $user->id }}</span>
              </td>
              <td>
                <div>{{ $user->email }}</div>
                <div style="color:#6b7280;">{{ $user->phone ?: '—' }}</div>
              </td>
              <td>{{ ucfirst($user->role) }}</td>
              <td>{{ number_format((int) ($user->loyaltyAccount?->points ?? 0), 0, ',', '.') }}</td>
              <td>{{ $user->user_coupons_count }}</td>
              <td>{{ $user->orders_count }}</td>
              <td>{{ $user->last_login?->format('d/m/Y H:i') ?? '—' }}</td>
              <td>
                @if ($user->active)
                  <span class="badge badge-success">Ativo</span>
                @else
                  <span class="badge badge-muted">Inativo</span>
                @endif
              </td>
              <td>
                <a class="btn btn-secondary" href="{{ route('admin.users.show', $user) }}">Abrir</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" style="text-align:center; padding:32px 0; color:#6b7280;">
                Nenhum usuário encontrado.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div style="margin-top:18px;">
        {{ $users->links() }}
      </div>
    </div>
  </div>
@endsection
