{{--
  Brand lockup: shield mark (SVG) + wordmark as real HTML text so it uses the page
  font and stays crisp at any size. `dark` is for always-dark surfaces such as the footer.
--}}

@props([
  'size' => 'md',
  'dark' => false,
])
@php
  $mark = match ($size) {
    'sm' => 'h-7',
    'lg' => 'h-12',
    default => 'h-9',
  };
  $word = match ($size) {
    'sm' => 'text-lg',
    'lg' => 'text-3xl',
    default => 'text-2xl',
  };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2']) }}>
  <img src="{{ asset('img/logo-mark.svg') }}" alt="" width="64" height="72" class="{{ $mark }} w-auto shrink-0" />
  {{-- Flex container: whitespace between the two halves is ignored, so formatters cannot insert a gap. --}}
  <span class="{{ $word }} inline-flex font-extrabold tracking-tight">
    <span class="{{ $dark ? 'text-white' : 'text-ink' }}">Scam</span>
    <span class="{{ $dark ? 'text-red-400' : 'text-accent' }}">proof</span>
  </span>
</span>
