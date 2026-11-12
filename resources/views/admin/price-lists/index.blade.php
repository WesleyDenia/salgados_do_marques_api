@extends('admin.layout')

@section('title', 'Listas de preços')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Listas de preços</h2>
        <p class="inline-note">Campanhas valem pela hora de criação da encomenda. Tags só são usadas pelo staff.</p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.price-lists.create') }}">Nova lista</a>
    </div>

    <div class="responsive-table-wrap">
      <table class="responsive-table">
        <thead><tr><th>Nome</th><th>Prioridade</th><th>Vigência</th><th>Tags</th><th>Entradas</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
          @forelse ($priceLists as $priceList)
            <tr>
              <td><span class="stack-table-label">Nome</span>{{ $priceList->name }}{{ $priceList->is_default ? ' (Normal)' : '' }}</td>
              <td><span class="stack-table-label">Prioridade</span>{{ $priceList->priority }}</td>
              <td><span class="stack-table-label">Vigência</span>{{ $priceList->starts_at?->format('d/m/Y H:i') ?? 'Sem início' }} — {{ $priceList->ends_at?->format('d/m/Y H:i') ?? 'Sem fim' }}</td>
              <td><span class="stack-table-label">Tags</span>{{ $priceList->tags->pluck('name')->join(', ') ?: 'Global' }}</td>
              <td><span class="stack-table-label">Entradas</span>{{ $priceList->entries_count }}</td>
              <td><span class="stack-table-label">Status</span>{{ $priceList->active ? 'Ativa' : 'Inativa' }}</td>
              <td><span class="stack-table-label">Ações</span><a class="btn btn-secondary" href="{{ route('admin.price-lists.edit', $priceList) }}">Editar</a></td>
            </tr>
          @empty
            <tr><td colspan="7" style="text-align:center; padding:32px; color:#6b7280;">Nenhuma lista de preços cadastrada.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
