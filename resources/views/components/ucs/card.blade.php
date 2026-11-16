@props([
  'title' => null,
  'subtitle' => null,
  'padding' => true,
])

<div {{ $attributes->class(['border-line bg-surface rounded-card border']) }}>
  @if ($title || isset($header) || isset($actions))
    <div class="border-line flex items-start justify-between gap-3 border-b px-4 py-3 sm:px-5">
      <div class="min-w-0">
        @isset($header)
          {{ $header }}
        @else
          <h3 class="text-ink text-base font-semibold">{{ $title }}</h3>
          @if ($subtitle)
            <p class="text-ink-subtle mt-0.5 text-sm">{{ $subtitle }}</p>
          @endif
        @endisset
      </div>
      @isset($actions)
        <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
      @endisset
    </div>
  @endif

  <div @class(['p-4 sm:p-5' => $padding])>
    {{ $slot }}
  </div>

  @isset($footer)
    <div class="border-line border-t px-4 py-3 sm:px-5">{{ $footer }}</div>
  @endisset
</div>
