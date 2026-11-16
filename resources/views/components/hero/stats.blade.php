@props([
  'stats' => [],
])

@php
  // Only render numbers the controller actually computed; never show placeholders.
  $items = collect([
    'total_reports' => 'Tố cáo đã duyệt',
    'total_scammers' => 'Đối tượng bị tố cáo',
    'total_comments' => 'Bình luận cộng đồng',
  ])->filter(fn ($label, $key) => isset($stats[$key]));
@endphp

@if ($items->isNotEmpty())
  <div {{ $attributes }}>
    <h2 class="text-ink-muted mb-3 text-sm font-medium">Dữ liệu trên hệ thống</h2>
    <div class="grid grid-cols-3 gap-3 lg:grid-cols-1">
      @foreach ($items as $key => $label)
        <x-ucs.stat
          :label="$label"
          :value="number_format((int) $stats[$key], 0, ',', '.')"
          class="lg:flex lg:items-baseline lg:justify-between lg:gap-4 lg:[&>p:last-child]:mt-0"
        />
      @endforeach
    </div>
  </div>
@endif
