{{-- Comment cards for the home feed; also returned alone for the "load more" AJAX request. --}}
@use('App\Helpers\StringHelper')

@foreach ($comments as $comment)
  <article data-comment-item class="border-line bg-surface rounded-card flex flex-col border p-4">
    <header class="flex items-center gap-3">
      <span
        class="bg-surface-muted text-ink-muted flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
        aria-hidden="true"
      >
        {{ mb_strtoupper(mb_substr($comment->display_name, 0, 1)) }}
      </span>
      <div class="min-w-0">
        <p class="text-ink truncate text-sm font-medium">{{ $comment->display_name }}</p>
        <p class="text-ink-subtle text-xs">
          <time datetime="{{ $comment->created_at->toIso8601String() }}">
            {{ $comment->created_at->locale('vi')->diffForHumans() }}
          </time>
        </p>
      </div>
    </header>

    <p class="text-ink-muted mt-3 line-clamp-3 flex-1 text-sm leading-relaxed">{{ $comment->content }}</p>

    <footer class="border-line mt-3 flex items-center justify-between gap-2 border-t pt-3 text-xs">
      <span class="text-ink-subtle shrink-0">Đối tượng</span>
      @if ($comment->report)
        <a
          href="{{ route('scammer.show', $comment->report->slug) }}"
          class="text-ink hover:text-accent focus-visible:outline-accent truncate rounded-sm font-medium tabular-nums focus-visible:outline-2"
        >
          {{ StringHelper::mask_id($comment->report->target_id, $comment->report->type) }}
        </a>
      @else
        <span class="text-ink-subtle">Không còn tồn tại</span>
      @endif
    </footer>
  </article>
@endforeach
