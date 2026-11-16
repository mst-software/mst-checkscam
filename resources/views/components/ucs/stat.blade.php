@props([
  'label',
  'value',
  'hint' => null,
  'tone' => 'neutral',
])

@php
  $valueColor = match ($tone) {
    'danger' => 'text-danger',
    'success' => 'text-success',
    'accent' => 'text-accent',
    default => 'text-ink',
  };
@endphp

<div {{ $attributes->class(['border-line bg-surface rounded-card border p-4']) }}>
  <p class="text-ink-subtle text-xs font-medium">{{ $label }}</p>
  <p class="{{ $valueColor }} mt-1 text-2xl font-semibold tabular-nums">{{ $value }}</p>
  @if ($hint)
    <p class="text-ink-subtle mt-1 text-xs">{{ $hint }}</p>
  @endif
</div>
