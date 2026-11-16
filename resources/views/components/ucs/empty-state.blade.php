@props([
  'title',
  'description' => null,
  'icon' => 'fa-regular fa-folder-open',
])

<div
  {{ $attributes->class(['border-line rounded-card flex flex-col items-center border border-dashed px-6 py-10 text-center']) }}
>
  <div class="bg-surface-muted text-ink-subtle mb-3 flex h-10 w-10 items-center justify-center rounded-full">
    <i class="{{ $icon }}" aria-hidden="true"></i>
  </div>
  <p class="text-ink text-sm font-semibold">{{ $title }}</p>
  @if ($description)
    <p class="text-ink-subtle mt-1 max-w-sm text-sm">{{ $description }}</p>
  @endif

  @if (! $slot->isEmpty())
    <div class="mt-4">{{ $slot }}</div>
  @endif
</div>
