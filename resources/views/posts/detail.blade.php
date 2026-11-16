@extends('layouts.app')

@php
  $wordCount = count(preg_split('/\s+/u', trim(strip_tags((string) $post->content)), -1, PREG_SPLIT_NO_EMPTY));
  $readMinutes = max(1, (int) ceil($wordCount / 220));
  $authorName = $post->author->name ?? 'Admin';
  $tags = collect(explode(',', (string) $post->hashtags))
    ->map(fn ($tag) => trim($tag))
    ->filter();
  $shareUrl = route('posts.frontend.show', $post->slug);
@endphp

@section('content')
  <div id="reading-progress" class="bg-accent fixed top-0 left-0 z-50 h-0.5 w-0" role="presentation"></div>

  <main class="flex-1 pb-20">
    <x-ucs.page-header
      :title="$post->title"
      :description="$post->description"
      :breadcrumbs="[
        ['name' => 'Kiến thức MMO', 'url' => route('posts.frontend.index')],
        ['name' => $post->title, 'url' => $shareUrl],
      ]"
    />

    <x-ucs.container>
      <div class="grid grid-cols-1 gap-10 lg:grid-cols-[minmax(0,1fr)_320px]">
        <article class="min-w-0">
          <div class="mx-auto max-w-3xl">
            <div class="text-ink-subtle flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
              <span class="text-ink flex items-center gap-2 font-medium">
                @if ($post->author?->avatar_url)
                  <img
                    src="{{ $post->author->avatar_url }}"
                    alt=""
                    width="32"
                    height="32"
                    class="border-line h-8 w-8 rounded-full border object-cover"
                  />
                @else
                  <span
                    class="bg-surface-muted text-ink-muted flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold"
                    aria-hidden="true"
                  >
                    {{ mb_strtoupper(mb_substr($authorName, 0, 1)) }}
                  </span>
                @endif
                {{ $authorName }}
              </span>
              <time datetime="{{ $post->created_at->toIso8601String() }}">
                {{ $post->created_at->format('d/m/Y') }}
              </time>
              <span>{{ $readMinutes }} phút đọc</span>
              <span>{{ number_format($post->view_count, 0, ',', '.') }} lượt xem</span>
            </div>

            @if ($post->thumbnail_url)
              <figure class="border-line bg-surface-muted rounded-card mt-6 overflow-hidden border">
                <img
                  src="{{ $post->thumbnail_url }}"
                  alt="Ảnh bìa: {{ $post->title }}"
                  class="aspect-[16/9] w-full object-cover"
                />
              </figure>
            @endif

            @include('posts.partials.content', ['content' => $post->content])

            <div
              class="border-line mt-10 flex flex-col gap-4 border-t pt-6 sm:flex-row sm:items-center sm:justify-between"
            >
              @if ($tags->isNotEmpty())
                <ul class="flex flex-wrap gap-2" aria-label="Thẻ bài viết">
                  @foreach ($tags as $tag)
                    <li><x-ucs.badge>#{{ $tag }}</x-ucs.badge></li>
                  @endforeach
                </ul>
              @else
                <span></span>
              @endif

              <div class="flex flex-wrap items-center gap-2">
                <x-ucs.button variant="secondary" size="sm" icon="fa-solid fa-link" data-copy="{{ $shareUrl }}">
                  <span data-copy-label aria-live="polite">Sao chép liên kết</span>
                </x-ucs.button>
                <x-ucs.button
                  variant="secondary"
                  size="sm"
                  icon="fa-brands fa-facebook-f"
                  href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  Facebook
                </x-ucs.button>
                <x-ucs.button
                  variant="secondary"
                  size="sm"
                  icon="fa-brands fa-telegram"
                  href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ urlencode($post->title) }}"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  Telegram
                </x-ucs.button>
              </div>
            </div>
          </div>

          @if ($relatedPosts->isNotEmpty())
            <section class="mt-14" aria-label="Bài viết liên quan">
              <x-ucs.section-title
                title="Bài viết liên quan"
                action-label="Xem tất cả"
                :action-url="route('posts.frontend.index')"
              />
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($relatedPosts as $related)
                  @include('posts.partials.card', ['post' => $related])
                @endforeach
              </div>
            </section>
          @endif
        </article>

        <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
          <x-banner-ads position="blog" />
          @include('posts.partials.popular', ['popularPosts' => $popularPosts])
        </aside>
      </div>
    </x-ucs.container>
  </main>

  @include('partials.copy-to-clipboard')

  @push('scripts')
    <script>
      (function () {
        const bar = document.getElementById('reading-progress');
        if (!bar) {
          return;
        }
        const update = function () {
          const height = document.documentElement.scrollHeight - window.innerHeight;
          bar.style.width = (height > 0 ? (window.scrollY / height) * 100 : 0) + '%';
        };
        window.addEventListener('scroll', update, { passive: true });
        update();
      })();
    </script>
  @endpush
@endsection
