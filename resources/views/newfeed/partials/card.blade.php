{{--
    One marketplace post card. Expects: $post, $userReportedIds.
    Keep in sync with renderCard() in newfeed/partials/index-scripts (cards loaded by the JSON API).
--}}
@php
  $imageUrls = [];
  if (! empty($post->image_paths)) {
      $imageUrls = array_map(fn ($p) => asset("storage/" . $p), $post->image_paths);
  } elseif ($post->image_path) {
      $imageUrls = [asset("storage/" . $post->image_path)];
  }
  $imageCount = count($imageUrls);
  $showCount = min($imageCount, 4);
  $extraCount = $imageCount - $showCount;

  $truncate = mb_strlen($post->content) > 200;
  $displayContent = \App\Helpers\StringHelper::formatPostContent($truncate ? mb_substr($post->content, 0, 200) : $post->content);
  $authorName = $post->user->full_name ?? $post->user->username;
  $postUrl = route("newfeed.show", $post->id);
@endphp

<article
  class="nf-card border-line bg-surface rounded-card hover:border-line-strong relative mb-4 cursor-pointer break-inside-avoid border p-4 transition-colors"
  data-id="{{ $post->id }}"
  data-post-url="{{ $postUrl }}"
>
  @include('newfeed.partials.card-action', ['post' => $post, 'userReportedIds' => $userReportedIds])

  <div class="flex items-center gap-3 pr-10">
    <img src="{{ $post->user->avatar_url }}" alt="" width="36" height="36" class="h-9 w-9 rounded-full object-cover" />
    <div class="min-w-0">
      <p class="text-ink flex items-center gap-1 text-sm font-medium">
        <span class="truncate">{{ $authorName }}</span>
        @if ($post->user->is_verified)
          <i
            class="fa-solid fa-circle-check text-success text-xs"
            title="Tài khoản đã được Scamproof xác minh uy tín"
            aria-hidden="true"
          ></i>
          <span class="sr-only">(đã xác minh)</span>
        @endif
      </p>
      <p class="text-ink-subtle text-xs">
        <a
          href="{{ $postUrl }}"
          class="hover:text-ink rounded-sm hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          {{ $post->created_at->diffForHumans() }}
        </a>
        · {{ $post->category }}
      </p>
    </div>
  </div>

  <div class="text-ink mt-3 text-sm leading-relaxed break-words">
    {!! $displayContent !!}@if ($truncate)<span class="text-ink-subtle">...</span>
    @endif
  </div>

  @if ($post->price && $post->price > 0)
    <p class="text-ink mt-2 text-sm font-semibold tabular-nums">{{ number_format($post->price, 0, ",", ".") }} đ</p>
  @endif

  @if ($imageCount > 0)
    <div @class(['mt-3 grid gap-1', 'grid-cols-1' => $showCount === 1, 'grid-cols-2' => $showCount > 1])>
      @foreach (array_slice($imageUrls, 0, $showCount) as $idx => $url)
        <button
          type="button"
          @class([
            'bg-surface-muted relative cursor-pointer overflow-hidden rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
            'col-span-2' => $showCount === 3 && $idx === 0,
            'max-h-72' => $showCount === 1,
            'h-36' => $showCount > 1,
          ])
          onclick="
            event.stopPropagation();
            openCardGallery({{ Js::from($imageUrls) }}, {{ $idx }});
          "
          aria-label="Xem ảnh {{ $idx + 1 }}/{{ $imageCount }}"
        >
          <img src="{{ $url }}" alt="" class="h-full w-full object-cover" loading="lazy" />
          @if ($idx === $showCount - 1 && $extraCount > 0)
            <span
              class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/50 text-xl font-semibold text-white"
            >
              +{{ $extraCount }}
            </span>
          @endif
        </button>
      @endforeach
    </div>
  @endif
</article>
