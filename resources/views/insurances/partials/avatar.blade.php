{{-- Round partner avatar with an initial as fallback. Expects: $insurance; optional $size ('md' 48px | 'lg' 64px | 'xl' 96px). --}}
@php
  [$sizeClass, $pixels] = match ($size ?? 'md') {
    'xl' => ['h-20 w-20 text-3xl sm:h-24 sm:w-24', 96],
    'lg' => ['h-16 w-16 text-xl', 64],
    default => ['h-12 w-12 text-base', 48],
  };
@endphp

@if ($insurance->avatar_url)
  <img
    src="{{ $insurance->avatar_url }}"
    alt="Ảnh đại diện của {{ $insurance->full_name }}"
    width="{{ $pixels }}"
    height="{{ $pixels }}"
    @if (($size ?? 'md') !== 'xl') loading="lazy" @endif
    class="{{ $sizeClass }} border-line bg-surface-muted shrink-0 rounded-full border object-cover"
  />
@else
  <span
    class="{{ $sizeClass }} bg-surface-muted text-ink-muted flex shrink-0 items-center justify-center rounded-full font-semibold"
    aria-hidden="true"
  >
    {{ mb_strtoupper(mb_substr($insurance->full_name, 0, 1)) }}
  </span>
@endif
