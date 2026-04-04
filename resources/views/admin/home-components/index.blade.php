@extends('admin.layout')

@section('title', 'Componentes da Home')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Componentes da Home</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Registe aqui os componentes permitidos no Content Home para evitar listas hardcoded no painel.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.home-components.create') }}">Novo componente</a>
    </div>

    <table>
      <thead>
        <tr>
          <th>Rótulo</th>
          <th>Nome técnico</th>
          <th>Descrição</th>
          <th>Estado</th>
          <th style="width:76px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($components as $component)
          <tr>
            <td>{{ $component->label }}</td>
            <td><code>{{ $component->key }}</code></td>
            <td>{{ $component->description ?: '—' }}</td>
            <td>
              @if ($component->is_active)
                <span class="badge badge-success">Ativo</span>
              @else
                <span class="badge badge-muted">Inativo</span>
              @endif
            </td>
            <td>
              <details class="action-menu">
                <summary class="btn action-menu-trigger" aria-label="Abrir ações do componente">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <circle cx="8" cy="3" r="1.4" />
                    <circle cx="8" cy="8" r="1.4" />
                    <circle cx="8" cy="13" r="1.4" />
                  </svg>
                </summary>

                <div class="action-menu-panel">
                  <a class="btn action-menu-item" href="{{ route('admin.home-components.edit', $component) }}">Editar</a>
                  <form method="POST" action="{{ route('admin.home-components.destroy', $component) }}" onsubmit="return confirm('Remover este componente do catálogo?');">
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
            <td colspan="5" style="text-align:center; padding:32px 0; color:#6b7280;">
              Nenhum componente registado.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:18px;">
      {{ $components->links() }}
    </div>
  </div>
@endsection
