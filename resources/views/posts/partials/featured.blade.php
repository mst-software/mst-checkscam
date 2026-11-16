{{-- Featured post shown above the post grid. Expects: $post. --}}
<article
  class="group border-line bg-surface rounded-card hover:border-line-strong relative mb-8 grid grid-cols-1 overflow-hidden border transition-colors md:grid-cols-2"
>
  <div class="bg-surface-muted aspect-[16/9] overflow-hidden md:aspect-auto md:h-full">
    <img src="{{ $post->thumbnail_url }}" alt="" class="h-full w-full object-cover" />
  </div>
  <div class="flex flex-col justify-center p-5 sm:p-6">
    <div class="text-ink-subtle flex items-center gap-2 text-xs">
      <x-ucs.badge variant="info" icon="fa-solid fa-star">Nổi bật</x-ucs.badge>
      <time datetime="{{ $post->created_at->toDateString() }}">{{ $post->created_at->format('d/m/Y') }}</time>
    </div>
    <h2 class="text-ink mt-3 text-xl leading-snug font-semibold text-balance sm:text-2xl">
      <a
        href="{{ route('posts.frontend.show', $post->slug) }}"
        class="group-hover:text-accent after:rounded-card after:absolute after:inset-0 after:content-[''] focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:outline-offset-2 focus-visible:after:outline-accent"
      >
        {{ $post->title }}
      </a>
    </h2>
    @if ($post->description)
      <p class="text-ink-muted mt-3 line-clamp-3 text-sm leading-relaxed sm:text-base">{{ $post->description }}</p>
    @endif

    <span class="text-accent mt-4 inline-flex items-center gap-1 text-sm font-medium" aria-hidden="true">
      Đọc bài viết
      <i class="fa-solid fa-arrow-right text-xs"></i>
    </span>
  </div>
</article>
