@extends('admin.layout')

@section('title', 'Lojas - Painel')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Lojas cadastradas</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Gerencie pontos de venda e revendedores do Coinxinhas.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.stores.create') }}">Nova loja</a>
    </div>

    <form method="GET" action="{{ route('admin.stores.index') }}" class="filter-grid">
      <div class="form-group">
        <label for="filter_city">Cidade</label>
        <input
          type="text"
          id="filter_city"
          name="city"
          value="{{ $filters['city'] ?? '' }}"
          placeholder="Filtrar por cidade"
        />
      </div>

      <div class="form-group">
        <label for="filter_type">Tipo</label>
        <select id="filter_type" name="type">
          <option value="">Todos</option>
          <option value="principal" @selected(($filters['type'] ?? '') === 'principal')>Principal</option>
          <option value="revenda" @selected(($filters['type'] ?? '') === 'revenda')>Revenda</option>
        </select>
      </div>

      <div class="form-group">
        <label for="filter_active">Estado</label>
        <select id="filter_active" name="is_active">
          <option value="">Todos</option>
          <option value="1" @selected(($filters['is_active'] ?? '') === '1')>Ativas</option>
          <option value="0" @selected(($filters['is_active'] ?? '') === '0')>Inativas</option>
        </select>
      </div>

      <div class="form-group align-end">
        <button type="submit" class="btn btn-secondary">Filtrar</button>
      </div>
    </form>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>Cidade</th>
          <th>Tipo</th>
          <th>Telefone</th>
          <th>Status</th>
          <th>Atualizado em</th>
          <th style="width:170px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($stores as $store)
          <tr>
            <td>{{ $store->id }}</td>
            <td>{{ $store->name }}</td>
            <td>{{ $store->city }}</td>
            <td>{{ ucfirst($store->type) }}</td>
            <td>{{ $store->phone ?? '—' }}</td>
            <td>
              @if ($store->is_active)
                <span class="badge badge-success">Ativa</span>
              @else
                <span class="badge badge-muted">Inativa</span>
              @endif
            </td>
            <td>{{ $store->updated_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td>
              <a class="btn btn-secondary" href="{{ route('admin.stores.edit', $store) }}">Editar</a>
              <form
                action="{{ route('admin.stores.destroy', $store) }}"
                method="POST"
                class="inline"
                onsubmit="return confirm('Remover esta loja?');"
              >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align:center; padding:32px 0; color:#6b7280;">
              Nenhuma loja encontrada.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:18px;">
      {{ $stores->links() }}
    </div>
  </div>
@endsection
