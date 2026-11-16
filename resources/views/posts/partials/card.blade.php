{{-- One post teaser card (whole card is clickable). Expects: $post. --}}
<article
  class="group border-line bg-surface rounded-card hover:border-line-strong relative flex h-full flex-col overflow-hidden border transition-colors"
>
  <div class="bg-surface-muted aspect-[16/9] overflow-hidden">
    <img src="{{ $post->thumbnail_url }}" alt="" loading="lazy" class="h-full w-full object-cover" />
  </div>
  <div class="flex flex-1 flex-col p-4">
    <div class="text-ink-subtle flex items-center gap-2 text-xs">
      @if ($post->is_featured)
        <x-ucs.badge variant="info">Nổi bật</x-ucs.badge>
      @endif

      <time datetime="{{ $post->created_at->toDateString() }}">{{ $post->created_at->format('d/m/Y') }}</time>
    </div>
    <h3 class="text-ink mt-2 line-clamp-2 text-base leading-snug font-semibold">
      <a
        href="{{ route('posts.frontend.show', $post->slug) }}"
        class="group-hover:text-accent rounded-sm after:absolute after:inset-0 after:content-[''] after:rounded-card focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:outline-offset-2 focus-visible:after:outline-accent"
      >
        {{ $post->title }}
      </a>
    </h3>
    @if ($post->description)
      <p class="text-ink-muted mt-2 line-clamp-2 text-sm leading-relaxed">{{ $post->description }}</p>
    @endif
  </div>
</article>
