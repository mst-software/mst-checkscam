@props([
  'variant' => 'neutral',
  'icon' => null,
])

@php
  $variantClasses = match ($variant) {
    'danger' => 'border-danger/25 bg-danger-soft text-danger',
    'success' => 'border-success/25 bg-success-soft text-success',
    'info' => 'border-accent/25 bg-accent-soft text-accent',
    default => 'border-line bg-surface-muted text-ink-muted',
  };
@endphp

<span
  {{ $attributes->class(['inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium whitespace-nowrap', $variantClasses]) }}
>
  @if ($icon)
    <i class="{{ $icon }} text-xs" aria-hidden="true"></i>
  @endif

  {{ $slot }}
</span>
