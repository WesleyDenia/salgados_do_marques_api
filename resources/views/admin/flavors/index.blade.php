@extends('admin.layout')

@section('title', 'Sabores')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Sabores</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Gerencie os sabores disponíveis para os packs.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.flavors.create') }}">Novo sabor</a>
    </div>

    <div class="responsive-table-wrap">
      <table class="responsive-table">
        <thead>
        <tr>
          <th style="width:90px;">Ordem</th>
          <th>Nome</th>
          <th>Status</th>
          <th style="width:76px;">Ações</th>
        </tr>
        </thead>
        <tbody>
          @forelse ($flavors as $flavor)
            <tr>
              <td>
                <span class="stack-table-label">Ordem</span>
                {{ $flavor->display_order }}
              </td>
              <td>
                <span class="stack-table-label">Nome</span>
                <strong>{{ $flavor->name }}</strong>
              </td>
              <td>
                <span class="stack-table-label">Status</span>
                @if ($flavor->active)
                  <span class="badge badge-success">Ativo</span>
                @else
                  <span class="badge badge-muted">Inativo</span>
                @endif
              </td>
              <td>
                <span class="stack-table-label">Ações</span>
                <details class="action-menu">
                  <summary class="btn action-menu-trigger" aria-label="Abrir ações do sabor">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                      <circle cx="8" cy="3" r="1.4" />
                      <circle cx="8" cy="8" r="1.4" />
                      <circle cx="8" cy="13" r="1.4" />
                    </svg>
                  </summary>

                  <div class="action-menu-panel">
                    <a class="btn action-menu-item" href="{{ route('admin.flavors.edit', $flavor) }}">Editar</a>
                    <form
                      action="{{ route('admin.flavors.destroy', $flavor) }}"
                      method="POST"
                      onsubmit="return confirm('Remover este sabor?');"
                    >
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn action-menu-item action-menu-item-danger">Excluir</button>
                    </form>
                  </div>
                </details>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" style="text-align:center; padding:32px 0; color:#6b7280;">
                Nenhum sabor cadastrado.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
