@props([
  'href',
  'icon',
  'label',
  'sub' => null,
])

<a
  href="{{ $href }}"
  {{ $attributes->class(['group border-line bg-surface hover:border-accent/40 hover:bg-accent-soft/40 focus-visible:outline-accent flex h-full items-center gap-2.5 rounded-2xl border p-2.5 shadow-xs sm:gap-3 sm:p-3 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 md:gap-4 md:p-4']) }}
>
  <span
    class="bg-accent-soft text-accent flex size-9 shrink-0 sm:size-10 items-center justify-center rounded-xl md:size-12 md:text-xl"
    aria-hidden="true"
  >
    <i class="{{ $icon }}"></i>
  </span>

  <span class="min-w-0 text-left">
    @if ($sub)
      <span class="text-ink-subtle block truncate text-xs font-bold uppercase">{{ $sub }}</span>
    @endif

    <span class="text-ink group-hover:text-accent block text-xs font-black uppercase transition-colors md:text-sm">
      {{ $label }}
    </span>
  </span>
</a>
