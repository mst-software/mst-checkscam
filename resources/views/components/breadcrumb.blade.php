@props([
  'links' => [],
])
<div class="border-line bg-surface mb-10 border-b py-3">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <nav class="text-ink-subtle flex items-center text-xs font-medium" aria-label="Breadcrumb">
      <ol class="inline-flex min-w-0 items-center gap-2">
        <li><a href="{{ route('home') }}" class="hover:text-ink transition-colors">Trang chủ</a></li>
        <li aria-hidden="true"><i class="fa-solid fa-chevron-right text-xs opacity-50"></i></li>
        @for ($i = 0; $i < count($links) - 1; $i++)
          <li>
            <a href="{{ $links[$i]['url'] }}" class="hover:text-ink transition-colors">
              {{ $links[$i]['name'] }}
            </a>
          </li>
          <li aria-hidden="true"><i class="fa-solid fa-chevron-right text-xs opacity-50"></i></li>
        @endfor

        <li class="text-ink max-w-[180px] truncate md:max-w-none" aria-current="page">
          {{ $links[count($links) - 1]['name'] }}
        </li>
      </ol>
    </nav>
  </div>
</div>
