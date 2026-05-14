@extends('admin.layout')

@section('title', 'Clientes')

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">Clientes</h2>
          <p style="margin:8px 0 0; color:#6b7280; max-width:820px;">
            Lista de usuários com perfil de cliente, com acesso rápido ao cadastro, loyalty, cupons e pedidos.
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
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Com pedidos</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['with_orders'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Com loyalty</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['with_loyalty'] }}</div>
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
              <th>Cliente</th>
              <th>Contacto</th>
              <th>Loyalty</th>
              <th>Cupons</th>
              <th>Pedidos</th>
              <th>Status</th>
              <th style="width:120px;">Ações</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($users as $user)
              <tr>
                <td>
                  <span class="stack-table-label">Cliente</span>
                  <strong>{{ $user->name }}</strong><br>
                  <span style="color:#6b7280;">#{{ $user->id }}</span>
                </td>
                <td>
                  <span class="stack-table-label">Contacto</span>
                  <div>{{ $user->email }}</div>
                  <div style="color:#6b7280;">{{ $user->phone ?: '—' }}</div>
                </td>
                <td>
                  <span class="stack-table-label">Loyalty</span>
                  {{ number_format((int) ($user->loyaltyAccount?->points ?? 0), 0, ',', '.') }}
                </td>
                <td>
                  <span class="stack-table-label">Cupons</span>
                  {{ $user->user_coupons_count }}
                </td>
                <td>
                  <span class="stack-table-label">Pedidos</span>
                  {{ $user->orders_count }}
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
                  <a class="btn btn-secondary" href="{{ route('admin.customers.show', $user) }}">Abrir</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="text-align:center; padding:32px 0; color:#6b7280;">
                  Nenhum cliente encontrado.
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
