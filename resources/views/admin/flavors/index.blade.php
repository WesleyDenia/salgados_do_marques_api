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

    <table>
      <thead>
        <tr>
          <th style="width:90px;">Ordem</th>
          <th>Nome</th>
          <th>Status</th>
          <th style="width:170px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($flavors as $flavor)
          <tr>
            <td>{{ $flavor->display_order }}</td>
            <td><strong>{{ $flavor->name }}</strong></td>
            <td>
              @if ($flavor->active)
                <span class="badge badge-success">Ativo</span>
              @else
                <span class="badge badge-muted">Inativo</span>
              @endif
            </td>
            <td>
              <a class="btn btn-secondary" href="{{ route('admin.flavors.edit', $flavor) }}">Editar</a>
              <form
                action="{{ route('admin.flavors.destroy', $flavor) }}"
                method="POST"
                class="inline"
                onsubmit="return confirm('Remover este sabor?');"
              >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
              </form>
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
@endsection
