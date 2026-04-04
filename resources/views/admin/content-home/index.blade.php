@extends('admin.layout')

@section('title', 'ContentHome - Painel')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">ContentHome</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Gerencie os blocos exibidos na home do aplicativo.
        </p>
        <p style="margin:8px 0 0; color:#9ca3af; font-size:0.88rem;">
          Arraste os itens pela coluna de ordem para reorganizar a sequência de exibição.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.content-home.create') }}">Novo conteúdo</a>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Ordem</th>
          <th>Título</th>
          <th>Tipo</th>
          <th>Layout</th>
          <th>Ativo</th>
          <th>Componente</th>
          <th>Publicação</th>
          <th style="width:76px;">Ações</th>
        </tr>
      </thead>
      <tbody id="sortable-content-home">
        @forelse ($items as $item)
          <tr data-id="{{ $item->id }}">
            <td>{{ $item->id }}</td>
            <td style="cursor:grab;">⋮⋮ <span data-order-label>{{ $item->display_order }}</span></td>
            <td>{{ $item->title ?? '—' }}</td>
            <td>{{ $item->type }}</td>
            <td>{{ $item->layout }}</td>
            <td>
              @if ($item->is_active)
                <span class="badge badge-success">Ativo</span>
              @else
                <span class="badge badge-muted">Inativo</span>
              @endif
            </td>
            <td>{{ $item->component_name ?? '—' }}</td>
            <td>{{ $item->publish_at ? $item->publish_at->format('d/m/Y H:i') : '—' }}</td>
            <td>
              <details class="action-menu">
                <summary class="btn action-menu-trigger" aria-label="Abrir ações do conteúdo">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <circle cx="8" cy="3" r="1.4" />
                    <circle cx="8" cy="8" r="1.4" />
                    <circle cx="8" cy="13" r="1.4" />
                  </svg>
                </summary>

                <div class="action-menu-panel">
                  <a class="btn action-menu-item" href="{{ route('admin.content-home.edit', $item) }}">Editar</a>
                  <form
                    action="{{ route('admin.content-home.destroy', $item) }}"
                    method="POST"
                    onsubmit="return confirm('Remover este conteúdo?');"
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
            <td colspan="9" style="text-align:center; padding:32px 0; color:#6b7280;">
              Nenhum conteúdo cadastrado ainda.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <script src="{{ asset('vendor/sortablejs/sortable.min.js') }}"></script>
  <script>
    const sortableContentHome = document.getElementById('sortable-content-home');

    if (sortableContentHome && typeof Sortable !== 'undefined') {
      Sortable.create(sortableContentHome, {
        handle: 'td:nth-child(2)',
        animation: 150,
        onEnd: async () => {
          const rows = Array.from(sortableContentHome.querySelectorAll('tr[data-id]'));
          const order = rows.map((row, idx) => ({
            id: row.dataset.id,
            position: idx + 1,
          }));

          rows.forEach((row, idx) => {
            const label = row.querySelector('[data-order-label]');
            if (label) {
              label.textContent = String(idx + 1);
            }
          });

          try {
            const response = await fetch("{{ route('admin.content-home.reorder') }}", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json",
              },
              body: JSON.stringify({ order }),
            });

            if (!response.ok) {
              throw new Error(`Falha ao salvar ordem (${response.status})`);
            }
          } catch (error) {
            console.error('Falha ao reordenar conteúdos da home', error);
            alert('Não foi possível salvar a nova ordem. Atualize a página e tente novamente.');
          }
        },
      });
    } else if (sortableContentHome) {
      console.error('SortableJS indisponível.');
      alert('A funcionalidade de reordenação não está disponível no momento.');
    }
  </script>
@endsection
