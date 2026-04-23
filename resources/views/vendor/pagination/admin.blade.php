@if ($paginator->hasPages())
  <nav class="pagination" role="navigation" aria-label="Paginação">
    <div class="pagination-summary">
      Mostrando {{ $paginator->firstItem() }} a {{ $paginator->lastItem() }} de {{ $paginator->total() }} resultados
    </div>

    <div class="pagination-links">
      @if ($paginator->onFirstPage())
        <span class="pagination-link disabled">Anterior</span>
      @else
        <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
      @endif

      @foreach ($elements as $element)
        @if (is_string($element))
          <span class="pagination-link disabled">{{ $element }}</span>
        @endif

        @if (is_array($element))
          @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
              <span class="pagination-link active">{{ $page }}</span>
            @else
              <a class="pagination-link" href="{{ $url }}">{{ $page }}</a>
            @endif
          @endforeach
        @endif
      @endforeach

      @if ($paginator->hasMorePages())
        <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Próxima</a>
      @else
        <span class="pagination-link disabled">Próxima</span>
      @endif
    </div>
  </nav>
@endif
