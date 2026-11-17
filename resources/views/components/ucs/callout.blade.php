{{--
  Highlighted note inside long-form content (rules, warnings, rights).
  Usage: <x-ucs.callout variant="warning" icon="fa-solid fa-triangle-exclamation" title="...">text</x-ucs.callout>
--}}

@props([
  'variant' => 'info',
  'icon' => 'fa-solid fa-circle-info',
  'title' => null,
])

@php
  [$boxClasses, $iconClasses] = match ($variant) {
    'warning' => ['border-warning/30 bg-warning-soft', 'text-warning'],
    'success' => ['border-success/30 bg-success-soft', 'text-success'],
    'danger' => ['border-danger/30 bg-danger-soft', 'text-danger'],
    default => ['border-line bg-surface-muted', 'text-accent'],
  };
@endphp

<div {{ $attributes->class(['rounded-card flex gap-3 border p-4 sm:p-5 print:break-inside-avoid', $boxClasses]) }}>
  <i class="{{ $icon }} {{ $iconClasses }} mt-1 shrink-0" aria-hidden="true"></i>
  <div class="text-ink min-w-0 space-y-2 text-[0.9375rem] leading-relaxed">
    @if ($title)
      <p class="font-semibold">{{ $title }}</p>
    @endif

    {{ $slot }}
  </div>
</div>
