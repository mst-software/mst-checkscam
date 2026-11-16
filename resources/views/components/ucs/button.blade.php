@props([
  'variant' => 'primary',
  'size' => 'md',
  'href' => null,
  'type' => 'button',
  'icon' => null,
])

@php
  $variantClasses = match ($variant) {
    'secondary' => 'border-line bg-surface text-ink hover:bg-surface-muted',
    'ghost' => 'border-transparent bg-transparent text-ink-muted hover:bg-surface-muted hover:text-ink',
    'danger' => 'border-transparent bg-danger text-white hover:opacity-90',
    default => 'border-transparent bg-accent text-on-accent hover:bg-accent-hover',
  };

  $sizeClasses = match ($size) {
    'sm' => 'h-8 px-3 text-xs',
    'lg' => 'h-11 px-5 text-base',
    default => 'h-10 px-4 text-sm',
  };

  $classes = "rounded-control inline-flex cursor-pointer items-center justify-center gap-2 border font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:opacity-50 {$variantClasses} {$sizeClasses}";
@endphp

@if ($href)
  <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
    @if ($icon)
      <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif

    {{ $slot }}
  </a>
@else
  <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>
    @if ($icon)
      <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif

    {{ $slot }}
  </button>
@endif
