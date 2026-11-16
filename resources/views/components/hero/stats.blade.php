@props([
  'stats' => [],
])

@php
  // Only render numbers the controller actually computed; never show placeholders.
  $items = collect([
    'total_scammers' => ['label' => ['Đối tượng', 'bị tố cáo'], 'dot' => 'bg-accent'],
    'total_comments' => ['label' => ['Bình luận', 'cộng đồng'], 'dot' => 'bg-ink-subtle'],
    'total_insurances' => ['label' => ['Đối tác', 'bảo chứng'], 'dot' => 'bg-success'],
  ])->filter(fn ($item, $key) => isset($stats[$key]));
@endphp

@if ($items->isNotEmpty())
  <ul
    {{ $attributes->class(['border-line bg-surface grid grid-cols-3 overflow-hidden rounded-2xl border shadow-xs sm:inline-flex']) }}
    aria-label="Dữ liệu trên hệ thống"
  >
    @foreach ($items as $key => $item)
      <li
        class="border-line flex min-w-0 flex-col items-center justify-center gap-1 border-l px-2 py-3 first:border-l-0 sm:flex-row sm:gap-3 sm:px-7 sm:py-3.5"
      >
        <span class="flex items-center gap-2 sm:gap-3">
          <span class="{{ $item['dot'] }} size-2 shrink-0 rounded-full" aria-hidden="true"></span>
          <span class="text-ink text-xl font-extrabold tracking-tight tabular-nums sm:text-[1.625rem]">
            {{ number_format((int) $stats[$key], 0, ',', '.') }}
          </span>
        </span>
        <span class="text-ink-subtle text-center text-xs leading-tight font-medium sm:text-left sm:text-[13px]">
          {{ $item['label'][0] }}
          <br />
          {{ $item['label'][1] }}
        </span>
      </li>
    @endforeach
  </ul>
@endif
