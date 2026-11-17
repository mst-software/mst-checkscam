{{--
  Brand logo image (shield + wordmark). The light logo has a white background and the dark one #0b1220
  (the footer surface); `mix-blend-lighten` hides the small colour gap on the dark header surface.
  `dark` is for always-dark surfaces such as the footer.
--}}

@props([
  'size' => 'md',
  'dark' => false,
])
@php
  $height = match ($size) {
    'sm' => 'h-10',
    'lg' => 'h-16',
    default => 'h-11 sm:h-14',
  };
@endphp

<span {{ $attributes->class(['inline-flex items-center']) }}>
  @unless ($dark)
    <img
      src="{{ asset('img/scamproof-logo-tight.png') }}"
      alt="Scamproof"
      width="651"
      height="159"
      class="{{ $height }} w-auto dark:hidden"
    />
  @endunless

  <img
    src="{{ asset('img/scamproof-logo-dark-tight.png') }}"
    alt="Scamproof"
    width="651"
    height="159"
    class="{{ $height }} {{ $dark ? '' : 'hidden dark:block' }} w-auto mix-blend-lighten"
  />
</span>
