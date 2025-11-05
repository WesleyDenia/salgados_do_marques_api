@extends('admin.layout')

@section('title', 'Produtos')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Produtos</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Cadastre os itens do cardápio exibidos no aplicativo.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.products.create') }}">Novo produto</a>
    </div>

    <table>
      <thead>
        <tr>
          <th>Produto</th>
          <th>Categoria</th>
          <th>Preço</th>
          <th>Status</th>
          <th style="width:170px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($products as $product)
          <tr>
            <td>
              <strong>{{ $product->name }}</strong>
              @if ($product->description)
                <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">
                  {{ \Illuminate\Support\Str::limit($product->description, 120) }}
                </div>
              @endif
            </td>
            <td>{{ $product->category?->name ?? '—' }}</td>
            <td>
              {{ $product->price !== null ? '€ ' . number_format($product->price, 2, ',', '.') : '—' }}
            </td>
            <td>
              @if ($product->active)
                <span class="badge badge-success">Ativo</span>
              @else
                <span class="badge badge-muted">Inativo</span>
              @endif
            </td>
            <td>
              <a class="btn btn-secondary" href="{{ route('admin.products.edit', $product) }}">Editar</a>
              <form
                action="{{ route('admin.products.destroy', $product) }}"
                method="POST"
                class="inline"
                onsubmit="return confirm('Remover este produto?');"
              >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center; padding:32px 0; color:#6b7280;">
              Nenhum produto cadastrado.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:18px;">
      {{ $products->links() }}
    </div>
  </div>
@endsection
