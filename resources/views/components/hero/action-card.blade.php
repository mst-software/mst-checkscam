@props([
  'href',
  'icon',
  'label',
  'sub' => null,
])

<a
  href="{{ $href }}"
  {{ $attributes->class(['group border-line bg-surface hover:border-accent/40 focus-visible:outline-accent relative flex h-full flex-col items-start gap-3 rounded-2xl border p-3.5 text-left shadow-xs transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 motion-safe:transition-[color,background-color,border-color,box-shadow,translate] motion-safe:hover:-translate-y-0.5 motion-safe:hover:shadow-md sm:flex-row sm:items-center sm:gap-3.5 md:p-4']) }}
>
  <span
    class="bg-accent-soft text-accent flex size-10 shrink-0 items-center justify-center rounded-xl text-base md:size-11 md:text-lg"
    aria-hidden="true"
  >
    <i class="{{ $icon }}"></i>
  </span>

  <span class="w-full min-w-0 sm:w-auto sm:flex-1">
    @if ($sub)
      <span class="text-ink-subtle block truncate text-xs font-bold tracking-[0.12em] uppercase">{{ $sub }}</span>
    @endif

    <span
      class="text-ink group-hover:text-accent block text-[13px] leading-snug font-extrabold uppercase transition-colors sm:text-sm md:text-[15px]"
    >
      {{ $label }}
    </span>
  </span>

  <i
    class="fa-solid fa-chevron-right text-line-strong group-hover:text-accent absolute top-4 right-4 shrink-0 text-xs transition-colors sm:static"
    aria-hidden="true"
  ></i>
</a>
