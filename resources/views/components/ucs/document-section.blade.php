{{--
  Numbered section of an <x-ucs.document>. The id must match the TOC entry; scroll-mt keeps the heading clear of the sticky site header.
--}}

@props([
  'id',
  'number',
  'title',
])

<section
  id="{{ $id }}"
  aria-labelledby="{{ $id }}-title"
  {{ $attributes->class(['border-line scroll-mt-24 border-t pt-10 first:border-t-0 first:pt-0']) }}
>
  <h2
    id="{{ $id }}-title"
    class="text-ink flex items-start gap-3 text-xl font-semibold tracking-tight text-balance sm:text-2xl"
  >
    <span
      class="border-line text-accent mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md border text-sm font-semibold tabular-nums sm:mt-0"
      aria-hidden="true"
    >
      {{ $number }}
    </span>
    <span class="min-w-0">{{ $title }}</span>
  </h2>

  <div class="text-ink-muted mt-5 space-y-4 text-base leading-7 sm:pl-11">
    {{ $slot }}
  </div>
</section>
