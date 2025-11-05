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
          <th style="width:170px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($items as $item)
          <tr>
            <td>{{ $item->id }}</td>
            <td>{{ $item->display_order }}</td>
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
              <a class="btn btn-secondary" href="{{ route('admin.content-home.edit', $item) }}">Editar</a>
              <form
                action="{{ route('admin.content-home.destroy', $item) }}"
                method="POST"
                class="inline"
                onsubmit="return confirm('Remover este conteúdo?');"
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
              Nenhum conteúdo cadastrado ainda.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:18px;">
      {{ $items->links() }}
    </div>
  </div>
@endsection
