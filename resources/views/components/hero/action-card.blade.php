@props([
  'href',
  'icon',
  'label',
  'description' => null,
  'featured' => false,
])

{{-- Shortcut card. `featured` is the primary call to action (solid brand color); the others are quiet white cards. --}}
<a
  href="{{ $href }}"
  {{
    $attributes->class([
      'group relative flex h-full flex-col gap-4 overflow-hidden rounded-2xl border p-4 text-left sm:p-5',
      'focus-visible:outline-2 focus-visible:outline-offset-2',
      'motion-safe:transition-[translate,box-shadow,border-color,background-color] motion-safe:duration-200 motion-safe:hover:-translate-y-1',
      'border-accent bg-accent focus-visible:outline-accent text-white shadow-lg shadow-accent/25 hover:bg-accent-hover' => $featured,
      'border-line bg-surface hover:border-accent/40 focus-visible:outline-accent shadow-xs hover:shadow-lg hover:shadow-accent/10' => ! $featured,
    ])
  }}
>
  <div class="flex items-start justify-between">
    <span
      @class([
        'flex size-11 shrink-0 items-center justify-center rounded-xl text-lg sm:size-12',
        'bg-white/15 text-white ring-1 ring-white/25' => $featured,
        'bg-accent-soft text-accent' => ! $featured,
      ])
      aria-hidden="true"
    >
      <i class="{{ $icon }}"></i>
    </span>

    <span
      @class([
        'flex size-8 items-center justify-center rounded-full text-xs motion-safe:transition-transform motion-safe:group-hover:translate-x-0.5',
        'bg-white/15 text-white' => $featured,
        'bg-surface-muted text-ink-subtle group-hover:bg-accent group-hover:text-on-accent' => ! $featured,
      ])
      aria-hidden="true"
    >
      <i class="fa-solid fa-arrow-right"></i>
    </span>
  </div>

  <div class="min-w-0">
    <span @class(['block text-base leading-snug font-bold sm:text-[17px]', 'text-ink' => ! $featured])>
      {{ $label }}
    </span>
    @if ($description)
      <span
        @class([
          'mt-1 block text-[13px] leading-relaxed sm:text-sm',
          'text-white/85' => $featured,
          'text-ink-muted' => ! $featured,
        ])
      >
        {{ $description }}
      </span>
    @endif
  </div>
</a>
