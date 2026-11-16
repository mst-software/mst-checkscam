{{--
  One insurance partner row. Expects: $insurance; optional $showServices (bool).
--}}
@php
  $services = is_array($insurance->services)
    ? collect($insurance->services)
      ->pluck('title')
      ->filter()
      ->implode(', ')
    : '';
@endphp

<a
  href="{{ route('insurances.frontend.show', $insurance->slug) }}"
  class="hover:bg-surface-muted focus-visible:outline-accent flex h-full items-center gap-3 p-4 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2"
>
  @if ($insurance->avatar_url)
    <img
      src="{{ $insurance->avatar_url }}"
      alt=""
      width="40"
      height="40"
      loading="lazy"
      class="border-line bg-surface-muted h-10 w-10 shrink-0 rounded-full border object-cover"
    />
  @else
    <span
      class="bg-surface-muted text-ink-muted flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
      aria-hidden="true"
    >
      {{ mb_strtoupper(mb_substr($insurance->full_name, 0, 1)) }}
    </span>
  @endif

  <span class="min-w-0 flex-1">
    <span class="text-ink flex items-center gap-1.5 text-sm font-medium">
      <span class="truncate">{{ $insurance->full_name }}</span>
      <i class="fa-solid fa-circle-check text-success text-xs" aria-hidden="true"></i>
      <span class="sr-only">(đã xác thực)</span>
    </span>
    <span class="text-ink-muted block text-xs">
      Quỹ bảo hiểm:
      <span class="text-ink font-medium tabular-nums">
        {{ number_format((int) $insurance->amount, 0, ',', '.') }} đ
      </span>
    </span>
    @if (($showServices ?? false) && $services !== '')
      <span class="text-ink-subtle mt-0.5 block truncate text-xs">Dịch vụ: {{ $services }}</span>
    @endif
  </span>

  <i class="fa-solid fa-chevron-right text-ink-subtle text-xs" aria-hidden="true"></i>
</a>
