@php
  $itemClasses =
    'rounded-control inline-flex h-9 min-w-9 items-center justify-center border px-3 text-sm font-medium tabular-nums';
  $linkClasses =
    $itemClasses .
    ' border-line bg-surface text-ink hover:bg-surface-muted focus-visible:outline-accent transition-colors focus-visible:outline-2 focus-visible:outline-offset-2';
  $disabledClasses = $itemClasses . ' border-line bg-surface text-ink-subtle cursor-not-allowed opacity-60';
@endphp

<nav aria-label="Phân trang" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
  <p class="text-ink-subtle text-sm">
    Trang
    <span class="text-ink font-medium tabular-nums">{{ $paginator->currentPage() }}</span>
    /
    <span class="tabular-nums">{{ $paginator->lastPage() }}</span>
  </p>

  <ul class="flex flex-wrap items-center justify-center gap-1">
    <li>
      @if ($paginator->onFirstPage())
        <span class="{{ $disabledClasses }}" aria-disabled="true">
          <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
          <span class="sr-only">Trang trước</span>
        </span>
      @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $linkClasses }}">
          <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
          <span class="sr-only">Trang trước</span>
        </a>
      @endif
    </li>

    @foreach ($elements as $element)
      @if (is_string($element))
        <li><span class="{{ $disabledClasses }} border-transparent" aria-hidden="true">{{ $element }}</span></li>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          <li>
            @if ($page == $paginator->currentPage())
              <span class="{{ $itemClasses }} border-accent bg-accent text-on-accent" aria-current="page">
                {{ $page }}
              </span>
            @else
              <a href="{{ $url }}" class="{{ $linkClasses }}" aria-label="Trang {{ $page }}">{{ $page }}</a>
            @endif
          </li>
        @endforeach
      @endif
    @endforeach

    <li>
      @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $linkClasses }}">
          <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
          <span class="sr-only">Trang sau</span>
        </a>
      @else
        <span class="{{ $disabledClasses }}" aria-disabled="true">
          <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
          <span class="sr-only">Trang sau</span>
        </span>
      @endif
    </li>
  </ul>
</nav>
