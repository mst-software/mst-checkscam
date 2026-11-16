@props([
  'stats' => [],
])

@php
  // Only render numbers the controller actually computed; never show placeholders.
  $items = collect([
    'total_scammers' => ['label' => 'Kẻ lừa đảo', 'dot' => 'bg-accent'],
    'total_comments' => ['label' => 'Bình luận', 'dot' => 'bg-ink-subtle'],
  ])->filter(fn ($item, $key) => isset($stats[$key]));
@endphp

@if ($items->isNotEmpty())
  <ul
    {{ $attributes->class(['text-ink-muted flex flex-wrap justify-center gap-x-8 gap-y-2 text-xs font-bold md:text-sm']) }}
    aria-label="Dữ liệu trên hệ thống"
  >
    @foreach ($items as $key => $item)
      <li class="flex items-center gap-1.5">
        <span class="{{ $item['dot'] }} size-1.5 rounded-full" aria-hidden="true"></span>
        <span class="text-ink tabular-nums">{{ number_format((int) $stats[$key], 0, ',', '.') }}</span>
        {{ $item['label'] }}
      </li>
    @endforeach
  </ul>
@endif
