{{-- Most-read posts sidebar. Expects: $popularPosts. --}}
@if ($popularPosts->isNotEmpty())
  <x-ucs.card title="Đọc nhiều nhất" :padding="false">
    <ol class="divide-line divide-y">
      @foreach ($popularPosts as $index => $popular)
        <li>
          <a
            href="{{ route('posts.frontend.show', $popular->slug) }}"
            class="hover:bg-surface-muted focus-visible:outline-accent flex items-start gap-3 px-4 py-3 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 sm:px-5"
          >
            <span class="text-ink-subtle w-5 shrink-0 text-sm font-semibold tabular-nums">{{ $index + 1 }}</span>
            <span class="min-w-0">
              <span class="text-ink line-clamp-2 text-sm leading-snug font-medium">{{ $popular->title }}</span>
              <span class="text-ink-subtle mt-1 block text-xs">
                {{ number_format($popular->view_count, 0, ',', '.') }} lượt xem
              </span>
            </span>
          </a>
        </li>
      @endforeach
    </ol>
  </x-ucs.card>
@endif
