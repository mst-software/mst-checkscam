@props([
  'size' => 'default',
])

@php
  $width = match ($size) {
    'narrow' => 'max-w-3xl',
    'wide' => 'max-w-screen-2xl',
    default => 'max-w-7xl',
  };
@endphp

<div {{ $attributes->class(['mx-auto w-full px-4 sm:px-6 lg:px-8', $width]) }}>
  {{ $slot }}
</div>
