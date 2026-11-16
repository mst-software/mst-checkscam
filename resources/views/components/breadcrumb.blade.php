@props([
  'links' => [],
  'onHero' => false,
])

@php
  $linkClass = $onHero
    ? 'text-on-hero-muted rounded-sm transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-white'
    : 'hover:text-ink transition-colors';
@endphp

<div @class(['border-line bg-surface mb-10 border-b py-3' => ! $onHero])>
  <div @class(['mx-auto max-w-7xl px-4 sm:px-6 lg:px-8' => ! $onHero])>
    <nav
      @class(['flex items-center text-xs font-medium', 'text-ink-subtle' => ! $onHero, 'text-on-hero-muted' => $onHero])
      aria-label="Breadcrumb"
    >
      <ol @class(['inline-flex min-w-0 items-center', 'gap-2' => ! $onHero, 'flex-wrap gap-x-2 gap-y-1' => $onHero])>
        <li><a href="{{ route('home') }}" class="{{ $linkClass }}">Trang chủ</a></li>
        <li aria-hidden="true"><i class="fa-solid fa-chevron-right text-xs opacity-50"></i></li>
        @for ($i = 0; $i < count($links) - 1; $i++)
          <li>
            <a href="{{ $links[$i]['url'] }}" class="{{ $linkClass }}">
              {{ $links[$i]['name'] }}
            </a>
          </li>
          <li aria-hidden="true"><i class="fa-solid fa-chevron-right text-xs opacity-50"></i></li>
        @endfor

        <li
          @class(['max-w-[180px] truncate', 'text-ink md:max-w-none' => ! $onHero, 'text-white md:max-w-md' => $onHero])
          aria-current="page"
        >
          {{ $links[count($links) - 1]['name'] }}
        </li>
      </ol>
    </nav>
  </div>
</div>
