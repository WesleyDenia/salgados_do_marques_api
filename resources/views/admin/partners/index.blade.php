@extends('admin.layout')

@section('title', 'Parceiros')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Parceiros</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Gerencie os parceiros exibidos no aplicativo.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.partners.create') }}">Novo parceiro</a>
    </div>

    <div class="responsive-table-wrap">
      <table class="responsive-table">
        <thead>
        <tr>
          <th>Nome</th>
          <th>Slug</th>
          <th>Status</th>
          <th style="width:76px;">Ações</th>
        </tr>
        </thead>
        <tbody>
          @forelse ($partners as $partner)
            <tr>
              <td>
                <span class="stack-table-label">Nome</span>
                {{ $partner->name }}
              </td>
              <td>
                <span class="stack-table-label">Slug</span>
                {{ $partner->slug }}
              </td>
              <td>
                <span class="stack-table-label">Status</span>
                {{ $partner->active ? 'Ativo' : 'Inativo' }}
              </td>
              <td>
                <span class="stack-table-label">Ações</span>
                <details class="action-menu">
                  <summary class="btn action-menu-trigger" aria-label="Abrir ações do parceiro">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                      <circle cx="8" cy="3" r="1.4" />
                      <circle cx="8" cy="8" r="1.4" />
                      <circle cx="8" cy="13" r="1.4" />
                    </svg>
                  </summary>

                  <div class="action-menu-panel">
                    <a class="btn action-menu-item" href="{{ route('admin.partners.edit', $partner) }}">Editar</a>
                    <form action="{{ route('admin.partners.destroy', $partner) }}" method="POST" onsubmit="return confirm('Remover este parceiro?');">
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
                Nenhum parceiro cadastrado.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:18px;">
      {{ $partners->links() }}
    </div>
  </div>
@endsection
