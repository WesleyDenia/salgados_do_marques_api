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

    <div class="responsive-table-wrap">
      <table class="responsive-table">
        <thead>
          <tr>
            <th style="width:84px;">Imagem</th>
            <th>Produto</th>
            <th>Categoria</th>
            <th>Preço</th>
            <th>Status</th>
            <th style="width:76px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($products as $product)
            <tr>
              <td>
                <span class="stack-table-label">Imagem</span>
                @if ($product->image_url)
                  <img
                    src="{{ $product->image_url }}"
                    alt="Imagem de {{ $product->name }}"
                    style="width:56px; height:56px; object-fit:cover; border-radius:12px; border:1px solid #e5e7eb; background:#f8fafc;"
                  />
                @else
                  <div
                    aria-label="Produto sem imagem"
                    title="Produto sem imagem"
                    style="width:56px; height:56px; border-radius:12px; border:1px dashed #cbd5e1; background:linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); color:#64748b; display:flex; align-items:center; justify-content:center; font-size:0.72rem; font-weight:700; text-align:center; line-height:1.1; padding:6px;"
                  >
                    Sem imagem
                  </div>
                @endif
              </td>
              <td>
                <span class="stack-table-label">Produto</span>
                <strong>{{ $product->name }}</strong>
                @if ($product->description)
                  <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">
                    {{ \Illuminate\Support\Str::limit($product->description, 120) }}
                  </div>
                @endif
              </td>
              <td>
                <span class="stack-table-label">Categoria</span>
                {{ $product->category?->name ?? '—' }}
              </td>
              <td>
                <span class="stack-table-label">Preço</span>
                {{ $product->price !== null ? '€ ' . number_format($product->price, 2, ',', '.') : '—' }}
              </td>
              <td>
                <span class="stack-table-label">Status</span>
                @if ($product->active)
                  <span class="badge badge-success">Ativo</span>
                @else
                  <span class="badge badge-muted">Inativo</span>
                @endif
              </td>
              <td>
                <span class="stack-table-label">Ações</span>
                <details class="action-menu">
                  <summary class="btn action-menu-trigger" aria-label="Abrir ações do produto">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                      <circle cx="8" cy="3" r="1.4" />
                      <circle cx="8" cy="8" r="1.4" />
                      <circle cx="8" cy="13" r="1.4" />
                    </svg>
                  </summary>

                  <div class="action-menu-panel">
                    <a class="btn action-menu-item" href="{{ route('admin.products.edit', $product) }}">Editar</a>
                    <form
                      action="{{ route('admin.products.destroy', $product) }}"
                      method="POST"
                      onsubmit="return confirm('Remover este produto?');"
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
              <td colspan="6" style="text-align:center; padding:32px 0; color:#6b7280;">
                Nenhum produto cadastrado.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:18px;">
      {{ $products->links() }}
    </div>
  </div>
@endsection
