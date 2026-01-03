@extends('admin.layout')

@section('title', 'Categorias')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Categorias</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Organize os produtos do cardápio agrupando-os em categorias.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">Nova categoria</a>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:80px;">Ordem</th>
          <th>Nome</th>
          <th>ID externo</th>
          <th>Produtos</th>
          <th>Status</th>
          <th style="width:170px;">Ações</th>
        </tr>
      </thead>
      <tbody id="sortable-categories">
        @forelse ($categories as $category)
          <tr data-id="{{ $category->id }}">
            <td style="cursor:grab;">⋮⋮ {{ $category->display_order }}</td>
            <td>
              <strong>{{ $category->name }}</strong>
              @if ($category->description)
                <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">
                  {{ \Illuminate\Support\Str::limit($category->description, 120) }}
                </div>
              @endif
            </td>
            <td>{{ $category->external_id ?? '—' }}</td>
            <td>{{ $category->products_count }}</td>
            <td>
              @if ($category->active)
                <span class="badge badge-success">Ativa</span>
              @else
                <span class="badge badge-muted">Inativa</span>
              @endif
            </td>
            <td>
              <a class="btn btn-secondary" href="{{ route('admin.categories.edit', $category) }}">Editar</a>
              <form
                action="{{ route('admin.categories.destroy', $category) }}"
                method="POST"
                class="inline"
                onsubmit="return confirm('Remover esta categoria?');"
              >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align:center; padding:32px 0; color:#6b7280;">
              Nenhuma categoria cadastrada.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <script>
    const el = document.getElementById('sortable-categories');
    if (el) {
      Sortable.create(el, {
        handle: 'td:first-child',
        animation: 150,
        onEnd: async () => {
          const order = Array.from(el.querySelectorAll('tr[data-id]')).map((row, idx) => ({
            id: row.dataset.id,
            position: idx + 1,
          }));

          try {
            await fetch("{{ route('admin.categories.reorder') }}", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json",
              },
              body: JSON.stringify({ order }),
            });
          } catch (error) {
            console.error('Falha ao reordenar categorias', error);
            alert('Não foi possível salvar a nova ordem. Tente novamente.');
          }
        },
      });
    }
  </script>
@endsection
