{{-- Round partner avatar with an initial as fallback. Expects: $insurance; optional $size ('md' 48px | 'lg' 64px). --}}
@php
  $isLarge = ($size ?? 'md') === 'lg';
  $sizeClass = $isLarge ? 'h-16 w-16 text-xl' : 'h-12 w-12 text-base';
@endphp

@if ($insurance->avatar_url)
  <img
    src="{{ $insurance->avatar_url }}"
    alt="Ảnh đại diện của {{ $insurance->full_name }}"
    width="{{ $isLarge ? 64 : 48 }}"
    height="{{ $isLarge ? 64 : 48 }}"
    loading="lazy"
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
