@props([
  'title',
  'subtitle' => null,
  'actionLabel' => null,
  'actionUrl' => null,
  'as' => 'h2',
])

<div {{ $attributes->class(['mb-4 flex items-end justify-between gap-4']) }}>
  <div class="min-w-0">
    <{{ $as }} class="text-ink text-lg font-semibold sm:text-xl">{{ $title }}</{{ $as }}>
    @if ($subtitle)
      <p class="text-ink-subtle mt-1 text-sm">{{ $subtitle }}</p>
    @endif
  </div>

  @if ($actionLabel && $actionUrl)
    <a
      href="{{ $actionUrl }}"
      class="text-accent hover:text-accent-hover inline-flex shrink-0 items-center gap-1 text-sm font-medium"
    >
      {{ $actionLabel }}
      <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
    </a>
  @endif
</div>
